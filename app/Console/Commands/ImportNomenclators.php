<?php

namespace App\Console\Commands;

use App\Models\Nomenclator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportNomenclators extends Command
{
    protected $signature = 'app:import-nomenclators';

    protected $description = 'Import nomenclators from CSV files with proper UTF-8 encoding';

    public function handle(): int
    {
        $files = [
            storage_path('app/imports/n_stare_firma.csv') => 'description',
        ];

        foreach ($files as $file => $descriptionField) {
            if (!file_exists($file)) {
                $this->error("File not found: $file");
                continue;
            }

            $this->importNomenclators($file);
        }

        return 0;
    }

    private function importNomenclators(string $file): void
    {
        $this->info("Importing from: $file");

        $handle = fopen($file, 'r');
        if (!$handle) {
            $this->error("Cannot open file: $file");
            return;
        }

        // Skip BOM if present
        if (fgets($handle, 4) !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = null;
        $count = 0;
        $errors = 0;

        DB::transaction(function () use ($handle, &$header, &$count, &$errors) {
            while (($line = fgetcsv($handle, 0, '^')) !== false) {
                if ($header === null) {
                    $header = $line;
                    continue;
                }

                $code = trim($line[0] ?? '');
                $description = trim($line[1] ?? '');

                if (!$code || !$description) {
                    $errors++;
                    continue;
                }

                try {
                    Nomenclator::updateOrCreate(
                        ['code' => (int)$code],
                        ['description' => $description]
                    );
                    $count++;
                } catch (\Exception $e) {
                    $this->error("Error importing code $code: " . $e->getMessage());
                    $errors++;
                }
            }
        });

        fclose($handle);

        $this->info("Imported $count nomenclators");
        if ($errors > 0) {
            $this->warn("Encountered $errors errors during import");
        }
    }
}
