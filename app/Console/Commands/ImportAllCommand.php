<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportAllCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'import:all {--dir=storage/app/imports : Directory containing CSV files}';

    /**
     * The console command description.
     */
    protected $description = 'Import all CSV files (companies, statuses, representatives, EU branches)';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $dir = $this->option('dir');

        $requiredFiles = [
            'companies' => 'od_firme.csv',
            'status' => 'od_stare_firma.csv',
            'legal-representatives' => 'od_reprezentanti_legali.csv',
            'natural-persons' => 'od_reprezentanti_if.csv',
            'eu-branches' => 'od_sucursale_alte_state_membre.csv',
        ];

        $optionalFiles = [
            'caen-definition' => 'n_caen.csv',
            'caen-version' => 'n_caen_versiune.csv',
            'caen-company' => 'od_caen_autorizat.csv',
        ];

        $this->output->title('Starting full import');
        $this->output->info("Using directory: $dir");

        $missingRequired = [];
        foreach ($requiredFiles as $name => $filename) {
            $path = "$dir/$filename";
            if (! file_exists($path)) {
                $missingRequired[] = "$filename ($name)";
            }
        }

        if (! empty($missingRequired)) {
            $this->error('Missing required CSV files:');
            foreach ($missingRequired as $file) {
                $this->error("  - $file");
            }

            return;
        }

        $this->info('All required CSV files found. Dispatching import jobs...');

        $this->call('import:companies', ['file' => "$dir/od_firme.csv"]);
        $this->call('import:status', ['file' => "$dir/od_stare_firma.csv"]);
        $this->call('import:legal-representatives', ['file' => "$dir/od_reprezentanti_legali.csv"]);
        $this->call('import:natural-persons', ['file' => "$dir/od_reprezentanti_if.csv"]);
        $this->call('import:eu-branches', ['file' => "$dir/od_sucursale_alte_state_membre.csv"]);

        // Import optional CAEN files if available
        $caenFilesFound = 0;
        foreach ($optionalFiles as $name => $filename) {
            $path = "$dir/$filename";
            if (file_exists($path)) {
                $this->call("import:$name", ['file' => $path]);
                $caenFilesFound++;
            }
        }

        if ($caenFilesFound === 0) {
            $this->output->warning('CAEN files not found. To import CAEN classifications, download:');
            $this->output->warning('  - n_caen.csv (CAEN definitions)');
            $this->output->warning('  - n_caen_versiune.csv (CAEN versions)');
            $this->output->warning('  - od_caen_autorizat.csv (Company CAEN links)');
            $this->output->warning('Then run: php artisan import:caen-definition <file>');
        }

        $this->output->success('All import jobs dispatched successfully!');
        $this->output->note('Monitor progress on the Horizon dashboard at: /horizon');
    }
}
