<?php

namespace App\Jobs\Batches;

use App\Models\Status;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Horizon\Contracts\Silenced;
use PDOException;

class ProcessStatusBatchImportJob implements ShouldQueue, Silenced
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [1, 5, 10];

    /** 5 minutes per batch. */
    public int $timeout = 300;

    public function __construct(
        private array $batch,
        private array $fieldMap,
        private int $batchNumber
    ) {}

    public function handle(): void
    {
        try {
            DB::transaction(function () {
                $this->processUpsertBatch();
            });
        } catch (PDOException $e) {
            if ($this->isDeadlockException($e)) {
                Log::warning("Deadlock detected in status batch {$this->batchNumber}. Attempt {$this->attempts()}/3. Will retry.", [
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
            Log::error("Batch {$this->batchNumber} failed: ".$e->getMessage());
            throw $e;
        } catch (\Exception $exception) {
            Log::error("Batch {$this->batchNumber} failed: ".$exception->getMessage());
            throw $exception;
        }
    }

    private function isDeadlockException(PDOException $exception): bool
    {
        return str_contains($exception->getMessage(), 'Deadlock') ||
               str_contains($exception->getMessage(), '1213') ||
               str_contains($exception->getMessage(), '40001');
    }

    private function processUpsertBatch(): void
    {
        $upsertData = [];
        $now = now();

        foreach ($this->batch as $line) {
            $registration = $line[$this->fieldMap['COD_INMATRICULARE']] ?? null;
            $status = $line[$this->fieldMap['COD']] ?? null;

            if (! $registration) {
                continue;
            }

            $upsertData[] = [
                'registration' => $registration,
                'status' => $status,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($upsertData)) {
            // Use upsert for better performance
            Status::upsert(
                $upsertData,
                ['registration'], // unique columns
                ['status', 'updated_at'] // columns to update
            );
        }
    }
}
