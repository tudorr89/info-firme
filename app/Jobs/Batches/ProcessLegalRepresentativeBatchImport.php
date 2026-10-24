<?php

namespace App\Jobs\Batches;

use App\Models\Company;
use App\Models\LegalRepresentative;
use Carbon\Carbon;
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

    /**
     * Small chunks keep the IN(...) lookups out of MariaDB's Aria temp
     * tables, which fail with 'Record has changed' under load.
     */
    private const CHUNK_SIZE = 500;

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
        } catch (\Exception $exception) {
            Log::error("Legal Representatives Batch {$this->batchNumber} failed: ".$exception->getMessage());
            throw $exception;
        }
    }

    private function isDeadlockException(PDOException $exception): bool
    {
        return str_contains($exception->getMessage(), 'Deadlock') ||
               str_contains($exception->getMessage(), '1213') ||
               str_contains($exception->getMessage(), '40001');
    }

    private function processBatch(): void
    {
        foreach (array_chunk($this->batch, self::CHUNK_SIZE) as $chunk) {
            $this->processChunk($chunk);
        }
    }

    /**
     * @param  array<int, array<int, mixed>>  $chunk
     */
    private function processChunk(array $chunk): void
    {
        $insertData = [];
        $now = now();

        $registrations = array_filter(array_map(
            fn (array $line) => $line[$this->fieldMap['COD_INMATRICULARE']] ?? null,
            $chunk
        ));

        $companies = Company::whereIn('reg_com', $registrations)
            ->pluck('id', 'reg_com')
            ->all();

        foreach ($chunk as $line) {
            $registration = $line[$this->fieldMap['COD_INMATRICULARE']] ?? null;

            if (! $registration) {
                continue;
            }

            $companyId = $companies[$registration] ?? null;

            if ($companyId === null) {
                continue;
            }

            $insertData[] = [
                'company_id' => $companyId,
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
                return Carbon::createFromFormat('d/m/Y H:i:s', $dateStr)->toDateString();
            }

            // Fall back to date only format (d/m/Y)
            return Carbon::createFromFormat('d/m/Y', $dateStr)->toDateString();
        } catch (\Exception) {
            return null;
        }
    }
}
