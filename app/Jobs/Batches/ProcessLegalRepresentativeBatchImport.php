<?php

namespace App\Jobs\Batches;

use App\Models\Company;
use App\Models\LegalRepresentative;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Horizon\Contracts\Silenced;
use PDOException;

class ProcessLegalRepresentativeBatchImport implements ShouldQueue, Silenced
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
                Log::warning("Deadlock detected in legal representatives batch {$this->batchNumber}. Attempt {$this->attempts()}/3. Will retry.", [
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
            Log::error("Legal Representatives Batch {$this->batchNumber} failed: ".$e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            Log::error("Legal Representatives Batch {$this->batchNumber} failed: ".$e->getMessage());
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
                'person_name' => $line[$this->fieldMap['PERSOANA_IMPUTERNICITA']] ?? null,
                'role' => $line[$this->fieldMap['CALITATE']] ?? null,
                'birth_date' => $this->parseDate($line[$this->fieldMap['DATA_NASTERE']] ?? null),
                'birth_location' => $line[$this->fieldMap['LOCALITATE_NASTERE']] ?? null,
                'birth_county' => $line[$this->fieldMap['JUDET_NASTERE']] ?? null,
                'birth_country' => $line[$this->fieldMap['TARA_NASTERE']] ?? null,
                'current_location' => $line[$this->fieldMap['LOCALITATE']] ?? null,
                'current_county' => $line[$this->fieldMap['JUDET']] ?? null,
                'current_country' => $line[$this->fieldMap['TARA']] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($insertData)) {
            LegalRepresentative::insert($insertData);
        }
    }

    private function parseDate(?string $dateStr): ?string
    {
        if (! $dateStr) {
            return null;
        }

        $dateStr = trim($dateStr);

        try {
            // Try format with timestamp first (d/m/Y H:i:s)
            if (str_contains($dateStr, ' ')) {
                return \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $dateStr)->toDateString();
            }

            // Fall back to date only format (d/m/Y)
            return \Carbon\Carbon::createFromFormat('d/m/Y', $dateStr)->toDateString();
        } catch (\Exception) {
            return null;
        }
    }
}
