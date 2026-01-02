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

class ProcessLegalRepresentativeBatchImport implements ShouldQueue, Silenced
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

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
        } catch (\Exception $e) {
            Log::error("Legal Representatives Batch {$this->batchNumber} failed: ".$e->getMessage());
            throw $e;
        }
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

        try {
            return \Carbon\Carbon::createFromFormat('d/m/Y', $dateStr)->toDateString();
        } catch (\Exception) {
            return null;
        }
    }
}
