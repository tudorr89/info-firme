<?php

namespace App\Jobs\Batches;

use App\Models\CaenCompany;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Laravel\Horizon\Contracts\Silenced;

class ProcessCaenCompanyImportJob implements ShouldQueue, Silenced
{
    use Queueable, Batchable;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    public function __construct(private array $dataChunk, private array $fieldMap)
    {
        //
    }

    /**
     * Execute the job - Process multiple records in bulk
     */
    public function handle(): void
    {
        $insertData = [];
        $updateData = [];
        $existingRegistrations = [];

        // Get existing registrations in one query
        $registrationCodes = array_map(function($line) {
            return [
                'registration' => $line[$this->fieldMap['COD_INMATRICULARE']],
                'code' => $line[$this->fieldMap['COD_CAEN_AUTORIZAT']],
            ];
        }, $this->dataChunk);

        $existing = CaenCompany::whereIn('registration', array_column($registrationCodes, 'registration'))
            ->whereIn('code', array_column($registrationCodes, 'code'))
            ->pluck('registration', 'code','version')
            ->toArray();

        foreach ($this->dataChunk as $line) {
            $registration = $line[$this->fieldMap['COD_INMATRICULARE']];
            $code = $line[$this->fieldMap['COD_CAEN_AUTORIZAT']];
            $version = $line[$this->fieldMap['VER_CAEN_AUTORIZAT']];

            $record = [
                'registration' => $registration,
                'code' => $code,
                'version' => $version,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (!isset($existing[$code]) || $existing[$code] !== $registration) {
                $insertData[] = $record;
            } else {
                $updateData[] = $record;
            }
        }

        // Bulk insert new records
        if (!empty($insertData)) {
            CaenCompany::insert($insertData);
        }

        // Bulk update existing records if needed
        if (!empty($updateData)) {
            foreach ($updateData as $record) {
                CaenCompany::where('registration', $record['registration'])
                    ->where('code', $record['code'])
                    ->update(['version' => $record['version'], 'updated_at' => $record['updated_at']]);
            }
        }
    }
}
