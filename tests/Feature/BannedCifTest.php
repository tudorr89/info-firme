<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\CaenCompany;
use App\Models\Company;
use App\Models\EUBranch;
use App\Models\LegalRepresentative;
use App\Models\NaturalPersonRepresentative;
use App\Models\Status;
use App\Services\BannedCifService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The banned CIFs are the reason this application exists, so they must be
 * skipped at import time and removable from an already imported database.
 */
class BannedCifTest extends TestCase
{
    use RefreshDatabase;

    private const BANNED_CUI = '52491237';

    public function test_the_banned_cui_is_always_in_the_skip_list(): void
    {
        $this->assertTrue(BannedCifService::isBanned(self::BANNED_CUI));
        $this->assertContains(self::BANNED_CUI, config('skip.always'));
    }

    public function test_the_enforced_list_is_part_of_the_effective_skip_list(): void
    {
        foreach (config('skip.always') as $cui) {
            $this->assertContains($cui, BannedCifService::all());
        }

        $this->assertFalse(BannedCifService::isBanned('1234567'));
    }

    public function test_the_environment_variable_is_no_longer_used(): void
    {
        // Requests are stored in the database now; a stale SKIP_CIFS_IMPORT
        // entry must not be able to ban a company silently.
        $this->assertArrayNotHasKey('cifs', config('skip'));
    }

    public function test_company_delete_removes_the_company_and_every_related_row(): void
    {
        $company = Company::create([
            'name' => 'BANNED COMPANY SRL',
            'cui' => self::BANNED_CUI,
            'reg_com' => 'F2025033927003',
            'euid' => 'ROONRC.F2025033927003',
            'type' => 'PF',
            'registration_date' => '2025-05-26',
        ]);

        Address::create(['company_id' => $company->id, 'city' => 'București']);
        Status::create(['registration' => 'F2025033927003', 'status' => 1048]);
        CaenCompany::create(['registration' => 'F2025033927003', 'code' => 6201, 'version' => 3]);
        LegalRepresentative::create(['company_id' => $company->id, 'registration' => 'F2025033927003', 'person_name' => 'X']);
        NaturalPersonRepresentative::create(['company_id' => $company->id, 'registration' => 'F2025033927003', 'full_name' => 'Y']);
        EUBranch::create(['company_id' => $company->id, 'registration' => 'F2025033927003', 'branch_name' => 'Z']);

        $this->artisan('company:delete', ['cif' => self::BANNED_CUI, '--force' => true])
            ->assertSuccessful();

        $this->assertDatabaseMissing('companies', ['cui' => self::BANNED_CUI]);
        $this->assertDatabaseMissing('addresses', ['company_id' => $company->id]);
        $this->assertDatabaseMissing('statuses', ['registration' => 'F2025033927003']);
        $this->assertDatabaseMissing('caen_companies', ['registration' => 'F2025033927003']);
        $this->assertDatabaseMissing('legal_representatives', ['registration' => 'F2025033927003']);
        $this->assertDatabaseMissing('natural_person_representatives', ['registration' => 'F2025033927003']);
        $this->assertDatabaseMissing('e_u_branches', ['registration' => 'F2025033927003']);
    }

    public function test_company_delete_reports_a_missing_company(): void
    {
        $this->artisan('company:delete', ['cif' => '9999999', '--force' => true])
            ->assertFailed();
    }
}
