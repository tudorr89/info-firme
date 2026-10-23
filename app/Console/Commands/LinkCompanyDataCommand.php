<?php

namespace App\Console\Commands;

use App\Services\CompanyLinkService;
use Illuminate\Console\Command;

class LinkCompanyDataCommand extends Command
{
    protected $signature = 'companies:link
        {--prune : Also delete related rows whose company was not imported (banned or removed)}
        {--dry-run : Only report the rows that are missing a link}';

    protected $description = 'Backfill the company_id link on related ONRC import tables.';

    public function handle(CompanyLinkService $service): int
    {
        if ($this->option('dry-run')) {
            foreach ($service->unlinkedCounts() as $table => $count) {
                $this->line("  {$table}: {$count}");
            }

            return self::SUCCESS;
        }

        $this->output->title('Linking company data');

        foreach ($service->linkAll() as $table => $count) {
            $this->line("  {$table}: {$count} linked");
        }

        if ($this->option('prune')) {
            $this->output->info('Pruning orphan rows');

            foreach ($service->pruneOrphans() as $table => $count) {
                $this->line("  {$table}: {$count} pruned");
            }
        }

        $this->output->success('Company data linked.');

        return self::SUCCESS;
    }
}
