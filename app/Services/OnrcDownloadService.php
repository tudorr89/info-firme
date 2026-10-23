<?php

namespace App\Services;

use App\Jobs\OnrcDownloadJob;
use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Discovers and downloads the ONRC open data snapshots published on
 * data.gov.ro (CKAN API).
 *
 * ONRC file URLs are large (hundreds of MB) and the host can be slow, so
 * downloads are streamed straight to disk instead of being buffered in
 * memory. Anything longer than a few seconds should be dispatched through
 * {@see OnrcDownloadJob} which adds long timeouts and retries.
 */
class OnrcDownloadService
{
    /**
     * Canonical target filenames keyed by the lowercase basename of the
     * resource download URL.
     *
     * @var array<string, string>
     */
    private const FILE_MAP = [
        'od_firme.csv' => 'od_firme.csv',
        'od_stare_firma.csv' => 'od_stare_firma.csv',
        'od_reprezentanti_legali.csv' => 'od_reprezentanti_legali.csv',
        'od_reprezentanti_if.csv' => 'od_reprezentanti_if.csv',
        'od_sucursale_alte_state_membre.csv' => 'od_sucursale_alte_state_membre.csv',
        'od_caen_autorizat.csv' => 'od_caen_autorizat.csv',
        'n_caen.csv' => 'n_caen.csv',
        'n_versiune_caen.csv' => 'n_versiune_caen.csv',
        'n_stare_firma.csv' => 'n_stare_firma.csv',
    ];

    /**
     * Dataset families published by ONRC, newest snapshot wins.
     *
     * @var array<int, string>
     */
    private const PACKAGE_TYPES = ['firme', 'nomenclatoare'];

    /**
     * Discover the downloadable CSV resources of the latest snapshot of every
     * ONRC dataset family.
     *
     * @return array<int, array{url: string, target: string, package: string}>
     */
    public function latestResources(): array
    {
        $packages = $this->packageSearch();
        $resources = [];

        foreach (self::PACKAGE_TYPES as $type) {
            $package = $this->latestPackage($packages, $type);

            if ($package === null) {
                Log::warning("No ONRC package found for type: {$type}");

                continue;
            }

            foreach ($this->packageResources($package['name']) as $resource) {
                $resources[] = $resource;
            }
        }

        return $resources;
    }

    /**
     * Download every CSV resource of the latest snapshots, synchronously.
     *
     * @return array<int, string>
     */
    public function downloadLatest(): array
    {
        $downloaded = [];

        foreach ($this->latestResources() as $resource) {
            $this->download($resource['url'], $resource['target']);
            $downloaded[] = $resource['target'];
        }

        return $downloaded;
    }

    /**
     * Stream a single CSV resource to the import directory.
     *
     * The file is written to a temporary `.part` file first so an interrupted
     * download never leaves a truncated CSV behind.
     */
    public function download(string $url, string $target): void
    {
        $disk = Storage::disk(config('import.disk'));
        $directory = (string) config('import.directory');
        $partialPath = $disk->path("{$directory}/{$target}.part");
        $finalPath = $disk->path("{$directory}/{$target}");

        if (! is_dir(dirname($partialPath))) {
            mkdir(dirname($partialPath), 0755, true);
        }

        $this->downloadRequest()
            ->sink($partialPath)
            ->get($url)
            ->throw();

        if (! rename($partialPath, $finalPath)) {
            throw new RuntimeException("Unable to move downloaded file into place: {$finalPath}");
        }
    }

    /**
     * @return array<int, array{name: string, title: string, metadata_modified: ?string}>
     */
    public function packageSearch(): array
    {
        $response = $this->metadataRequest()->get('package_search', [
            'q' => 'organization:'.config('services.onrc.organization'),
            'rows' => 100,
        ])->throw();

        $data = $response->json();

        if (($data['success'] ?? false) !== true) {
            throw new RuntimeException('ONRC package search failed.');
        }

        return $data['result']['results'] ?? [];
    }

    /**
     * @param  array<int, array{name: string, title: string, metadata_modified: ?string}>  $packages
     * @return array{name: string, title: string, metadata_modified: ?string}|null
     */
    public function latestPackage(array $packages, string $type): ?array
    {
        $matching = array_values(array_filter(
            $packages,
            fn (array $package) => $this->isPackageOfType($package['name'], $type)
        ));

        if ($matching === []) {
            return null;
        }

        usort($matching, function (array $first, array $second) {
            return $this->extractDate($second)->getTimestamp() <=> $this->extractDate($first)->getTimestamp();
        });

        return $matching[0];
    }

    /**
     * @return array<int, array{url: string, target: string, package: string}>
     */
    public function packageResources(string $packageName): array
    {
        $response = $this->metadataRequest()->get('package_show', ['id' => $packageName])->throw();

        $resources = $response->json()['result']['resources'] ?? [];
        $found = [];

        foreach ($resources as $resource) {
            if (strtoupper((string) ($resource['format'] ?? '')) !== 'CSV') {
                continue;
            }

            $url = $resource['url'] ?? $resource['datagovro_download_url'] ?? null;

            if (! is_string($url) || $url === '') {
                continue;
            }

            $target = $this->targetFilename($url);

            if ($target === null) {
                continue;
            }

            $found[$target] = [
                'url' => $url,
                'target' => $target,
                'package' => $packageName,
            ];
        }

        return array_values($found);
    }

    public function targetFilename(string $url): ?string
    {
        $basename = strtolower(basename((string) parse_url($url, PHP_URL_PATH)));

        return self::FILE_MAP[$basename] ?? null;
    }

    /**
     * Metadata requests are small and fast.
     */
    private function metadataRequest(): PendingRequest
    {
        return Http::baseUrl((string) config('services.onrc.api_base_url'))
            ->timeout((int) config('services.onrc.timeout', 30))
            ->connectTimeout(5)
            ->retry(3, 1000);
    }

    /**
     * File downloads are large and the host is slow, so the read timeout is
     * generous and retries back off over several minutes.
     */
    private function downloadRequest(): PendingRequest
    {
        return Http::timeout((int) config('services.onrc.download_timeout', 1800))
            ->connectTimeout(15)
            ->retry(
                (int) config('services.onrc.download_retries', 3),
                fn (int $attempt) => min($attempt * 15000, 120000),
                throw: false,
            );
    }

    private function isPackageOfType(string $name, string $type): bool
    {
        return Str::startsWith(Str::lower($name), $type);
    }

    /**
     * @param  array{name: string, metadata_modified: ?string}  $package
     */
    private function extractDate(array $package): Carbon
    {
        $name = Str::lower($package['name']);

        if (preg_match('/(\d{1,2})-(\d{1,2})-(\d{4})$/', $name, $matches)) {
            return Carbon::createFromFormat('d-m-Y', $matches[0]);
        }

        $modified = $package['metadata_modified'] ?? null;

        return $modified ? Carbon::parse($modified) : Carbon::createFromTimestamp(0);
    }
}
