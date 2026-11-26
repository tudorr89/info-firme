<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Writes the sitemap files that `SitemapController` serves.
 *
 * Layout in storage/app/sitemaps:
 *   sitemap.xml          – the index (pages + one entry per company chunk)
 *   sitemap-pages.xml    – the public static pages
 *   sitemap-{n}.xml      – company pages, at most `--chunk` URLs each
 *
 * Only what `Company::presentable()` allows is listed, so obsolete companies
 * and banned CIFs never reach a search engine.
 */
#[Signature('sitemap:generate {--chunk=50000 : URLs per company sitemap file}')]
#[Description('Generate the XML sitemaps for the public pages')]
class GenerateSitemap extends Command
{
    /**
     * The sitemap protocol allows at most 50.000 URLs (and 50 MB) per file.
     */
    private const MAX_URLS_PER_FILE = 50000;

    public function handle(): int
    {
        $chunkSize = (int) $this->option('chunk');

        if ($chunkSize < 1) {
            $this->error('--chunk must be a positive number.');

            return self::FAILURE;
        }

        $chunkSize = min($chunkSize, self::MAX_URLS_PER_FILE);

        $directory = $this->directory();
        $this->ensureDirectory($directory);

        $this->writePages($directory);

        $totalCompanies = Company::presentable()
            ->whereNotNull('cui')
            ->whereNotIn('cui', ['', '0'])
            ->distinct()
            ->count('cui');
        $this->info("Writing {$totalCompanies} company URLs to sitemap chunks...");

        $chunks = $this->writeCompanyChunks($directory, $chunkSize);

        $this->writeIndex($directory, $chunks);
        $this->removeStaleFiles($directory, $chunks);

        $this->info("Sitemap generation complete: 1 page sitemap + {$chunks} company sitemap(s).");

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{loc: string, priority: string}>
     */
    private function pages(): array
    {
        return [
            ['loc' => route('company.search'), 'priority' => '1.0'],
            ['loc' => route('legal.terms'), 'priority' => '0.3'],
            ['loc' => route('legal.privacy'), 'priority' => '0.3'],
        ];
    }

    private function writePages(string $directory): void
    {
        $xml = $this->openUrlset();

        foreach ($this->pages() as $page) {
            $xml .= $this->urlEntry($page['loc'], now()->format('Y-m-d'), $page['priority']);
        }

        $xml .= '</urlset>';

        $this->writeGzipped($directory.'/sitemap-pages.xml.gz', $xml);

        $this->info('Generated sitemap-pages.xml.gz ('.count($this->pages()).' URLs)');
    }

    /**
     * One URL per CUI, streamed with keyset pagination over the `cui` index.
     *
     * The company route is `/companie/{cui}`, i.e. the CUI *is* the URL key, so
     * walking rows emits the same <loc> again whenever several rows share a CUI
     * (116 954 CUIs in the current data). 84 914 rows also carry cui = "0", all
     * of them collapsing onto /companie/0. GROUP BY + MAX() fixes both: one URL
     * per real CUI, and a per-company lastmod instead of one global date.
     * Ordering/keyset on the indexed `cui` column keeps this to a single index
     * walk, where an OFFSET scan would walk every skipped row.
     */
    private function writeCompanyChunks(string $directory, int $chunkSize): int
    {
        $chunks = 0;
        $lastCui = null;

        while (true) {
            $companies = Company::presentable()
                ->selectRaw('cui, MAX(updated_at) as updated_at')
                ->whereNotNull('cui')
                // The company route only accepts digits: "0" and "" are import
                // artefacts, not companies, and both used to reach the sitemap.
                ->whereNotIn('cui', ['', '0'])
                ->when($lastCui !== null, fn ($query) => $query->where('cui', '>', $lastCui))
                ->groupBy('cui')
                ->orderBy('cui')
                ->limit($chunkSize)
                ->get();

            if ($companies->isEmpty()) {
                break;
            }

            $xml = $this->openUrlset();
            $written = 0;

            foreach ($companies as $company) {
                $cui = (string) $company->cui;

                if (preg_match('/^[0-9]+$/', $cui) !== 1) {
                    continue;
                }

                $xml .= $this->urlEntry(
                    route('company.show', $cui),
                    // MAX(updated_at) arrives as a raw datetime string.
                    $company->updated_at
                        ? substr((string) $company->updated_at, 0, 10)
                        : now()->format('Y-m-d'),
                    '0.8',
                );

                $written++;
            }

            $xml .= '</urlset>';

            if ($written > 0) {
                $chunks++;

                $this->writeGzipped($directory."/sitemap-{$chunks}.xml.gz", $xml);

                $this->info("Generated sitemap-{$chunks}.xml.gz ({$written} URLs)");
            }

            $lastCui = (string) $companies->last()->cui;

            if ($companies->count() < $chunkSize) {
                break;
            }
        }

        return $chunks;
    }

    private function writeIndex(string $directory, int $chunks): void
    {
        $lastmod = now()->format('Y-m-d');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        $xml .= $this->indexEntry(route('sitemap.pages'), $lastmod);

        for ($chunk = 1; $chunk <= $chunks; $chunk++) {
            $xml .= $this->indexEntry(route('sitemap.chunk', $chunk), $lastmod);
        }

        $xml .= '</sitemapindex>';

        $this->writeAtomically($directory.'/sitemap.xml', $xml);

        $this->info('Generated sitemap.xml ('.($chunks + 1).' sitemaps)');
    }

    /**
     * Remove files a previous run left behind: plain `.xml` chunks (superseded
     * by the gzipped ones, and they are what made the directory 529 MB) and
     * chunks with a number higher than this run produced.
     */
    private function removeStaleFiles(string $directory, int $chunks): void
    {
        foreach (glob($directory.'/sitemap-*.xml') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        foreach (glob($directory.'/sitemap-*.xml.gz') ?: [] as $file) {
            $name = basename($file, '.xml.gz');

            if ($name === 'sitemap-pages') {
                continue;
            }

            $number = (int) str_replace('sitemap-', '', $name);

            if (($number < 1 || $number > $chunks) && is_file($file)) {
                unlink($file);
            }
        }
    }

    private function openUrlset(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
    }

    private function urlEntry(string $loc, string $lastmod, string $priority): string
    {
        return "  <url>\n"
            .'    <loc>'.htmlspecialchars($loc, ENT_XML1)."</loc>\n"
            ."    <lastmod>{$lastmod}</lastmod>\n"
            ."    <priority>{$priority}</priority>\n"
            ."  </url>\n";
    }

    private function indexEntry(string $loc, string $lastmod): string
    {
        return "  <sitemap>\n"
            .'    <loc>'.htmlspecialchars($loc, ENT_XML1)."</loc>\n"
            ."    <lastmod>{$lastmod}</lastmod>\n"
            ."  </sitemap>\n";
    }

    /**
     * Gzipped sitemaps are ~10x smaller on disk and over the wire, and Google
     * accepts them as long as the file name ends in `.gz`.
     */
    private function writeGzipped(string $path, string $contents): void
    {
        $compressed = gzencode($contents, 9);

        if ($compressed === false) {
            throw new RuntimeException("Could not gzip {$path}");
        }

        $this->writeAtomically($path, $compressed);
    }

    /**
     * Write to a temporary file first: a crawler must never read a file that is
     * still being written.
     */
    private function writeAtomically(string $path, string $contents): void
    {
        $temporary = $path.'.tmp';

        if (file_put_contents($temporary, $contents) === false) {
            throw new RuntimeException("Could not write {$temporary}");
        }

        // A failed rename would leave the old file in place while the caller
        // believes the new one was written.
        if (! rename($temporary, $path)) {
            unlink($temporary);

            throw new RuntimeException("Could not replace {$path}");
        }
    }

    private function directory(): string
    {
        return storage_path('app/sitemaps');
    }

    private function ensureDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }
}
