<?php

namespace App\Jobs\Batches;

use App\Models\Company;
use App\Models\EUBranch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Horizon\Contracts\Silenced;
use PDOException;

class ProcessEUBranchBatchImport implements ShouldQueue, Silenced
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    public $tries = 3;

    public $backoff = [1, 5, 10];

    public function __construct(
        private array $batch,
        private array $fieldMap,
        private int $batchNumber
    ) {
        //
    }

    public function handle(): void
    {
        try {
            DB::transaction(function () {
                $this->processBatch();
            });
        } catch (PDOException $e) {
            if ($this->isDeadlockException($e)) {
                Log::warning("Deadlock detected in EU branches batch {$this->batchNumber}. Attempt {$this->attempts()}/3. Will retry.", [
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
            Log::error("EU Branches Batch {$this->batchNumber} failed: ".$e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            Log::error("EU Branches Batch {$this->batchNumber} failed: ".$e->getMessage());
            throw $e;
        }
    }

    private function isDeadlockException(PDOException $e): bool
    {
        return str_contains($e->getMessage(), 'Deadlock') ||
               str_contains($e->getMessage(), '1213') ||
               str_contains($e->getMessage(), '40001');
    }

    private function processBatch(): void
    {
        $insertData = [];
        $now = now();

        foreach ($this->batch as $line) {
            $registration = $line[$this->fieldMap['COD_INMATRICULARE']] ?? null;

            if (! $registration) {
                continue;
            }

            // Get company_id from registration
            $company = Company::where('reg_com', $registration)->first();

            $insertData[] = [
                'company_id' => $company?->id,
                'registration' => $registration,
                'branch_type' => $line[$this->fieldMap['TIP_UNITATE']] ?? null,
                'branch_name' => $line[$this->fieldMap['DENUMIRE_SUCURSALA']] ?? null,
                'euid' => $line[$this->fieldMap['EUID']] ?? null,
                'tax_code' => $line[$this->fieldMap['COD_FISCAL']] ?? null,
                'country' => $line[$this->fieldMap['TARA']] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($insertData)) {
            EUBranch::insert($insertData);
        }
    }
}
