<?php

namespace App\Console\Commands;

use App\Jobs\EUBranchImportJob;
use Illuminate\Console\Command;

class ImportEUBranches extends Command
{
    protected $signature = 'import:eu-branches {file}';

    protected $description = 'Imports EU branches from a CSV file. od_sucursale_alte_state_membre.csv';

    public function handle(): void
    {
        $this->output->title('Starting EU branches import');
        dispatch(new EUBranchImportJob($this->argument('file')));
        $this->output->success('Import successful');
    }
}
