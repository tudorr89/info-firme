<?php

namespace Tests\Feature;

use App\Enums\CifRemovalStatus;
use App\Livewire\CompanySearch;
use App\Models\Address;
use App\Models\CaenCompany;
use App\Models\CifRemovalRequest;
use App\Models\Company;
use App\Models\LegalRepresentative;
use App\Models\Status;
use App\Models\User;
use App\Services\BannedCifService;
use App\Services\CifRemovalService;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tests\TruncatesCommittedDatabase;

/**
 * An approved removal request has to behave exactly like a CIF banned in code:
 * the data is deleted, the CIF is skipped by future imports and nothing serves
 * it any more. A rejected request must change nothing.
 */
class CifRemovalApprovalTest extends TestCase
{
    use TruncatesCommittedDatabase;

    private const CUI = '12345678';

    private const REG_COM = 'J40/1234/2024';

    private const OTHER_CUI = '87654321';

    private const OTHER_REG_COM = 'J40/5678/2024';

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
    }

    public function test_approving_a_request_bans_the_cif_and_deletes_its_data(): void
    {
        $company = $this->company('SOCIETATEA MEA S.R.L.', self::CUI, self::REG_COM);
        $request = $this->request(self::CUI);

        $deleted = CifRemovalService::approve($request);

        $this->assertSame(1, $deleted['companies']);
        $this->assertSame(CifRemovalStatus::Approved, $request->fresh()->status);
        $this->assertNotNull($request->fresh()->reviewed_at);

        $this->assertDatabaseMissing('companies', ['cui' => self::CUI]);
        $this->assertDatabaseMissing('addresses', ['company_id' => $company->id]);
        $this->assertDatabaseMissing('statuses', ['registration' => self::REG_COM]);
        $this->assertDatabaseMissing('caen_companies', ['registration' => self::REG_COM]);
        $this->assertDatabaseMissing('legal_representatives', ['registration' => self::REG_COM]);

        $this->assertTrue(BannedCifService::isBanned(self::CUI));
    }

    public function test_an_approved_cif_disappears_from_the_public_site(): void
    {
        $this->company('SOCIETATEA MEA S.R.L.', self::CUI, self::REG_COM);
        $this->company('ALTĂ SOCIETATE S.R.L.', self::OTHER_CUI, self::OTHER_REG_COM);

        // The page was cached before the approval.
        $this->get(route('company.show', self::CUI))->assertOk();

        CifRemovalService::approve($this->request(self::CUI));

        $this->get(route('company.show', self::CUI))->assertNotFound();
        $this->get('/api/v1/info?cui='.self::CUI)->assertNotFound();
    }

    public function test_an_approved_cif_is_not_returned_by_search(): void
    {
        $this->company('SOCIETATEA MEA S.R.L.', self::CUI, self::REG_COM);
        $this->company('ALTĂ SOCIETATE S.R.L.', self::OTHER_CUI, self::OTHER_REG_COM);

        CifRemovalService::approve($this->request(self::CUI));

        $component = new CompanySearch;
        $component->search = 'societate';

        $this->assertSame(['ALTĂ SOCIETATE S.R.L.'], $component->topResults()->pluck('name')->all());
    }

    public function test_the_approved_cif_is_skipped_by_future_imports(): void
    {
        CifRemovalService::approve($this->request(self::CUI));

        $this->assertContains(self::CUI, BannedCifService::all());
    }

    public function test_rejecting_a_request_changes_nothing(): void
    {
        $this->company('SOCIETATEA MEA S.R.L.', self::CUI, self::REG_COM);
        $request = $this->request(self::CUI);

        CifRemovalService::reject($request, null, 'Nu reprezintă firma.');

        $this->assertSame(CifRemovalStatus::Rejected, $request->fresh()->status);
        $this->assertSame('Nu reprezintă firma.', $request->fresh()->review_note);
        $this->assertFalse(BannedCifService::isBanned(self::CUI));

        $this->get(route('company.show', self::CUI))->assertOk();
        $this->assertDatabaseHas('companies', ['cui' => self::CUI]);
    }

    public function test_rejecting_a_previously_approved_request_unbans_the_cif_again(): void
    {
        $this->company('SOCIETATEA MEA S.R.L.', self::CUI, self::REG_COM);
        $request = $this->request(self::CUI);

        CifRemovalService::approve($request);
        $this->assertTrue(BannedCifService::isBanned(self::CUI));

        CifRemovalService::reject($request->fresh(), null, 'Aprobată din greșeală.');

        $this->assertFalse(BannedCifService::isBanned(self::CUI));
    }

    public function test_the_reviewer_is_recorded(): void
    {
        $this->company('SOCIETATEA MEA S.R.L.', self::CUI, self::REG_COM);
        $request = $this->request(self::CUI);
        $admin = $this->admin();

        CifRemovalService::approve($request, $admin, 'Verificat.');

        $this->assertSame($admin->id, $request->fresh()->reviewed_by);
        $this->assertSame('Verificat.', $request->fresh()->review_note);
        $this->assertSame($admin->name, $request->fresh()->reviewer?->name);
    }

    public function test_the_admin_panel_requires_an_admin_user(): void
    {
        $this->get('/admin/cif-removal-requests')->assertRedirect('/admin/login');
    }

    public function test_an_administrator_can_open_the_requests_list(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/cif-removal-requests')
            ->assertOk();
    }

    private function request(string $cui): CifRemovalRequest
    {
        return CifRemovalRequest::create([
            'cui' => $cui,
            'company_name' => 'SOCIETATEA MEA S.R.L.',
            'requester_name' => 'Ion Popescu',
            'requester_email' => 'ion.popescu@example.ro',
            'reason' => 'Solicit eliminarea datelor firmei mele din aplicație.',
        ]);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin@example.ro',
            'password' => Hash::make('password'),
            'admin' => true,
        ]);
    }

    private function company(string $name, string $cui, string $regCom): Company
    {
        $company = Company::create([
            'name' => $name,
            'cui' => $cui,
            'reg_com' => $regCom,
            'euid' => 'ROONRC.'.$regCom,
            'type' => 'SRL',
            'registration_date' => '2024-01-01',
        ]);

        Address::create(['company_id' => $company->id, 'city' => 'București']);
        Status::create(['registration' => $regCom, 'status' => 1048]);
        CaenCompany::create(['registration' => $regCom, 'code' => 6201, 'version' => 3]);
        LegalRepresentative::create([
            'company_id' => $company->id,
            'registration' => $regCom,
            'person_name' => 'POPESCU ION',
        ]);

        return $company;
    }
}
