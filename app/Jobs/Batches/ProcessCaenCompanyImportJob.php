<?php

namespace App\Jobs\Batches;

use App\Services\Import\BulkUpsert;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Laravel\Horizon\Contracts\Silenced;

class ProcessCaenCompanyImportJob implements ShouldQueue, Silenced
{
    use Batchable, Queueable;

    public int $timeout = 300;

    public int $tries = 3;

    /**
     * @param  array<int, array<int, string>>  $dataChunk
     * @param  array<string, int>  $fieldMap
     */
    public function __construct(private array $dataChunk, private array $fieldMap)
    {
        //
    }

    public function handle(): void
    {
        $rows = $this->rows();

        if ($rows === []) {
            return;
        }

        BulkUpsert::upsertWhereCompanyExists(
            'caen_companies',
            $rows,
            ['version'],
            ['version']
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rows(): array
    {
        $now = now()->toDateTimeString();
        $rows = [];

        foreach ($this->dataChunk as $line) {
            $registration = trim((string) ($line[$this->fieldMap['COD_INMATRICULARE']] ?? ''));
            $code = trim((string) ($line[$this->fieldMap['COD_CAEN_AUTORIZAT']] ?? ''));

            if ($registration === '' || $code === '') {
                continue;
            }

            $key = $registration.'|'.$code;

            if (isset($rows[$key])) {
                continue;
            }

            $rows[$key] = [
                'registration' => $registration,
                'code' => (int) $code,
                'version' => (int) trim((string) ($line[$this->fieldMap['VER_CAEN_AUTORIZAT']] ?? '')),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return array_values($rows);
    }
}
