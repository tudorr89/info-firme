<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate {--chunk=50000}';

    protected $description = 'Generate XML sitemap for all companies';

    public function handle(): int
    {
        $chunkSize = (int) $this->option('chunk');
        $totalCompanies = Company::count();
        $totalChunks = ceil($totalCompanies / $chunkSize);

        $this->info("Generating sitemap for {$totalCompanies} companies in {$totalChunks} chunks...");

        // Generate individual sitemaps
        for ($chunk = 0; $chunk < $totalChunks; $chunk++) {
            $offset = $chunk * $chunkSize;
            $this->generateChunk($chunk + 1, $offset, $chunkSize);
        }

        // Generate index file
        $this->generateIndex($totalChunks);

        $this->info('Sitemap generation complete!');

        return Command::SUCCESS;
    }

    private function generateChunk(int $chunkNumber, int $offset, int $chunkSize): void
    {
        $companies = Company::orderBy('id')
            ->offset($offset)
            ->limit($chunkSize)
            ->select('cui', 'updated_at')
            ->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($companies as $company) {
            $url = route('company.show', $company->cui);
            $lastmod = $company->updated_at->format('Y-m-d');

            $xml .= "  <url>\n";
            $xml .= '    <loc>'.htmlspecialchars($url)."</loc>\n";
            $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
            $xml .= "    <changefreq>monthly</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        $sitemapDir = storage_path('app/sitemaps');
        if (! is_dir($sitemapDir)) {
            mkdir($sitemapDir, 0755, true);
        }

        $filename = $sitemapDir."/sitemap-{$chunkNumber}.xml";
        file_put_contents($filename, $xml);

        $this->info("Generated sitemap-{$chunkNumber}.xml ({$companies->count()} URLs)");
    }

    private function generateIndex(int $totalChunks): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        for ($i = 1; $i <= $totalChunks; $i++) {
            $url = url("api/sitemap-{$i}.xml");
            $xml .= "  <sitemap>\n";
            $xml .= '    <loc>'.htmlspecialchars($url)."</loc>\n";
            $xml .= "  </sitemap>\n";
        }

        $xml .= '</sitemapindex>';

        $sitemapDir = storage_path('app/sitemaps');
        if (! is_dir($sitemapDir)) {
            mkdir($sitemapDir, 0755, true);
        }

        $filename = $sitemapDir.'/sitemap.xml';
        file_put_contents($filename, $xml);

        $this->info('Generated sitemap index (sitemap.xml)');
    }
}
