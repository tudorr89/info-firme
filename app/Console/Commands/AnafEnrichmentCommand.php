<?php

namespace App\Console\Commands;

use App\Services\AnafEnrichmentService;
use Illuminate\Console\Command;

class AnafEnrichmentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'anaf:enrich';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enrich companies with data from ANAF.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if(env('ENRICH') == 'true') {
            $anafEnrichmentService = new AnafEnrichmentService();
            return $anafEnrichmentService->enrich();
        }
    }
}
