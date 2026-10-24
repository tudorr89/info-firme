<?php

namespace App\Services\Import;

use App\Jobs\CaenCompanyImportJob;
use App\Jobs\CaenDefinitionImportJob;
use App\Jobs\CaenVersionImportJob;
use App\Jobs\CompanyImportJob;
use App\Jobs\EUBranchImportJob;
use App\Jobs\ImportAllJob;
use App\Jobs\LegalRepresentativeImportJob;
use App\Jobs\LinkCompanyDataJob;
use App\Jobs\NaturalPersonRepresentativeImportJob;
use App\Jobs\NomenclatorImportJob;
use App\Jobs\StatusImportJob;
use Illuminate\Support\Facades\Storage;

/**
 * Dispatches the import of the downloaded ONRC files.
 *
 * Both the `import:all` command and {@see ImportAllJob} go through
 * this service, so the import order lives in one place (config/import.php) and
 * a queued refresh never has to call an artisan command from a job.
 */
class ImportPipeline
{
    /**
     * @return array<int, string> the filenames that were dispatched
     */
    public function dispatchAll(?string $directory = null, int $linkDelayMinutes = 60): array
    {
        return $this->dispatch($directory, $linkDelayMinutes, false);
    }

    /**
     * Import everything except the company file, which must already be there.
     *
     * @return array<int, string>
     */
    public function dispatchRelated(?string $directory = null, int $linkDelayMinutes = 60): array
    {
        return $this->dispatch($directory, $linkDelayMinutes, true);
    }

    /**
     * @return array<int, string>
     */
    private function dispatch(?string $directory, int $linkDelayMinutes, bool $skipCompanies): array
    {
        $directory = $directory === null
            ? Storage::disk(config('import.disk'))->path((string) config('import.directory'))
            : rtrim($directory, '/');

        $dispatched = [];

        /** @var array<string, class-string> $files */
        $files = config('import.files');

        foreach ($files as $filename => $jobClass) {
            if ($skipCompanies && $jobClass === CompanyImportJob::class) {
                continue;
            }

            $path = "{$directory}/{$filename}";

            if (! is_file($path)) {
                continue;
            }

            dispatch(new $jobClass($path));
            $dispatched[] = $filename;
        }

        if ($dispatched !== [] && $linkDelayMinutes > 0) {
            LinkCompanyDataJob::dispatch()->delay(now()->addMinutes($linkDelayMinutes));
        }

        return $dispatched;
    }

    /**
     * @return array<int, string> the filenames that are present on disk
     */
    public function availableFiles(?string $directory = null): array
    {
        $directory = $directory === null
            ? Storage::disk(config('import.disk'))->path((string) config('import.directory'))
            : rtrim($directory, '/');

        return array_values(array_filter(
            array_keys((array) config('import.files')),
            fn (string $filename) => is_file("{$directory}/{$filename}")
        ));
    }

    /**
     * @return array<int, class-string>
     */
    public function jobClasses(): array
    {
        return [
            CompanyImportJob::class,
            NomenclatorImportJob::class,
            CaenVersionImportJob::class,
            CaenDefinitionImportJob::class,
            StatusImportJob::class,
            CaenCompanyImportJob::class,
            LegalRepresentativeImportJob::class,
            NaturalPersonRepresentativeImportJob::class,
            EUBranchImportJob::class,
        ];
    }
}
