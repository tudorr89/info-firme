<?php

namespace App\Console\Commands;

use App\Jobs\CaenDefinitionImportJob;
use Illuminate\Console\Command;

class CaenDefinitionImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:caen-definition {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports CAEN definitions from a CSV file. n_caen.csv';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->output->title('Starting import');
        dispatch(new CaenDefinitionImportJob($this->argument('file')));
        $this->output->success('Import successful');
    }
}
