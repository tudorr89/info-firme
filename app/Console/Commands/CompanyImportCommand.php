<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\CompanyImportJob;

class CompanyImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:companies {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import the companies from a CSV file.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->output->title('Starting import');
        dispatch(new CompanyImportJob($this->argument('file')));
        $this->output->success('Import successful');
    }
}
