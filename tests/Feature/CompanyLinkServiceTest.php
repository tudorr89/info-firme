<?php

namespace Tests\Feature;

use App\Jobs\LinkCompanyDataJob;
use App\Models\Company;
use App\Models\EUBranch;
use App\Models\LegalRepresentative;
use App\Models\NaturalPersonRepresentative;
use App\Services\CompanyLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CompanyLinkServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_backfills_company_id_on_related_tables(): void
    {
        $company = $this->createCompany('123456789', 'J40/1234/2024');

        $representative = LegalRepresentative::create([
            'registration' => 'J40/1234/2024',
            'person_name' => 'POPESCU ION',
            'role' => 'administrator',
        ]);

        $naturalPerson = NaturalPersonRepresentative::create([
            'registration' => 'J40/1234/2024',
            'full_name' => 'IONESCU ANA',
        ]);

        $branch = EUBranch::create([
            'registration' => 'J40/1234/2024',
            'branch_name' => 'SUCURSALA',
        ]);

        $this->assertNull($representative->company_id);
        $this->assertNull($naturalPerson->company_id);
        $this->assertNull($branch->company_id);

        $linked = (new CompanyLinkService)->linkAll();

        $this->assertSame(1, $linked['legal_representatives']);
        $this->assertSame(1, $linked['natural_person_representatives']);
        $this->assertSame(1, $linked['e_u_branches']);

        $this->assertSame($company->id, $representative->fresh()->company_id);
        $this->assertSame($company->id, $naturalPerson->fresh()->company_id);
        $this->assertSame($company->id, $branch->fresh()->company_id);
    }

    public function test_linking_is_idempotent(): void
    {
        $this->createCompany('123456789', 'J40/1234/2024');

        LegalRepresentative::create([
            'registration' => 'J40/1234/2024',
            'person_name' => 'POPESCU ION',
            'role' => 'administrator',
        ]);

        $service = new CompanyLinkService;

        $this->assertSame(1, $service->linkAll()['legal_representatives']);
        $this->assertSame(0, $service->linkAll()['legal_representatives']);
    }

    public function test_unlinked_counts_report_rows_without_a_company(): void
    {
        $this->createCompany('123456789', 'J40/1234/2024');

        LegalRepresentative::create([
            'registration' => 'J40/9999/2024',
            'person_name' => 'ORPHAN',
        ]);

        $counts = (new CompanyLinkService)->unlinkedCounts();

        $this->assertSame(1, $counts['legal_representatives']);
        $this->assertSame(0, $counts['e_u_branches']);
    }

    public function test_prune_removes_rows_for_companies_that_were_not_imported(): void
    {
        $this->createCompany('123456789', 'J40/1234/2024');

        LegalRepresentative::create([
            'registration' => 'J40/1234/2024',
            'person_name' => 'LINKED PERSON',
        ]);

        LegalRepresentative::create([
            'registration' => 'J40/9999/2024',
            'person_name' => 'BANNED PERSON',
        ]);

        EUBranch::create([
            'registration' => 'J40/9999/2024',
            'branch_name' => 'BANNED BRANCH',
        ]);

        DB::table('statuses')->insert([
            'registration' => 'J40/1234/2024',
            'status' => 1048,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('statuses')->insert([
            'registration' => 'J40/9999/2024',
            'status' => 1048,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('caen_companies')->insert([
            'registration' => 'J40/9999/2024',
            'code' => 6201,
            'version' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pruned = (new CompanyLinkService)->pruneOrphans();

        $this->assertSame(1, $pruned['legal_representatives']);
        $this->assertSame(1, $pruned['e_u_branches']);
        $this->assertSame(1, $pruned['statuses']);
        $this->assertSame(1, $pruned['caen_companies']);

        $this->assertDatabaseHas('legal_representatives', ['person_name' => 'LINKED PERSON']);
        $this->assertDatabaseMissing('legal_representatives', ['person_name' => 'BANNED PERSON']);
        $this->assertDatabaseMissing('e_u_branches', ['branch_name' => 'BANNED BRANCH']);
        $this->assertSame(1, DB::table('statuses')->count());
    }

    public function test_the_job_links_and_accepts_pruning(): void
    {
        $company = $this->createCompany('123456789', 'J40/1234/2024');

        LegalRepresentative::create([
            'registration' => 'J40/1234/2024',
            'person_name' => 'POPESCU ION',
        ]);

        LegalRepresentative::create([
            'registration' => 'J40/9999/2024',
            'person_name' => 'ORPHAN',
        ]);

        LinkCompanyDataJob::withPruning()->handle(new CompanyLinkService);

        $this->assertSame($company->id, LegalRepresentative::first()->company_id);
        $this->assertSame(1, LegalRepresentative::count());
    }

    private function createCompany(string $cui, string $regCom): Company
    {
        return Company::create([
            'name' => 'TEST SRL',
            'cui' => $cui,
            'reg_com' => $regCom,
            'euid' => 'ROONRC.'.$regCom,
            'type' => 'SRL',
            'registration_date' => '2024-01-01',
        ]);
    }
}
