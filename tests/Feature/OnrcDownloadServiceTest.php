<?php

namespace Tests\Feature;

use App\Jobs\OnrcDownloadJob;
use App\Services\OnrcDownloadService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class OnrcDownloadServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config([
            'services.onrc.organization' => 'onrc',
            'services.onrc.api_base_url' => 'https://data.gov.ro/api/3/action',
        ]);
    }

    public function test_it_downloads_only_the_latest_snapshot_of_each_type(): void
    {
        $this->fakeOnrcApi();

        $service = new OnrcDownloadService;
        $downloaded = $service->downloadLatest();

        sort($downloaded);

        $this->assertSame([
            'n_caen.csv',
            'n_stare_firma.csv',
            'n_versiune_caen.csv',
            'od_caen_autorizat.csv',
            'od_firme.csv',
            'od_reprezentanti_legali.csv',
            'od_stare_firma.csv',
        ], $downloaded);

        Storage::disk('local')->assertExists('imports/od_firme.csv');
        Storage::disk('local')->assertExists('imports/n_caen.csv');

        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'firme-01-01-2020'));
    }

    public function test_it_prefers_the_package_with_the_newest_date_in_the_name(): void
    {
        Http::fake([
            '*/package_search*' => Http::response([
                'success' => true,
                'result' => [
                    'results' => [
                        ['name' => 'firme-09-01-2026', 'title' => 'old', 'metadata_modified' => '2026-04-01T00:00:00'],
                        ['name' => 'firme-19-10-2026', 'title' => 'new', 'metadata_modified' => '2026-01-01T00:00:00'],
                    ],
                ],
            ]),
            '*/package_show*' => Http::response([
                'success' => true,
                'result' => ['resources' => []],
            ]),
        ]);

        $service = new OnrcDownloadService;

        $latest = $service->latestPackage($service->packageSearch(), 'firme');

        $this->assertSame('firme-19-10-2026', $latest['name']);
    }

    public function test_it_falls_back_to_metadata_modified_when_the_name_has_no_date(): void
    {
        Http::fake([
            '*/package_search*' => Http::response([
                'success' => true,
                'result' => [
                    'results' => [
                        ['name' => 'firme-old', 'title' => 'old', 'metadata_modified' => '2020-01-01T00:00:00'],
                        ['name' => 'firme-new', 'title' => 'new', 'metadata_modified' => '2026-01-01T00:00:00'],
                    ],
                ],
            ]),
        ]);

        $service = new OnrcDownloadService;

        $latest = $service->latestPackage($service->packageSearch(), 'firme');

        $this->assertSame('firme-new', $latest['name']);
    }

    public function test_download_leaves_no_partial_file_behind(): void
    {
        config([
            'import.disk' => 'local',
            'import.directory' => 'imports',
        ]);

        Http::fake(['*' => Http::response('data')]);

        (new OnrcDownloadService)->download('https://data.gov.ro/download/od_firme.csv', 'od_firme.csv');

        Storage::disk('local')->assertExists('imports/od_firme.csv');
        Storage::disk('local')->assertMissing('imports/od_firme.csv.part');
    }

    public function test_command_queues_a_download_job_per_resource(): void
    {
        Queue::fake();
        $this->fakeOnrcApi();

        $this->artisan('onrc:download')->assertSuccessful();

        Queue::assertPushed(OnrcDownloadJob::class, 7);
        Queue::assertPushed(
            OnrcDownloadJob::class,
            fn (OnrcDownloadJob $job) => $job->target === 'od_firme.csv'
                && $job->url === 'https://data.gov.ro/download/od_firme.csv'
        );
    }

    public function test_command_skips_files_that_already_exist_unless_forced(): void
    {
        Queue::fake();
        $this->fakeOnrcApi();
        Storage::disk('local')->put('imports/od_firme.csv', 'existing');

        $this->artisan('onrc:download')->assertSuccessful();

        Queue::assertPushed(OnrcDownloadJob::class, 6);
        Queue::assertNotPushed(
            OnrcDownloadJob::class,
            fn (OnrcDownloadJob $job) => $job->target === 'od_firme.csv'
        );

        $this->artisan('onrc:download', ['--force' => true])->assertSuccessful();

        Queue::assertPushed(
            OnrcDownloadJob::class,
            fn (OnrcDownloadJob $job) => $job->target === 'od_firme.csv'
        );
    }

    public function test_download_job_failure_removes_the_partial_file(): void
    {
        config([
            'import.disk' => 'local',
            'import.directory' => 'imports',
        ]);

        Storage::disk('local')->put('imports/od_firme.csv.part', 'partial');

        (new OnrcDownloadJob('https://data.gov.ro/download/od_firme.csv', 'od_firme.csv'))
            ->failed(new RuntimeException('timeout'));

        Storage::disk('local')->assertMissing('imports/od_firme.csv.part');
    }

    private function fakeOnrcApi(): void
    {
        config([
            'import.disk' => 'local',
            'import.directory' => 'imports',
        ]);

        Http::fake([
            '*/package_search*' => Http::response([
                'success' => true,
                'result' => [
                    'results' => [
                        ['name' => 'firme-01-01-2020', 'title' => 'old firme', 'metadata_modified' => '2020-01-01T00:00:00'],
                        ['name' => 'firme-19-10-2026', 'title' => 'new firme', 'metadata_modified' => '2026-10-19T00:00:00'],
                        ['name' => 'nomenclatoare-01-01-2020', 'title' => 'old nomenclatoare', 'metadata_modified' => '2020-01-01T00:00:00'],
                        ['name' => 'nomenclatoare-19-10-2026', 'title' => 'new nomenclatoare', 'metadata_modified' => '2026-10-19T00:00:00'],
                        ['name' => 'buget-registrul-comertului-2013', 'title' => 'budget', 'metadata_modified' => '2013-01-01T00:00:00'],
                    ],
                ],
            ]),
            '*/package_show*id=firme-19-10-2026*' => Http::response([
                'success' => true,
                'result' => [
                    'resources' => [
                        ['format' => 'CSV', 'url' => 'https://data.gov.ro/download/od_firme.csv'],
                        ['format' => 'CSV', 'url' => 'https://data.gov.ro/download/od_stare_firma.csv'],
                        ['format' => 'CSV', 'url' => 'https://data.gov.ro/download/od_reprezentanti_legali.csv'],
                        ['format' => 'CSV', 'url' => 'https://data.gov.ro/download/od_caen_autorizat.csv'],
                        ['format' => 'PDF', 'url' => 'https://data.gov.ro/download/metodologie.pdf'],
                        ['format' => 'CSV', 'url' => 'https://data.gov.ro/download/unknown-file.csv'],
                    ],
                ],
            ]),
            '*/package_show*id=nomenclatoare-19-10-2026*' => Http::response([
                'success' => true,
                'result' => [
                    'resources' => [
                        ['format' => 'CSV', 'url' => 'https://data.gov.ro/download/n_caen.csv'],
                        ['format' => 'CSV', 'url' => 'https://data.gov.ro/download/n_versiune_caen.csv'],
                        ['format' => 'CSV', 'url' => 'https://data.gov.ro/download/n_stare_firma.csv'],
                    ],
                ],
            ]),
            '*/download/*' => Http::response("DENUMIRE^CUI\nFirma^123\n"),
        ]);
    }
}
