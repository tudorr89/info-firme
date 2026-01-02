<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ImportFirst10kRowsTest extends TestCase
{
    private const SAMPLE_SIZE = 10000;

    public function test_import_first_10k_companies(): void
    {
        Queue::fake();

        $file = $this->createSampleCsv(
            storage_path('app/imports/od_firme.csv'),
            storage_path('app/test-imports/companies-10k.csv')
        );

        $this->artisan('import:companies', ['file' => $file])->assertSuccessful();

        // Verify that the company import job was queued
        Queue::assertPushed(\App\Jobs\CompanyImportJob::class);
    }

    public function test_import_first_10k_statuses(): void
    {
        Queue::fake();

        $file = $this->createSampleCsv(
            storage_path('app/imports/od_stare_firma.csv'),
            storage_path('app/test-imports/statuses-10k.csv')
        );

        $this->artisan('import:status', ['file' => $file])->assertSuccessful();

        Queue::assertPushed(\App\Jobs\StatusImportJob::class);
    }

    public function test_import_first_10k_legal_representatives(): void
    {
        Queue::fake();

        $file = $this->createSampleCsv(
            storage_path('app/imports/od_reprezentanti_legali.csv'),
            storage_path('app/test-imports/legal-reps-10k.csv')
        );

        $this->artisan('import:legal-representatives', ['file' => $file])->assertSuccessful();

        Queue::assertPushed(\App\Jobs\LegalRepresentativeImportJob::class);
    }

    public function test_import_first_10k_natural_persons(): void
    {
        Queue::fake();

        $file = $this->createSampleCsv(
            storage_path('app/imports/od_reprezentanti_if.csv'),
            storage_path('app/test-imports/natural-persons-10k.csv')
        );

        $this->artisan('import:natural-persons', ['file' => $file])->assertSuccessful();

        Queue::assertPushed(\App\Jobs\NaturalPersonRepresentativeImportJob::class);
    }

    public function test_import_first_10k_eu_branches(): void
    {
        Queue::fake();

        $file = $this->createSampleCsv(
            storage_path('app/imports/od_sucursale_alte_state_membre.csv'),
            storage_path('app/test-imports/eu-branches-10k.csv')
        );

        $this->artisan('import:eu-branches', ['file' => $file])->assertSuccessful();

        Queue::assertPushed(\App\Jobs\EUBranchImportJob::class);
    }

    private function createSampleCsv(string $sourcePath, string $destPath): string
    {
        // Ensure destination directory exists
        $destDir = dirname($destPath);
        if (! is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $source = fopen($sourcePath, 'r');
        $dest = fopen($destPath, 'w');

        $lineCount = 0;
        while (($line = fgetcsv($source, 1000, '^')) !== false) {
            fputcsv($dest, $line, '^');
            $lineCount++;

            // Include header + sample rows
            if ($lineCount > self::SAMPLE_SIZE) {
                break;
            }
        }

        fclose($source);
        fclose($dest);

        return $destPath;
    }
}
