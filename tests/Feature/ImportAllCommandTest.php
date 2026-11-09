<?php

namespace Tests\Feature;

use App\Jobs\CaenCompanyImportJob;
use App\Jobs\CaenDefinitionImportJob;
use App\Jobs\CaenVersionImportJob;
use App\Jobs\CompanyImportJob;
use App\Jobs\EUBranchImportJob;
use App\Jobs\LegalRepresentativeImportJob;
use App\Jobs\NaturalPersonRepresentativeImportJob;
use App\Jobs\NomenclatorImportJob;
use App\Jobs\StatusImportJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportAllCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config([
            'import.disk' => 'local',
            'import.directory' => 'imports',
        ]);
    }

    public function test_a_full_import_queues_only_the_company_and_nomenclator_jobs(): void
    {
        Queue::fake();

        $this->putAllFiles();

        $this->artisan('import:all')->assertSuccessful();

        Queue::assertPushed(CompanyImportJob::class);
        Queue::assertPushed(NomenclatorImportJob::class);
        Queue::assertPushed(CaenVersionImportJob::class);
        Queue::assertPushed(CaenDefinitionImportJob::class);

        // The related files are queued by ImportRelatedDataJob from the company
        // batch's `then` callback, once the companies exist. Queuing them here
        // as well runs two rounds, and the second one duplicates rows.
        Queue::assertNotPushed(StatusImportJob::class);
        Queue::assertNotPushed(CaenCompanyImportJob::class);
        Queue::assertNotPushed(LegalRepresentativeImportJob::class);
        Queue::assertNotPushed(NaturalPersonRepresentativeImportJob::class);
        Queue::assertNotPushed(EUBranchImportJob::class);
    }

    public function test_the_related_files_are_queued_when_the_company_file_is_missing(): void
    {
        Queue::fake();

        Storage::disk('local')->put('imports/od_stare_firma.csv', "HEADER\n");

        $this->artisan('import:all')->assertSuccessful();

        // Without the company file there is no batch `then` callback, so the
        // related files have to be dispatched directly.
        Queue::assertPushed(StatusImportJob::class);
        Queue::assertNotPushed(CompanyImportJob::class);
    }

    public function test_the_skip_companies_option_queues_the_related_files(): void
    {
        Queue::fake();

        $this->putAllFiles();

        $this->artisan('import:all', ['--skip-companies' => true])->assertSuccessful();

        Queue::assertNotPushed(CompanyImportJob::class);
        Queue::assertPushed(StatusImportJob::class);
        Queue::assertPushed(LegalRepresentativeImportJob::class);
        Queue::assertPushed(NaturalPersonRepresentativeImportJob::class);
        Queue::assertPushed(EUBranchImportJob::class);
        Queue::assertPushed(CaenCompanyImportJob::class);
    }

    public function test_it_skips_missing_files_and_still_imports_the_rest(): void
    {
        Queue::fake();

        Storage::disk('local')->put('imports/od_firme.csv', "HEADER\n");

        $this->artisan('import:all')->assertSuccessful();

        Queue::assertPushed(CompanyImportJob::class);
        Queue::assertNotPushed(StatusImportJob::class);
        Queue::assertNotPushed(CaenCompanyImportJob::class);
    }

    public function test_it_fails_when_no_importable_files_exist(): void
    {
        Queue::fake();

        $this->artisan('import:all')->assertFailed();

        Queue::assertNothingPushed();
    }

    public function test_import_auto_delegates_to_import_all(): void
    {
        Queue::fake();

        Storage::disk('local')->put('imports/od_firme.csv', "HEADER\n");

        $this->artisan('import:auto', ['--force' => true])->assertSuccessful();

        Queue::assertPushed(CompanyImportJob::class);
    }

    private function putAllFiles(): void
    {
        $files = [
            'od_firme.csv',
            'od_stare_firma.csv',
            'od_reprezentanti_legali.csv',
            'od_reprezentanti_if.csv',
            'od_sucursale_alte_state_membre.csv',
            'od_caen_autorizat.csv',
            'n_caen.csv',
            'n_versiune_caen.csv',
            'n_stare_firma.csv',
        ];

        foreach ($files as $file) {
            Storage::disk('local')->put("imports/{$file}", "HEADER\n");
        }
    }
}
