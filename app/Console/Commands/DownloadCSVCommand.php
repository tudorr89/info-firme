<?php

namespace App\Console\Commands;

use App\Services\CSVDownloadService;
use Illuminate\Console\Command;

class DownloadCSVCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'download:csv';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download the CSV files from the data.gov.ro. website using the API.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        CSVDownloadService::download();
    }
}
