<?php

namespace Tests\Feature;

use App\Livewire\CompanySearch;
use App\Models\Address;
use App\Models\Caen;
use App\Models\CaenCompany;
use App\Models\Company;
use App\Models\LegalRepresentative;
use App\Models\Nomenclator;
use App\Models\Status;
use App\Services\CompanyStatusService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\TruncatesCommittedDatabase;

class CompanyPresentationTest extends TestCase
{
    use TruncatesCommittedDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'companies.active_statuses' => [1048],
            'companies.obsolete_statuses' => [1049, 1052, 1070],
            'companies.hide_obsolete' => true,
        ]);
    }

    public function test_search_shows_only_active_companies(): void
    {
        $active = $this->company('ACTIVE ENERGY SRL', '11111111', 'J40/1/2024', 1048);
        $obsolete = $this->company('OBSOLETE ENERGY SRL', '22222222', 'J40/2/2024', 1049);
        $unknown = $this->company('UNKNOWN ENERGY SRL', '33333333', 'J40/3/2024', null);

        $names = $this->searchResults('ENERGY')->pluck('name')->all();

        $this->assertContains('ACTIVE ENERGY SRL', $names);
        $this->assertContains('UNKNOWN ENERGY SRL', $names);
        $this->assertNotContains('OBSOLETE ENERGY SRL', $names);

        $this->assertTrue(CompanyStatusService::isActive($active->fresh()));
        $this->assertFalse(CompanyStatusService::isActive($obsolete->fresh()));
        $this->assertTrue(CompanyStatusService::isObsolete($obsolete->fresh()));
        $this->assertFalse(CompanyStatusService::isActive($unknown->fresh()));
    }

    public function test_search_returns_one_result_per_cif(): void
    {
        $this->company('REUSED NAME SRL', '44444444', 'J40/4/1995', 1049, '1995-01-01');
        $this->company('REUSED NAME SRL', '44444444', 'J40/5/2024', 1048, '2024-01-01');

        $results = $this->searchResults('REUSED');

        $this->assertCount(1, $results);
        $this->assertSame('J40/5/2024', $results->first()->reg_com);
    }

    public function test_obsolete_companies_can_be_included_when_hiding_is_disabled(): void
    {
        config(['companies.hide_obsolete' => false]);

        $this->company('OBSOLETE SRL', '22222222', 'J40/2/2024', 1049);

        $this->assertSame(1, Company::presentable()->count());
    }

    public function test_presentable_scope_excludes_obsolete_companies(): void
    {
        $this->company('ACTIVE SRL', '11111111', 'J40/1/2024', 1048);
        $this->company('DISSOLVED SRL', '22222222', 'J40/2/2024', 1049);
        $this->company('BANKRUPT SRL', '33333333', 'J40/3/2024', 1070);

        $this->assertSame(1, Company::presentable()->count());
        $this->assertSame(2, Company::withStatus(CompanyStatusService::obsoleteCodes())->count());
        $this->assertSame(1, Company::withStatus(CompanyStatusService::activeCodes())->count());
    }

    public function test_prefer_active_returns_the_newer_company_for_a_reused_cif(): void
    {
        $this->company('OLD COMPANY SRL', '55555555', 'J40/6/1995', 1049, '1995-01-01');
        $this->company('NEW COMPANY SRL', '55555555', 'J40/7/2024', 1048, '2024-01-01');

        $resolved = Company::query()
            ->where('cui', '55555555')
            ->preferActive()
            ->orderByDesc('registration_date')
            ->first();

        $this->assertNotNull($resolved);
        $this->assertSame('NEW COMPANY SRL', $resolved->name);
        $this->assertSame('J40/7/2024', $resolved->reg_com);
        $this->assertTrue((bool) $resolved->is_active);
    }

    public function test_prefer_active_falls_back_to_the_newest_company_when_none_is_active(): void
    {
        $this->company('OLD COMPANY SRL', '66666666', 'J40/8/1995', 1049, '1995-01-01');
        $this->company('RADIATED COMPANY SRL', '66666666', 'J40/9/2020', 1070, '2020-01-01');

        $resolved = Company::query()
            ->where('cui', '66666666')
            ->preferActive()
            ->orderByDesc('registration_date')
            ->first();

        $this->assertNotNull($resolved);
        $this->assertSame('RADIATED COMPANY SRL', $resolved->name);
        $this->assertFalse((bool) $resolved->is_active);
    }

    public function test_a_cached_company_page_issues_no_relation_queries(): void
    {
        $company = $this->company('CACHED COMPANY S.R.L.', '77777777', 'J40/77/2024', 1048);

        Address::create([
            'company_id' => $company->id,
            'country' => 'România',
            'city' => 'București Sectorul 3',
            'county' => 'București',
            'street' => 'BARBU LĂZĂREANU',
            'number' => '2',
        ]);

        Caen::create([
            'class' => 6210,
            'version' => 3,
            'section' => 'K',
            'division' => 62,
            'group' => 621,
            'name' => 'Activități de realizare a soft-ului la comandă',
        ]);

        CaenCompany::create(['registration' => 'J40/77/2024', 'code' => 6210, 'version' => 3]);

        LegalRepresentative::create([
            'company_id' => $company->id,
            'registration' => 'J40/77/2024',
            'person_name' => 'POPESCU ION',
            'role' => 'administrator',
        ]);

        Nomenclator::create(['code' => 1048, 'description' => 'funcțiune']);

        // The first request warms the cache with the company and its relations.
        $this->get(route('company.show', '77777777'))->assertOk();

        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        // A cache hit rebuilds the models from the cached arrays, so it must
        // not reload any relation — and the page must still be complete,
        // including the nested `status.details` relation.
        $this->get(route('company.show', '77777777'))
            ->assertOk()
            ->assertSee('CACHED COMPANY S.R.L.')
            ->assertSee('funcțiune')
            ->assertSee('BARBU LĂZĂREANU')
            ->assertSee('6210')
            ->assertSee('Activități de realizare a soft-ului la comandă')
            // The section only renders when the restored representatives
            // relation is a non-empty collection.
            ->assertSee('Reprezentanti');

        $this->assertSame([], $queries, implode(PHP_EOL, $queries));
    }

    /**
     * @return Collection<int, Company>
     */
    private function searchResults(string $term): Collection
    {
        $component = new CompanySearch;
        $component->search = $term;

        return $component->topResults();
    }

    private function company(string $name, string $cui, string $regCom, ?int $status, string $registrationDate = '1995-01-01'): Company
    {
        $company = Company::create([
            'name' => $name,
            'cui' => $cui,
            'reg_com' => $regCom,
            'euid' => 'ROONRC.'.$regCom,
            'type' => 'SRL',
            'registration_date' => $registrationDate,
        ]);

        if ($status !== null) {
            Status::create([
                'registration' => $regCom,
                'status' => $status,
            ]);
        }

        return $company;
    }
}
