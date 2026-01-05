<?php

namespace App\Console\Commands;

use App\Jobs\CaenRev3ImportJob;
use Illuminate\Console\Command;

class CaenRev3Import extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:caen-rev3 {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import CAEN Rev.3 codes from correspondence CSV file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $file = $this->argument('file');

        if (! file_exists($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }

        $this->info('Starting CAEN Rev.3 import...');
        dispatch(new CaenRev3ImportJob($file));
        $this->info('Import job dispatched successfully');

        return self::SUCCESS;
    }
}
