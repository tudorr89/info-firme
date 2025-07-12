<?php

namespace App\Console\Commands;

use App\Jobs\CaenCompanyImportJob;
use Illuminate\Console\Command;

class CaenCompanyImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:caen-company {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports CAEN companies links from a CSV file.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->output->title('Starting import');
        dispatch(new CaenCompanyImportJob($this->argument('file')));
        $this->output->success('Import successful');
    }
}
