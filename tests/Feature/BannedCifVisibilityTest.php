<?php

namespace Tests\Feature;

use App\Livewire\CompanySearch;
use App\Models\Company;
use App\Models\Status;
use Tests\TestCase;
use Tests\TruncatesCommittedDatabase;

/**
 * Banned CIFs are skipped at import time, but a row imported before the ban was
 * enforced (or restored from a production dump) still exists in the database.
 * Nothing may serve it: it must stay out of search, the API, the company page
 * and the sitemap, no matter how it got there.
 */
class BannedCifVisibilityTest extends TestCase
{
    use TruncatesCommittedDatabase;

    private const BANNED_CUI = '52491237';

    private const BANNED_REG_COM = 'F2025033927003';

    private const ALLOWED_CUI = '12345678';

    private const ALLOWED_REG_COM = 'J40/1234/2024';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'companies.active_statuses' => [1048],
            'companies.obsolete_statuses' => [1049],
            'companies.hide_obsolete' => true,

            'skip.always' => [self::BANNED_CUI],
        ]);
    }

    public function test_the_banned_company_is_not_returned_by_search(): void
    {
        $this->company('SAVU CRINA-CECILIA PERSOANĂ FIZICĂ AUTORIZATĂ', self::BANNED_CUI, self::BANNED_REG_COM);
        $this->company('SAVU CONSTRUCT S.R.L.', self::ALLOWED_CUI, self::ALLOWED_REG_COM);

        $component = new CompanySearch;
        $component->search = 'savu';

        $names = $component->topResults()->pluck('name')->all();

        $this->assertSame(['SAVU CONSTRUCT S.R.L.'], $names);
    }

    public function test_the_banned_company_is_not_returned_by_cui_search(): void
    {
        $this->company('SAVU CRINA-CECILIA PERSOANĂ FIZICĂ AUTORIZATĂ', self::BANNED_CUI, self::BANNED_REG_COM);

        $component = new CompanySearch;
        $component->search = self::BANNED_CUI;

        $this->assertCount(0, $component->topResults());
    }

    public function test_the_banned_company_page_returns_404(): void
    {
        $this->company('SAVU CRINA-CECILIA PERSOANĂ FIZICĂ AUTORIZATĂ', self::BANNED_CUI, self::BANNED_REG_COM);

        $this->get(route('company.show', self::BANNED_CUI))->assertNotFound();
    }

    public function test_the_allowed_company_page_still_works(): void
    {
        $this->company('SAVU CONSTRUCT S.R.L.', self::ALLOWED_CUI, self::ALLOWED_REG_COM);

        $this->get(route('company.show', self::ALLOWED_CUI))->assertOk();
    }

    public function test_the_banned_company_is_not_returned_by_the_api(): void
    {
        $this->company('SAVU CRINA-CECILIA PERSOANĂ FIZICĂ AUTORIZATĂ', self::BANNED_CUI, self::BANNED_REG_COM);

        $this->getJson('/api/v1/info?cui='.self::BANNED_CUI)->assertNotFound();
    }

    public function test_the_banned_company_is_excluded_from_the_presentable_scope(): void
    {
        $this->company('SAVU CRINA-CECILIA PERSOANĂ FIZICĂ AUTORIZATĂ', self::BANNED_CUI, self::BANNED_REG_COM);
        $this->company('SAVU CONSTRUCT S.R.L.', self::ALLOWED_CUI, self::ALLOWED_REG_COM);

        $this->assertSame(1, Company::presentable()->count());
        $this->assertSame(2, Company::count());
    }

    public function test_the_ban_cannot_be_disabled_by_hiding_obsolete_companies_being_off(): void
    {
        config(['companies.hide_obsolete' => false]);

        $this->company('SAVU CRINA-CECILIA PERSOANĂ FIZICĂ AUTORIZATĂ', self::BANNED_CUI, self::BANNED_REG_COM);
        $this->company('SAVU CONSTRUCT S.R.L.', self::ALLOWED_CUI, self::ALLOWED_REG_COM);

        $this->assertSame(1, Company::presentable()->count());
    }

    public function test_company_delete_removes_every_row_of_a_reused_cif(): void
    {
        $this->company('OLD BANNED S.R.L.', self::BANNED_CUI, 'J40/1/1995');
        $this->company('NEW BANNED S.R.L.', self::BANNED_CUI, 'J40/2/2024');

        $this->artisan('company:delete', ['cif' => self::BANNED_CUI, '--force' => true])
            ->assertSuccessful();

        $this->assertDatabaseMissing('companies', ['cui' => self::BANNED_CUI]);
        $this->assertDatabaseMissing('statuses', ['registration' => 'J40/1/1995']);
        $this->assertDatabaseMissing('statuses', ['registration' => 'J40/2/2024']);
    }

    private function company(string $name, string $cui, string $regCom): Company
    {
        $company = Company::create([
            'name' => $name,
            'cui' => $cui,
            'reg_com' => $regCom,
            'euid' => 'ROONRC.'.$regCom,
            'type' => 'SRL',
            'registration_date' => '2025-05-26',
        ]);

        Status::create(['registration' => $regCom, 'status' => 1048]);

        return $company;
    }
}
