<?php

namespace App\Jobs\Batches;

use App\Services\BannedCifService;
use App\Services\Import\BulkUpsert;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Laravel\Horizon\Contracts\Silenced;
use PDOException;

/**
 * Imports a batch of companies and their addresses.
 *
 * Both tables are written with a single statement per chunk (`INSERT ... ON
 * DUPLICATE KEY UPDATE`), which replaces the previous "select existing ids,
 * insert the new ones, update the rest one by one" approach. `updated_at` only
 * moves forward when a compared column actually changed, so a monthly re-import
 * does not mark the whole table as updated.
 */
class ProcessCompanyBatchImportJobUpsert implements ShouldQueue, Silenced
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [1, 5, 10];

    /**
     * @param  array<int, array<int, string>>  $batchData
     * @param  array<string, int>  $fieldMap
     */
    public function __construct(private array $batchData, private array $fieldMap)
    {
        //
    }

    public function handle(): void
    {
        try {
            $companyRows = $this->companyRows();

            if ($companyRows === []) {
                return;
            }

            BulkUpsert::upsert(
                'companies',
                $companyRows,
                ['name', 'cui', 'type', 'registration_date', 'website', 'parent_country', 'mark'],
                ['name', 'cui', 'type', 'registration_date', 'website', 'parent_country', 'mark']
            );

            BulkUpsert::upsertForCompanies(
                'addresses',
                $this->addressRows(),
                ['country', 'city', 'county', 'street', 'number', 'block', 'scara', 'floor',
                    'apartment', 'postalCode', 'sector', 'additional'],
                ['country', 'city', 'county', 'street', 'number', 'block', 'scara', 'floor',
                    'apartment', 'postalCode', 'sector', 'additional']
            );
        } catch (PDOException $exception) {
            if ($this->isDeadlockException($exception)) {
                Log::warning("Deadlock detected in batch import. Attempt {$this->attempts()}/3. Will retry.", [
                    'error' => $exception->getMessage(),
                ]);
            }

            throw $exception;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function companyRows(): array
    {
        $now = now()->toDateTimeString();
        $rows = [];
        $processedEuids = [];

        foreach ($this->batchData as $dataLine) {
            $regCom = trim((string) ($dataLine[$this->fieldMap['COD_INMATRICULARE']] ?? ''));
            $euid = trim((string) ($dataLine[$this->fieldMap['EUID']] ?? ''));
            $cui = trim((string) ($dataLine[$this->fieldMap['CUI']] ?? ''));

            if ($regCom === '' || $cui === '' || isset($rows[$regCom])) {
                continue;
            }

            // EUID is unique in the database: keep the first occurrence so the
            // upsert can never match a different company through that index.
            if ($euid !== '' && isset($processedEuids[$euid])) {
                continue;
            }

            if (BannedCifService::isBanned($cui)) {
                continue;
            }

            $processedEuids[$euid] = true;

            $rows[$regCom] = [
                'name' => $dataLine[$this->fieldMap['DENUMIRE']],
                'cui' => $cui,
                'reg_com' => $regCom,
                'euid' => $euid,
                'type' => $dataLine[$this->fieldMap['FORMA_JURIDICA']] ?? null,
                'registration_date' => $this->parseDate($dataLine[$this->fieldMap['DATA_INMATRICULARE']] ?? null),
                'website' => $dataLine[$this->fieldMap['WEB']] ?? null,
                'parent_country' => $dataLine[$this->fieldMap['TARA_FIRMA_MAMA']] ?? null,
                'mark' => $dataLine[$this->fieldMap['MARK']] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return array_values($rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function addressRows(): array
    {
        $now = now()->toDateTimeString();
        $rows = [];

        foreach ($this->batchData as $dataLine) {
            $regCom = trim((string) ($dataLine[$this->fieldMap['COD_INMATRICULARE']] ?? ''));

            if ($regCom === '' || isset($rows[$regCom])) {
                continue;
            }

            $rows[$regCom] = [
                'registration' => $regCom,
                'country' => $dataLine[$this->fieldMap['ADR_TARA']] ?? null,
                'city' => $dataLine[$this->fieldMap['ADR_LOCALITATE']] ?? null,
                'county' => $dataLine[$this->fieldMap['ADR_JUDET']] ?? null,
                'street' => $dataLine[$this->fieldMap['ADR_DEN_STRADA']] ?? null,
                'number' => $dataLine[$this->fieldMap['ADR_NR_STRADA']] ?? null,
                'block' => $dataLine[$this->fieldMap['ADR_BLOC']] ?? null,
                'scara' => $dataLine[$this->fieldMap['ADR_SCARA']] ?? null,
                'floor' => $dataLine[$this->fieldMap['ADR_ETAJ']] ?? null,
                'apartment' => $dataLine[$this->fieldMap['ADR_APARTAMENT']] ?? null,
                'postalCode' => $dataLine[$this->fieldMap['ADR_COD_POSTAL']] ?? null,
                'sector' => $dataLine[$this->fieldMap['ADR_SECTOR']] ?? null,
                'additional' => $dataLine[$this->fieldMap['ADR_COMPLETARE']] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return array_values($rows);
    }

    private function isDeadlockException(PDOException $exception): bool
    {
        return str_contains($exception->getMessage(), 'Deadlock') ||
               str_contains($exception->getMessage(), '1213') ||
               str_contains($exception->getMessage(), '40001');
    }

    /**
     * Parse a date in DD/MM/YYYY or DD.MM.YYYY format to Y-m-d H:i:s.
     */
    private function parseDate(?string $dateStr): ?string
    {
        if (! $dateStr) {
            return null;
        }

        $dateStr = trim($dateStr);

        $formats = str_contains($dateStr, '/')
            ? ['d/m/Y H:i:s', 'd/m/Y']
            : ['d.m.Y H:i:s', 'd.m.Y'];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $dateStr)->startOfDay()->format('Y-m-d H:i:s');
            } catch (\Exception) {
                continue;
            }
        }

        Log::warning("Failed to parse date: {$dateStr}");

        return null;
    }
}
