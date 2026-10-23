<?php

namespace App\Console\Commands;

use App\Jobs\LinkCompanyDataJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportAllCommand extends Command
{
    protected $signature = 'import:all
        {--dir= : Directory containing the CSV files (defaults to the configured import directory)}
        {--link-delay=60 : Minutes to wait before backfilling the company links}';

    protected $description = 'Import all downloaded ONRC CSV files (companies, nomenclators, statuses, representatives, EU branches, CAEN)';

    public function handle(): int
    {
        $disk = Storage::disk(config('import.disk'));
        $directory = $this->option('dir')
            ? rtrim((string) $this->option('dir'), '/')
            : $disk->path((string) config('import.directory'));

        $this->output->title('Starting full import');
        $this->output->info("Using directory: {$directory}");

        /** @var array<string, string> $files */
        $files = config('import.files');

        $available = [];
        $missing = [];

        foreach ($files as $filename => $command) {
            if (is_file("{$directory}/{$filename}")) {
                $available[$filename] = $command;

                continue;
            }

            $missing[] = $filename;
        }

        if ($available === []) {
            $this->error('No importable CSV files found in '.$directory);

            return self::FAILURE;
        }

        foreach ($missing as $filename) {
            $this->output->warning("Skipping missing file: {$filename}");
        }

        foreach ($available as $filename => $command) {
            $this->output->info("Dispatching {$command} for {$filename}");
            $this->call($command, ['file' => "{$directory}/{$filename}"]);
        }

        $this->queueLinkBackfill();

        $this->output->success('All import jobs dispatched successfully!');
        $this->output->note('Monitor progress on the Horizon dashboard at: /admin/horizon');

        return self::SUCCESS;
    }

    /**
     * Related files are imported in parallel with the company file, so the
     * `company_id` backfill is queued with a delay and is idempotent.
     */
    private function queueLinkBackfill(): void
    {
        $delay = (int) $this->option('link-delay');

        if ($delay <= 0) {
            $this->output->info('Link backfill disabled');

            return;
        }

        $this->output->info("Queueing the company link backfill in {$delay} minute(s)");

        LinkCompanyDataJob::dispatch()->delay(now()->addMinutes($delay));
    }
}
