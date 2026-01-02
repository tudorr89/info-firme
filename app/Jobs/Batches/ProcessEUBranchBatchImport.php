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

class ProcessEUBranchBatchImport implements ShouldQueue, Silenced
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
            Log::error("EU Branches Batch {$this->batchNumber} failed: ".$e->getMessage());
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
