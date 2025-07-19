<?php

namespace App\Jobs\Batches;

use App\Models\Address;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Laravel\Horizon\Contracts\Silenced;

class ProcessCompanyBatchImportJobUpsert implements ShouldQueue, Silenced
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;

    public function __construct(private array $batchData, private array $fieldMap)
    {
        //
    }

    /**
     * Execute the job - Alternative version using upsert for better performance
     */
    public function handle(): void
    {
        DB::transaction(function () {
            $companyData = [];
            $addressData = [];

            // Pre-filter duplicates within the batch and clean data
            $processedRegComs = [];
            $processedEuids = [];

            foreach ($this->batchData as $dataLine) {
                $regCom = $dataLine[$this->fieldMap['COD_INMATRICULARE']];
                $euid = $dataLine[$this->fieldMap['EUID']];

                // Skip if we've already processed this reg_com or euid in this batch
                if (isset($processedRegComs[$regCom]) || isset($processedEuids[$euid])) {
                    continue;
                }

                $processedRegComs[$regCom] = true;
                $processedEuids[$euid] = true;

                $companyData[] = [
                    'name' => $dataLine[$this->fieldMap['DENUMIRE']],
                    'cui' => $dataLine[$this->fieldMap['CUI']],
                    'reg_com' => $regCom,
                    'euid' => $euid,
                    'type' => $dataLine[$this->fieldMap['FORMA_JURIDICA']],
                    'registration_date' => date('Y-m-d H:i:s', strtotime($dataLine[$this->fieldMap['DATA_INMATRICULARE']])),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (empty($companyData)) {
                return; // No valid data to process
            }

            try {
                // Method 1: Use upsert with composite unique constraint
                Company::upsert(
                    $companyData,
                    ['reg_com'], // Primary unique identifier
                    ['name', 'cui', 'type', 'registration_date', 'updated_at']
                );
                sleep(10);

                // Handle EUID conflicts separately if needed
                $this->handleEuidConflicts($companyData);

            } catch (\Illuminate\Database\QueryException $e) {
                // Fallback: Process records individually to handle conflicts
                Log::warning('Bulk upsert failed, processing individually: ' . $e->getMessage());
                $this->processCompaniesIndividually($companyData);
            }

            // Get company IDs for addresses
            $regComs = array_column($companyData, 'reg_com');
            $companies = Company::whereIn('reg_com', $regComs)
                ->pluck('id', 'reg_com')
                ->toArray();

            foreach ($this->batchData as $dataLine) {
                $regCom = $dataLine[$this->fieldMap['COD_INMATRICULARE']];
                $companyId = $companies[$regCom] ?? null;

                if ($companyId) {
                    $addressData[] = [
                        'company_id' => $companyId,
                        'country' => $dataLine[$this->fieldMap['ADR_TARA']],
                        'city' => $dataLine[$this->fieldMap['ADR_LOCALITATE']],
                        'county' => $dataLine[$this->fieldMap['ADR_JUDET']],
                        'street' => $dataLine[$this->fieldMap['ADR_DEN_STRADA']],
                        'number' => $dataLine[$this->fieldMap['ADR_DEN_NR_STRADA']],
                        'block' => $dataLine[$this->fieldMap['ADR_BLOC']],
                        'scara' => $dataLine[$this->fieldMap['ADR_SCARA']],
                        'floor' => $dataLine[$this->fieldMap['ADR_ETAJ']],
                        'apartment' => $dataLine[$this->fieldMap['ADR_APARTAMENT']],
                        'postalCode' => $dataLine[$this->fieldMap['ADR_COD_POSTAL']],
                        'sector' => $dataLine[$this->fieldMap['ADR_SECTOR']],
                        'additional' => $dataLine[$this->fieldMap['ADR_COMPLETARE']],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // Bulk upsert addresses
            if (!empty($addressData)) {
                Address::upsert(
                    $addressData,
                    ['company_id'],
                    ['country', 'city', 'county', 'street', 'number', 'block', 'scara', 'floor', 'apartment', 'postalCode', 'sector', 'additional', 'updated_at']
                );
            }
        });
    }

    /**
     * Handle EUID conflicts by checking existing records
     */
    private function handleEuidConflicts(array $companyData): void
    {
        $euids = array_column($companyData, 'euid');
        $existingEuids = Company::whereIn('euid', $euids)
            ->pluck('reg_com', 'euid')
            ->toArray();

        foreach ($companyData as $company) {
            if (isset($existingEuids[$company['euid']]) &&
                $existingEuids[$company['euid']] !== $company['reg_com']) {

                Log::warning("EUID conflict detected: {$company['euid']} exists for different reg_com", [
                    'existing_reg_com' => $existingEuids[$company['euid']],
                    'new_reg_com' => $company['reg_com']
                ]);
            }
        }
    }

    /**
     * Fallback method to process companies individually
     */
    private function processCompaniesIndividually(array $companyData): void
    {
        foreach ($companyData as $company) {
            try {
                Company::updateOrCreate(
                    ['reg_com' => $company['reg_com']],
                    $company
                );
            } catch (\Illuminate\Database\QueryException $e) {
                if (str_contains($e->getMessage(), 'euid_unique')) {
                    Log::warning("Skipping duplicate EUID: {$company['euid']} for reg_com: {$company['reg_com']}");
                } else {
                    Log::error("Failed to process company: {$company['reg_com']}", [
                        'error' => $e->getMessage(),
                        'company' => $company
                    ]);
                }
            }
        }
    }
}
