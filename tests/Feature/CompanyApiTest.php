<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Caen;
use App\Models\CaenCompany;
use App\Models\Company;
use App\Models\EUBranch;
use App\Models\LegalRepresentative;
use App\Models\NaturalPersonRepresentative;
use App\Models\Nomenclator;
use App\Models\Status;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The public API is the main consumer of this application: a lookup by CUI has
 * to return the complete picture of a company (address, status, CAEN codes with
 * their definitions, representatives and EU branches).
 */
class CompanyApiTest extends TestCase
{
    use RefreshDatabase;

    private const CUI = '51859625';

    private const REG_COM = 'J2025037467000';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'companies.active_statuses' => [1048],
            'companies.obsolete_statuses' => [1049],
            'companies.hide_obsolete' => true,
        ]);
    }

    public function test_it_returns_the_full_company_payload_for_a_cui(): void
    {
        $company = $this->createCompany(self::CUI, self::REG_COM, 1048);

        $response = $this->getJson('/api/v1/info?cui='.self::CUI);

        $response->assertOk()
            ->assertJsonPath('name', 'RTI GLOBAL SERVICES S.R.L.')
            ->assertJsonPath('cui', self::CUI)
            ->assertJsonPath('reg_com', self::REG_COM)
            ->assertJsonPath('is_active', 1)
            ->assertJsonPath('address.city', 'București Sectorul 3')
            ->assertJsonPath('status.details.description', 'funcțiune')
            ->assertJsonPath('caen.0.code', 6210)
            ->assertJsonPath('caen.0.caen_details.name', 'Activități de realizare a soft-ului la comandă')
            ->assertJsonPath('legal_representatives.0.person_name', 'POPESCU ION')
            ->assertJsonPath('natural_person_representatives.0.full_name', 'IONESCU ANA')
            ->assertJsonPath('eu_branches.0.branch_name', 'SUCURSALA ROMA');

        $this->assertSame($company->id, LegalRepresentative::first()->company_id);
    }

    public function test_the_payload_survives_the_cache_round_trip(): void
    {
        $this->createCompany(self::CUI, self::REG_COM, 1048);

        $this->getJson('/api/v1/info?cui='.self::CUI)->assertOk();

        // The second call is served from the cache and must return the same shape.
        $this->getJson('/api/v1/info?cui='.self::CUI)
            ->assertOk()
            ->assertJsonPath('name', 'RTI GLOBAL SERVICES S.R.L.')
            ->assertJsonPath('status.details.description', 'funcțiune')
            ->assertJsonMissingPath('__PHP_Incomplete_Class_Name');
    }

    public function test_it_returns_the_active_company_when_a_cif_was_reused(): void
    {
        $this->createCompany('77777777', 'J40/1/1995', 1049, 'OLD COMPANY SRL');
        $this->createCompany('77777777', 'J40/2/2024', 1048, 'NEW COMPANY SRL');

        $this->getJson('/api/v1/info?cui=77777777')
            ->assertOk()
            ->assertJsonPath('name', 'NEW COMPANY SRL')
            ->assertJsonPath('reg_com', 'J40/2/2024')
            ->assertJsonPath('is_active', 1);
    }

    public function test_it_lists_only_presentable_companies_by_name(): void
    {
        $this->createCompany('11111111', 'J40/3/2024', 1048, 'ALFA ACTIVE SRL');
        $this->createCompany('22222222', 'J40/4/2024', 1049, 'ALFA OBSOLETE SRL');

        $response = $this->getJson('/api/v1/info?company=ALFA');

        $response->assertOk();
        $names = array_column($response->json('data'), 'name');

        $this->assertContains('ALFA ACTIVE SRL', $names);
        $this->assertNotContains('ALFA OBSOLETE SRL', $names);
    }

    private function createCompany(
        string $cui,
        string $regCom,
        int $statusCode,
        string $name = 'RTI GLOBAL SERVICES S.R.L.'
    ): Company {
        $company = Company::create([
            'name' => $name,
            'cui' => $cui,
            'reg_com' => $regCom,
            'euid' => 'ROONRC.'.$regCom,
            'type' => 'SRL',
            'registration_date' => '2025-05-26',
        ]);

        Address::create([
            'company_id' => $company->id,
            'country' => 'România',
            'city' => 'București Sectorul 3',
            'county' => 'București',
            'street' => 'BARBU LĂZĂREANU',
            'number' => '2',
            'apartment' => '15',
            'postalCode' => '32077',
        ]);

        Status::create(['registration' => $regCom, 'status' => $statusCode]);

        Nomenclator::updateOrCreate(['code' => $statusCode], ['description' => $statusCode === 1048 ? 'funcțiune' : 'dizolvare']);

        Caen::updateOrCreate(
            ['class' => 6210, 'version' => 3],
            ['section' => 'K', 'division' => 62, 'group' => 621, 'name' => 'Activități de realizare a soft-ului la comandă']
        );

        CaenCompany::create(['registration' => $regCom, 'code' => 6210, 'version' => 3]);

        LegalRepresentative::create([
            'company_id' => $company->id,
            'registration' => $regCom,
            'person_name' => 'POPESCU ION',
            'role' => 'administrator',
        ]);

        NaturalPersonRepresentative::create([
            'company_id' => $company->id,
            'registration' => $regCom,
            'full_name' => 'IONESCU ANA',
            'role' => 'membru',
        ]);

        EUBranch::create([
            'company_id' => $company->id,
            'registration' => $regCom,
            'branch_name' => 'SUCURSALA ROMA',
            'country' => 'Italia',
        ]);

        return $company;
    }
}
