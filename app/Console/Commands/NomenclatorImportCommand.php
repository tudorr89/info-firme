<?php

namespace App\Console\Commands;

use App\Jobs\NomenclatorImportJob;
use Illuminate\Console\Command;

class NomenclatorImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:nomenclator {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import the nomenclator from a CSV file. n_stare_firma.csv';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->output->title('Starting import');
        dispatch(new NomenclatorImportJob($this->argument('file')));
        $this->output->success('Import successful');
    }
}
