<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Jobs\NomenclatorImportJob;
use App\Jobs\ProcessCSVJob;
use App\Services\LastUpdateService;

class AutoCSVImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:auto';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import the CSV files from the storage/csv folder to DB.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $files = Storage::files('csv');
        if(count($files) == 0 || LastUpdateService::needsUpdate() == false) {
            $this->info('No files to import or update not yet needed.');
            return;
        }

        foreach($files as $file) {
            if(str_contains($file,'nomeclator')) {
                dispatch(new NomenclatorImportJob(storage_path('app/'.$file)));

                Storage::delete($file);
            } else {
                dispatch(new ProcessCSVJob(storage_path('app/'.$file)));

                Storage::delete($file);
            }
        }

        LastUpdateService::setDate();
    }
}
