<?php

namespace App\Console\Commands;

use App\Services\LastUpdateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class AutoCSVImportCommand extends Command
{
    protected $signature = 'import:auto {--force : Import even when an update is not due}';

    protected $description = 'Import the downloaded ONRC CSV files from the import directory when an update is due.';

    public function handle(): int
    {
        $available = $this->availableFiles();

        if ($available === []) {
            $this->info('No files available to import.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! LastUpdateService::needsUpdate()) {
            $this->info('Import not yet due - skipping.');

            return self::SUCCESS;
        }

        $this->call('import:all');

        LastUpdateService::setDate();

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function availableFiles(): array
    {
        $disk = Storage::disk(config('import.disk'));
        $directory = config('import.directory');

        return array_values(array_filter(
            array_keys(config('import.files')),
            fn (string $filename) => $disk->exists("{$directory}/{$filename}")
        ));
    }
}
