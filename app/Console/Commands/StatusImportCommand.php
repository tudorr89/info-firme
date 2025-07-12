<?php

namespace App\Console\Commands;

use App\Jobs\StatusImportJob;
use Illuminate\Console\Command;

class StatusImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:status {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports Companies status from a CSV file.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->output->title('Starting import');
        dispatch(new StatusImportJob($this->argument('file')));
        $this->output->success('Import successful');
    }
}
