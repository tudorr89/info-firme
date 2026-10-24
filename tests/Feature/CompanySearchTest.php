<?php

namespace Tests\Feature;

use App\Livewire\CompanySearch;
use App\Models\Company;
use App\Models\Status;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\TruncatesCommittedDatabase;

/**
 * The search box used to run an unanchored `LIKE '%term%'` plus correlated
 * status subqueries over the whole companies table, which took about 35
 * seconds per keystroke. It now resolves candidate ids from the CUI index and
 * the `companies_name_fulltext` index, then applies the status filters only to
 * those candidates.
 *
 * `RefreshDatabase` cannot be used here: InnoDB full-text indexes do not see
 * rows written inside an uncommitted transaction, so the inserts have to be
 * committed (hence `DatabaseTruncation`).
 */
class CompanySearchTest extends TestCase
{
    use TruncatesCommittedDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'companies.active_statuses' => [1048],
            'companies.obsolete_statuses' => [1049],
            'companies.hide_obsolete' => true,
        ]);
    }

    public function test_it_finds_a_company_by_name_prefix(): void
    {
        $this->company('RTI GLOBAL SERVICES S.R.L.', '51859625', 'J2025037467000');

        $names = $this->search('rti')->pluck('name')->all();

        $this->assertSame(['RTI GLOBAL SERVICES S.R.L.'], $names);
    }

    public function test_name_search_is_case_insensitive(): void
    {
        $this->company('ENERGIE VERDE S.R.L.', '11111111', 'J40/1/2024');

        $this->assertCount(1, $this->search('energie'));
        $this->assertCount(1, $this->search('ENERGIE'));
    }

    public function test_name_search_requires_every_word(): void
    {
        $this->company('RTI GLOBAL SERVICES S.R.L.', '51859625', 'J2025037467000');
        $this->company('RTI SOLAR S.R.L.', '22222222', 'J40/2/2024');

        $this->assertSame(
            ['RTI GLOBAL SERVICES S.R.L.'],
            $this->search('rti global')->pluck('name')->all()
        );
    }

    public function test_it_finds_a_company_by_exact_cui(): void
    {
        $this->company('RTI GLOBAL SERVICES S.R.L.', '51859625', 'J2025037467000');

        $this->assertCount(1, $this->search('51859625'));
    }

    public function test_it_finds_a_company_by_cui_prefix(): void
    {
        $this->company('RTI GLOBAL SERVICES S.R.L.', '51859625', 'J2025037467000');

        $this->assertSame(
            ['RTI GLOBAL SERVICES S.R.L.'],
            $this->search('5185')->pluck('name')->all()
        );
    }

    public function test_a_single_character_search_returns_nothing(): void
    {
        $this->company('RTI GLOBAL SERVICES S.R.L.', '51859625', 'J2025037467000');

        $this->assertCount(0, $this->search('r'));
    }

    public function test_a_two_character_search_only_matches_the_cui(): void
    {
        $this->company('RTI GLOBAL SERVICES S.R.L.', '51859625', 'J2025037467000');

        // Below the full-text minimum token size, so the name is not searched.
        $this->assertCount(0, $this->search('rt'));
        $this->assertCount(1, $this->search('51'));
    }

    public function test_it_hides_obsolete_companies(): void
    {
        $this->company('ACTIVE ENERGY S.R.L.', '11111111', 'J40/1/2024', 1048);
        $this->company('OBSOLETE ENERGY S.R.L.', '22222222', 'J40/2/2024', 1049);

        $this->assertSame(['ACTIVE ENERGY S.R.L.'], $this->search('energy')->pluck('name')->all());
    }

    public function test_full_text_operators_in_the_input_are_ignored(): void
    {
        $this->company('RTI GLOBAL SERVICES S.R.L.', '51859625', 'J2025037467000');

        $this->assertCount(1, $this->search('+rti*'));
        $this->assertCount(1, $this->search('-rti'));
    }

    public function test_legal_form_suffixes_are_dropped_from_the_query(): void
    {
        $this->company('RTI GLOBAL SERVICES S.R.L.', '51859625', 'J2025037467000');

        // "srl" on its own matches almost every company, so it is ignored.
        $this->assertSame(
            ['RTI GLOBAL SERVICES S.R.L.'],
            $this->search('rti global srl')->pluck('name')->all()
        );
    }

    public function test_a_query_made_only_of_legal_forms_is_reported_as_too_generic(): void
    {
        $this->company('RTI GLOBAL SERVICES S.R.L.', '51859625', 'J2025037467000');

        $component = new CompanySearch;
        $component->search = 'S.R.L.';

        $this->assertTrue($component->isTooGeneric());
        $this->assertCount(0, $component->topResults());
    }

    public function test_a_meaningful_query_is_not_reported_as_too_generic(): void
    {
        $component = new CompanySearch;
        $component->search = 'rti';

        $this->assertFalse($component->isTooGeneric());
    }

    public function test_the_search_redirects_to_the_company_page(): void
    {
        $company = $this->company('RTI GLOBAL SERVICES S.R.L.', '51859625', 'J2025037467000');

        Livewire::test(CompanySearch::class)
            ->call('selectCompany', $company->id)
            ->assertRedirect(route('company.show', '51859625'));
    }

    /**
     * @return Collection<int, Company>
     */
    private function search(string $term): Collection
    {
        $component = new CompanySearch;
        $component->search = $term;

        return $component->topResults();
    }

    private function company(
        string $name,
        string $cui,
        string $regCom,
        ?int $status = 1048,
        string $registrationDate = '2024-01-01'
    ): Company {
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
