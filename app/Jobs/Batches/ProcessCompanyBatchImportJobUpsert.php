<?php

namespace App\Jobs\Batches;

use App\Models\Address;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Horizon\Contracts\Silenced;
use PDOException;

class ProcessCompanyBatchImportJobUpsert implements ShouldQueue, Silenced
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;

    public $tries = 3;

    public $backoff = [1, 5, 10];

    public function __construct(private array $batchData, private array $fieldMap)
    {
        //
    }

    /**
     * Execute the job - Optimized for insert performance with separate update handling
     */
    public function handle(): void
    {
        try {
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

                    $cui = trim($dataLine[$this->fieldMap['CUI']] ?? '');

                    // Skip records without valid CUI
                    if (empty($cui) || $cui === '0') {
                        continue;
                    }

                    $companyData[] = [
                        'name' => $dataLine[$this->fieldMap['DENUMIRE']],
                        'cui' => $cui,
                        'reg_com' => $regCom,
                        'euid' => $euid,
                        'type' => $dataLine[$this->fieldMap['FORMA_JURIDICA']],
                        'registration_date' => $this->parseDate($dataLine[$this->fieldMap['DATA_INMATRICULARE']]),
                        'website' => $dataLine[$this->fieldMap['WEB']] ?? null,
                        'parent_country' => $dataLine[$this->fieldMap['TARA_FIRMA_MAMA']] ?? null,
                        'mark' => $dataLine[$this->fieldMap['MARK']] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (empty($companyData)) {
                    return; // No valid data to process
                }

                // Optimize: Try bulk insert first (faster than upsert for new records)
                $existingRegComs = Company::whereIn('reg_com', array_column($companyData, 'reg_com'))
                    ->pluck('id', 'reg_com')
                    ->toArray();

                $newCompanies = [];
                $existingCompanies = [];

                foreach ($companyData as $company) {
                    if (isset($existingRegComs[$company['reg_com']])) {
                        $company['id'] = $existingRegComs[$company['reg_com']];
                        $existingCompanies[] = $company;
                    } else {
                        $newCompanies[] = $company;
                    }
                }

                // Insert new companies (ignore duplicates on unique constraints)
                if (! empty($newCompanies)) {
                    Company::insertOrIgnore($newCompanies);
                }

                // Update existing companies with only changed fields
                if (! empty($existingCompanies)) {
                    foreach ($existingCompanies as $company) {
                        Company::where('reg_com', $company['reg_com'])->update([
                            'name' => $company['name'],
                            'cui' => $company['cui'],
                            'type' => $company['type'],
                            'registration_date' => $company['registration_date'],
                            'website' => $company['website'],
                            'parent_country' => $company['parent_country'],
                            'mark' => $company['mark'],
                            'updated_at' => now(),
                        ]);
                    }
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
                            'number' => $dataLine[$this->fieldMap['ADR_NR_STRADA']],
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

                // Optimize address import: separate new and existing
                if (! empty($addressData)) {
                    $existingCompanyIds = Address::whereIn('company_id', array_column($addressData, 'company_id'))
                        ->pluck('company_id')
                        ->toArray();

                    $newAddresses = [];
                    $existingAddresses = [];

                    foreach ($addressData as $address) {
                        if (in_array($address['company_id'], $existingCompanyIds, true)) {
                            $existingAddresses[] = $address;
                        } else {
                            $newAddresses[] = $address;
                        }
                    }

                    // Insert new addresses
                    if (! empty($newAddresses)) {
                        Address::insert($newAddresses);
                    }

                    // Update existing addresses
                    if (! empty($existingAddresses)) {
                        foreach ($existingAddresses as $address) {
                            Address::where('company_id', $address['company_id'])->update([
                                'country' => $address['country'],
                                'city' => $address['city'],
                                'county' => $address['county'],
                                'street' => $address['street'],
                                'number' => $address['number'],
                                'block' => $address['block'],
                                'scara' => $address['scara'],
                                'floor' => $address['floor'],
                                'apartment' => $address['apartment'],
                                'postalCode' => $address['postalCode'],
                                'sector' => $address['sector'],
                                'additional' => $address['additional'],
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            });
        } catch (PDOException $e) {
            if ($this->isDeadlockException($e)) {
                Log::warning("Deadlock detected in batch import. Attempt {$this->attempts()}/3. Will retry.", [
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
            throw $e;
        }
    }

    private function isDeadlockException(PDOException $e): bool
    {
        return str_contains($e->getMessage(), 'Deadlock') ||
               str_contains($e->getMessage(), '1213') ||
               str_contains($e->getMessage(), '40001');
    }

    /**
     * Parse date in DD/MM/YYYY format to Y-m-d H:i:s
     */
    private function parseDate(?string $dateStr): ?string
    {
        if (! $dateStr) {
            return null;
        }

        $dateStr = trim($dateStr);

        try {
            // Try format with timestamp first (d/m/Y H:i:s)
            if (str_contains($dateStr, ' ')) {
                return \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $dateStr)->format('Y-m-d H:i:s');
            }

            // Fall back to date only format (d/m/Y)
            return \Carbon\Carbon::createFromFormat('d/m/Y', $dateStr)->format('Y-m-d H:i:s');
        } catch (\Exception) {
            Log::warning("Failed to parse date: {$dateStr}");

            return null;
        }
    }
}
