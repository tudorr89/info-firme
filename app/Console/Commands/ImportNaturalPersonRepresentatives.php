<?php

namespace App\Console\Commands;

use App\Jobs\NaturalPersonRepresentativeImportJob;
use Illuminate\Console\Command;

class ImportNaturalPersonRepresentatives extends Command
{
    protected $signature = 'import:natural-persons {file}';

    protected $description = 'Imports natural person representatives from a CSV file. od_reprezentanti_if.csv';

    public function handle(): void
    {
        $this->output->title('Starting natural person representatives import');
        dispatch(new NaturalPersonRepresentativeImportJob($this->argument('file')));
        $this->output->success('Import successful');
    }
}
