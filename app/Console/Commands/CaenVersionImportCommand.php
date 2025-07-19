<?php

namespace App\Console\Commands;

use App\Jobs\CaenVersionImportJob;
use Illuminate\Console\Command;

class CaenVersionImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:caen-version {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports CAEN versions from a CSV file. n_caen_versiune.csv';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->output->title('Starting import');
        dispatch(new CaenVersionImportJob($this->argument('file')));
        $this->output->success('Import successful');
    }
}
