<?php

namespace Tests\Feature;

use App\Enums\CifRemovalStatus;
use App\Models\CifRemovalRequest;
use App\Models\Company;
use App\Models\Status;
use App\Services\BannedCifService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The sitemap is generated to disk and served by SitemapController. Only what
 * `Company::presentable()` allows may be listed: an obsolete company or a
 * banned CIF must never reach a search engine.
 */
class SitemapTest extends TestCase
{
    use RefreshDatabase;

    private const DIRECTORY = 'app/sitemaps';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'companies.active_statuses' => [1048],
            'companies.obsolete_statuses' => [1049],
            'companies.hide_obsolete' => true,
            'skip.always' => [],
        ]);

        BannedCifService::flush();
        $this->wipe();
    }

    protected function tearDown(): void
    {
        $this->wipe();

        parent::tearDown();
    }

    public function test_it_writes_an_index_a_pages_sitemap_and_one_file_per_chunk(): void
    {
        $this->company('ALFA S.R.L.', '11111111', 'J40/1/2024');
        $this->company('BETA S.R.L.', '22222222', 'J40/2/2024');
        $this->company('GAMA S.R.L.', '33333333', 'J40/3/2024');

        $this->artisan('sitemap:generate', ['--chunk' => 2])->assertSuccessful();

        $this->assertFileExists($this->path('sitemap.xml'));
        $this->assertFileExists($this->path('sitemap-pages.xml'));
        $this->assertFileExists($this->path('sitemap-1.xml'));
        $this->assertFileExists($this->path('sitemap-2.xml'));

        $index = $this->contents('sitemap.xml');
        $this->assertStringContainsString('<sitemapindex', $index);
        $this->assertStringContainsString('/sitemap-pages.xml', $index);
        $this->assertStringContainsString('/sitemap-1.xml', $index);
        $this->assertStringContainsString('/sitemap-2.xml', $index);
        $this->assertStringNotContainsString('/sitemap-3.xml', $index);
    }

    public function test_the_pages_sitemap_lists_the_public_pages_only(): void
    {
        $this->artisan('sitemap:generate', ['--chunk' => 10])->assertSuccessful();

        $pages = $this->contents('sitemap-pages.xml');

        $this->assertStringContainsString(route('company.search'), $pages);
        $this->assertStringContainsString(route('legal.terms'), $pages);
        $this->assertStringContainsString(route('legal.privacy'), $pages);

        // The removal form is noindex, so it must not be advertised.
        $this->assertStringNotContainsString('solicitare-stergere', $pages);
    }

    public function test_a_company_url_carries_a_lastmod_and_every_company_is_listed_once(): void
    {
        $this->company('ALFA S.R.L.', '11111111', 'J40/1/2024');

        $this->artisan('sitemap:generate', ['--chunk' => 50])->assertSuccessful();

        $chunk = $this->contents('sitemap-1.xml');

        $this->assertStringContainsString(route('company.show', '11111111'), $chunk);
        $this->assertSame(1, substr_count($chunk, '<url>'));
        $this->assertStringContainsString('<lastmod>', $chunk);
    }

    public function test_obsolete_companies_and_banned_cifs_are_left_out(): void
    {
        $this->company('ACTIVA S.R.L.', '11111111', 'J40/1/2024', 1048);
        $this->company('DIZOLVATA S.R.L.', '22222222', 'J40/2/2024', 1049);

        CifRemovalRequest::create([
            'cui' => '33333333',
            'requester_name' => 'Ion Popescu',
            'requester_email' => 'ion@example.ro',
            'reason' => 'Solicit eliminarea datelor firmei mele din aplicatie.',
            'status' => CifRemovalStatus::Approved,
        ]);
        $this->company('INTERZISA S.R.L.', '33333333', 'J40/3/2024', 1048);

        $this->artisan('sitemap:generate', ['--chunk' => 50])->assertSuccessful();

        $chunk = $this->contents('sitemap-1.xml');

        $this->assertStringContainsString(route('company.show', '11111111'), $chunk);
        $this->assertStringNotContainsString(route('company.show', '22222222'), $chunk);
        $this->assertStringNotContainsString(route('company.show', '33333333'), $chunk);
    }

    public function test_a_company_without_a_numeric_cui_is_skipped(): void
    {
        Company::create([
            'name' => 'FARA CUI S.R.L.',
            'cui' => '',
            'reg_com' => 'J40/9/2024',
            'euid' => 'ROONRC.J40/9/2024',
            'type' => 'SRL',
            'registration_date' => '2024-01-01',
        ]);

        $this->artisan('sitemap:generate', ['--chunk' => 50])->assertSuccessful();

        // No chunk file at all: the only row has no usable CUI.
        $this->assertFileDoesNotExist($this->path('sitemap-1.xml'));
        $this->assertFileExists($this->path('sitemap.xml'));
    }

    public function test_a_second_run_removes_chunks_it_no_longer_needs(): void
    {
        $this->company('ALFA S.R.L.', '11111111', 'J40/1/2024');
        $this->company('BETA S.R.L.', '22222222', 'J40/2/2024');

        $this->artisan('sitemap:generate', ['--chunk' => 1])->assertSuccessful();
        $this->assertFileExists($this->path('sitemap-2.xml'));

        Company::where('cui', '22222222')->delete();

        $this->artisan('sitemap:generate', ['--chunk' => 1])->assertSuccessful();

        $this->assertFileExists($this->path('sitemap-1.xml'));
        $this->assertFileDoesNotExist($this->path('sitemap-2.xml'));
        $this->assertStringNotContainsString('/sitemap-2.xml', $this->contents('sitemap.xml'));
    }

    public function test_it_rejects_a_chunk_size_below_one(): void
    {
        $this->artisan('sitemap:generate', ['--chunk' => 0])->assertFailed();
    }

    public function test_the_routes_serve_the_generated_files(): void
    {
        $this->company('ALFA S.R.L.', '11111111', 'J40/1/2024');
        $this->artisan('sitemap:generate', ['--chunk' => 50])->assertSuccessful();

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $this->get('/sitemap-pages.xml')->assertOk();
        $this->get('/sitemap-1.xml')->assertOk();
    }

    public function test_a_missing_sitemap_returns_404(): void
    {
        $this->get('/sitemap.xml')->assertNotFound();
        $this->get('/sitemap-1.xml')->assertNotFound();
    }

    public function test_robots_txt_points_at_the_sitemap(): void
    {
        $robots = (string) file_get_contents(public_path('robots.txt'));

        // robots.txt is a static file, so the host is baked in; only the path
        // matters here.
        $this->assertMatchesRegularExpression('#^Sitemap: https?://[^/]+/sitemap\.xml$#m', $robots);
    }

    private function path(string $name): string
    {
        return storage_path(self::DIRECTORY.'/'.$name);
    }

    private function contents(string $name): string
    {
        return (string) file_get_contents($this->path($name));
    }

    private function wipe(): void
    {
        $directory = storage_path(self::DIRECTORY);

        foreach (glob($directory.'/*') ?: [] as $file) {
            @unlink($file);
        }

        if (is_dir($directory)) {
            @rmdir($directory);
        }
    }

    private function company(string $name, string $cui, string $regCom, int $status = 1048): Company
    {
        $company = Company::create([
            'name' => $name,
            'cui' => $cui,
            'reg_com' => $regCom,
            'euid' => 'ROONRC.'.$regCom,
            'type' => 'SRL',
            'registration_date' => '2024-01-01',
        ]);

        Status::create(['registration' => $regCom, 'status' => $status]);

        return $company;
    }
}
