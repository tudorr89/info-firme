<?php

namespace App\Jobs\Batches;

use App\Services\Import\BulkUpsert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Laravel\Horizon\Contracts\Silenced;
use PDOException;

/**
 * Imports the company status file.
 *
 * Rows are written with a single `INSERT ... SELECT ... ON DUPLICATE KEY
 * UPDATE` that joins `companies`, so statuses for companies that were not
 * imported (banned CIFs or rows outside the snapshot) are skipped by the
 * database instead of a separate existence query per batch.
 */
class ProcessStatusBatchImportJob implements ShouldQueue, Silenced
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [1, 5, 10];

    public int $timeout = 300;

    /**
     * @param  array<int, array<int, mixed>>  $batch
     * @param  array<string, int>  $fieldMap
     */
    public function __construct(
        private array $batch,
        private array $fieldMap,
        private int $batchNumber
    ) {}

    public function handle(): void
    {
        try {
            $rows = $this->rows();

            if ($rows === []) {
                return;
            }

            BulkUpsert::upsertWhereCompanyExists('statuses', $rows, ['status'], ['status']);
        } catch (PDOException $exception) {
            if ($this->isDeadlockException($exception)) {
                Log::warning("Deadlock detected in status batch {$this->batchNumber}. Attempt {$this->attempts()}/3. Will retry.", [
                    'error' => $exception->getMessage(),
                ]);
            } else {
                Log::error("Batch {$this->batchNumber} failed: ".$exception->getMessage());
            }

            throw $exception;
        }
    }

    private function isDeadlockException(PDOException $exception): bool
    {
        return str_contains($exception->getMessage(), 'Deadlock') ||
               str_contains($exception->getMessage(), '1213') ||
               str_contains($exception->getMessage(), '40001');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rows(): array
    {
        $now = now()->toDateTimeString();
        $rows = [];

        foreach ($this->batch as $line) {
            $registration = trim((string) ($line[$this->fieldMap['COD_INMATRICULARE']] ?? ''));
            $status = trim((string) ($line[$this->fieldMap['COD']] ?? ''));

            if ($registration === '' || $status === '' || isset($rows[$registration])) {
                continue;
            }

            $rows[$registration] = [
                'registration' => $registration,
                'status' => (int) $status,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return array_values($rows);
    }
}
