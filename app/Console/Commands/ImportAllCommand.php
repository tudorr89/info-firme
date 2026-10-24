<?php

namespace App\Console\Commands;

use App\Services\Import\ImportPipeline;
use Illuminate\Console\Command;

class ImportAllCommand extends Command
{
    protected $signature = 'import:all
        {--dir= : Directory containing the CSV files (defaults to the configured import directory)}
        {--link-delay=60 : Minutes to wait before backfilling the company links}
        {--skip-companies : Only import the files that link to companies}';

    protected $description = 'Import all downloaded ONRC CSV files (companies, nomenclators, statuses, representatives, EU branches, CAEN)';

    public function handle(ImportPipeline $pipeline): int
    {
        $directory = $this->option('dir') ? rtrim((string) $this->option('dir'), '/') : null;
        $linkDelay = (int) $this->option('link-delay');
        $skipCompanies = (bool) $this->option('skip-companies');

        $this->output->title('Starting full import');
        $this->output->info('Using directory: '.($directory ?? config('import.directory')));

        $dispatched = $skipCompanies
            ? $pipeline->dispatchRelated($directory, $linkDelay)
            : $pipeline->dispatchAll($directory, $linkDelay);

        if ($dispatched === []) {
            $this->error('No importable CSV files found.');

            return self::FAILURE;
        }

        foreach ($dispatched as $filename) {
            $this->output->info("Queued import for {$filename}");
        }

        if ($linkDelay > 0) {
            $this->output->info("Company link backfill queued in {$linkDelay} minute(s)");
        }

        $this->output->success('All import jobs dispatched successfully!');
        $this->output->note('Monitor progress on the Horizon dashboard at: /admin/horizon');

        return self::SUCCESS;
    }
}
