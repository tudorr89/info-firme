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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The per-file import commands are thin wrappers: they only queue the job that
 * knows the file format. The job itself is covered by the pipeline tests, so a
 * tiny fixture is enough here (the old test read the real 663 MB ONRC export,
 * which is not in git and made the suite fail on a fresh checkout).
 */
class ImportFileCommandsTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Storage::fake('local');
        Storage::disk('local')->put('fixtures/sample.csv', "COD_INMATRICULARE^COD\nJ40/1/2024^1048\n");

        $this->file = Storage::disk('local')->path('fixtures/sample.csv');
    }

    /**
     * @return array<string, array{string, class-string}>
     */
    public static function commands(): array
    {
        return [
            'companies' => ['import:companies', CompanyImportJob::class],
            'statuses' => ['import:status', StatusImportJob::class],
            'legal representatives' => ['import:legal-representatives', LegalRepresentativeImportJob::class],
            'natural persons' => ['import:natural-persons', NaturalPersonRepresentativeImportJob::class],
            'eu branches' => ['import:eu-branches', EUBranchImportJob::class],
            'caen companies' => ['import:caen-company', CaenCompanyImportJob::class],
            'caen definitions' => ['import:caen-definition', CaenDefinitionImportJob::class],
            'caen versions' => ['import:caen-version', CaenVersionImportJob::class],
            'nomenclators' => ['import:nomenclator', NomenclatorImportJob::class],
        ];
    }

    /**
     * @param  class-string  $job
     */
    #[DataProvider('commands')]
    public function test_each_file_import_command_queues_its_job(string $command, string $job): void
    {
        $this->artisan($command, ['file' => $this->file])->assertSuccessful();

        Queue::assertPushed($job);
    }
}
