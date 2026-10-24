<?php

namespace App\Jobs;

use Illuminate\Bus\Batch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Horizon\Contracts\Silenced;
use RuntimeException;
use Throwable;

/**
 * Imports the company file.
 *
 * The file is sliced into chunk files while streaming (a few million rows do
 * not fit in a job payload) and the chunks are dispatched as one Laravel batch.
 * The related files (statuses, CAEN, representatives, branches) are dispatched
 * only in the batch's `then` callback: they link to `companies`, so running them
 * before the companies exist would silently skip rows.
 */
class CompanyImportJob implements ShouldQueue, Silenced
{
    use Dispatchable, Queueable;

    public int $timeout = 1800;

    public int $tries = 1;

    private const CHUNK_SIZE = 25000;

    public function __construct(private string $file)
    {
        //
    }

    public function handle(): void
    {
        $fieldMap = [
            'DENUMIRE' => 0,
            'CUI' => 1,
            'COD_INMATRICULARE' => 2,
            'DATA_INMATRICULARE' => 3,
            'EUID' => 4,
            'FORMA_JURIDICA' => 5,
            'ADR_TARA' => 6,
            'ADR_JUDET' => 7,
            'ADR_LOCALITATE' => 8,
            'ADR_DEN_STRADA' => 9,
            'ADR_NR_STRADA' => 10,
            'ADR_BLOC' => 11,
            'ADR_SCARA' => 12,
            'ADR_ETAJ' => 13,
            'ADR_APARTAMENT' => 14,
            'ADR_COD_POSTAL' => 15,
            'ADR_SECTOR' => 16,
            'ADR_COMPLETARE' => 17,
            'WEB' => 18,
            'TARA_FIRMA_MAMA' => 19,
            'MARK' => 20,
        ];

        $chunkFiles = $this->splitIntoChunks();
        $jobs = array_map(
            fn (string $chunkFile) => new CompanyChunkImportJob($chunkFile, $fieldMap),
            $chunkFiles
        );

        Bus::batch($jobs)
            ->name('Import companies')
            ->allowFailures()
            ->then(function (Batch $batch): void {
                Log::info('Company import finished', [
                    'total' => $batch->totalJobs,
                    'failed' => $batch->failedJobs,
                ]);

                ImportRelatedDataJob::dispatch();
            })
            ->dispatch();

        Log::info('Company import dispatched', ['chunks' => count($jobs)]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Company import failed: '.$exception?->getMessage());
    }

    /**
     * Split the CSV into chunk files that keep the original '^' layout.
     *
     * @return array<int, string>
     */
    private function splitIntoChunks(): array
    {
        $fileStream = fopen($this->file, 'r');

        if ($fileStream === false) {
            throw new RuntimeException("Cannot open company file: {$this->file}");
        }

        $directory = (string) config('import.directory').'/chunks';
        $disk = Storage::disk(config('import.disk'));

        if (! is_dir($disk->path($directory))) {
            mkdir($disk->path($directory), 0755, true);
        }

        $chunkFiles = [];
        $handle = null;
        $rowsInChunk = 0;
        $skipHeader = true;

        while (($line = fgetcsv($fileStream, 0, '^')) !== false) {
            if ($skipHeader) {
                $skipHeader = false;

                continue;
            }

            if ($handle === null) {
                $path = $disk->path($directory.'/companies_'.(count($chunkFiles) + 1).'.csv');
                $handle = fopen($path, 'w');

                if ($handle === false) {
                    throw new RuntimeException("Cannot write company chunk file: {$path}");
                }

                $chunkFiles[] = $path;
                $rowsInChunk = 0;
            }

            fputcsv($handle, $line, '^', '"', '');
            $rowsInChunk++;

            if ($rowsInChunk >= self::CHUNK_SIZE) {
                fclose($handle);
                $handle = null;
            }
        }

        if ($handle !== null) {
            fclose($handle);
        }

        fclose($fileStream);

        return $chunkFiles;
    }
}
