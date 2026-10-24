<?php

namespace App\Console\Commands;

use App\Services\Import\ImportPipeline;
use App\Services\LastUpdateService;
use Illuminate\Console\Command;

class AutoCSVImportCommand extends Command
{
    protected $signature = 'import:auto {--force : Import even when an update is not due}';

    protected $description = 'Import the downloaded ONRC CSV files from the import directory when an update is due.';

    public function handle(ImportPipeline $pipeline): int
    {
        $available = $pipeline->availableFiles();

        if ($available === []) {
            $this->info('No files available to import.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! LastUpdateService::needsUpdate()) {
            $this->info('Import not yet due - skipping.');

            return self::SUCCESS;
        }

        $pipeline->dispatchAll();

        LastUpdateService::setDate();

        return self::SUCCESS;
    }
}
