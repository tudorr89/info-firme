<?php

namespace App\Services\Import;

use App\Jobs\CaenCompanyImportJob;
use App\Jobs\CaenDefinitionImportJob;
use App\Jobs\CaenVersionImportJob;
use App\Jobs\CompanyImportJob;
use App\Jobs\EUBranchImportJob;
use App\Jobs\ImportAllJob;
use App\Jobs\ImportRelatedDataJob;
use App\Jobs\LegalRepresentativeImportJob;
use App\Jobs\LinkCompanyDataJob;
use App\Jobs\NaturalPersonRepresentativeImportJob;
use App\Jobs\NomenclatorImportJob;
use App\Jobs\StatusImportJob;
use Closure;
use Illuminate\Support\Facades\Storage;

/**
 * Dispatches the import of the downloaded ONRC files.
 *
 * Both the `import:all` command and {@see ImportAllJob} go through
 * this service, so the import order lives in one place (config/import.php) and
 * a queued refresh never has to call an artisan command from a job.
 *
 * The files listed under `import.deferred` are only queued by
 * {@see ImportRelatedDataJob}, which runs from the company import
 * batch's `then` callback. A normal dispatch therefore queues the company file
 * and the nomenclators; the deferred files follow once the companies exist.
 */
class ImportPipeline
{
    /**
     * @return array<int, string> the filenames that were dispatched
     */
    public function dispatchAll(?string $directory = null, int $linkDelayMinutes = 60): array
    {
        return $this->dispatch($directory, $linkDelayMinutes, null);
    }

    /**
     * Import everything except the company file, which must already be there.
     *
     * @return array<int, string>
     */
    public function dispatchRelated(?string $directory = null, int $linkDelayMinutes = 60): array
    {
        /** @var array<int, string> $companyFiles */
        $companyFiles = array_keys((array) config('import.files'), CompanyImportJob::class, true);

        return $this->dispatch(
            $directory,
            $linkDelayMinutes,
            fn (string $filename): bool => ! in_array($filename, $companyFiles, true)
        );
    }

    /**
     * Queue only the files that need the companies to exist (the files that
     * link to `companies`). Called from the company batch's `then` callback.
     *
     * @return array<int, string>
     */
    public function dispatchDeferred(?string $directory = null, int $linkDelayMinutes = 60): array
    {
        /** @var array<int, string> $deferred */
        $deferred = (array) config('import.deferred', []);

        return $this->dispatch(
            $directory,
            $linkDelayMinutes,
            fn (string $filename): bool => in_array($filename, $deferred, true)
        );
    }

    /**
     * @param  (Closure(string): bool)|null  $filter
     * @return array<int, string>
     */
    private function dispatch(?string $directory, int $linkDelayMinutes, ?Closure $filter): array
    {
        $directory = $directory === null
            ? Storage::disk(config('import.disk'))->path((string) config('import.directory'))
            : rtrim($directory, '/');

        $dispatched = [];

        /** @var array<string, class-string> $files */
        $files = config('import.files');

        /** @var array<int, string> $deferred */
        $deferred = (array) config('import.deferred', []);

        // The related files are only deferred when the company file is part of
        // this dispatch: without it there is no batch `then` callback to queue
        // them, so they are dispatched normally (they skip rows they cannot link).
        $companyFilename = array_search(CompanyImportJob::class, $files, true);
        $deferRelated = $filter === null
            && is_string($companyFilename)
            && is_file("{$directory}/{$companyFilename}");

        foreach ($files as $filename => $jobClass) {
            if ($filter !== null && ! $filter($filename)) {
                continue;
            }

            if ($deferRelated && in_array($filename, $deferred, true)) {
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
