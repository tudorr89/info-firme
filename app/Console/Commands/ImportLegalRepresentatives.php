<?php

namespace App\Console\Commands;

use App\Jobs\LegalRepresentativeImportJob;
use Illuminate\Console\Command;

class ImportLegalRepresentatives extends Command
{
    protected $signature = 'import:legal-representatives {file}';

    protected $description = 'Imports legal representatives from a CSV file. od_reprezentanti_legali.csv';

    public function handle(): void
    {
        $this->output->title('Starting legal representatives import');
        dispatch(new LegalRepresentativeImportJob($this->argument('file')));
        $this->output->success('Import successful');
    }
}
