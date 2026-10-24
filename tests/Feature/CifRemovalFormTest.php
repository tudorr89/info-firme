<?php

namespace Tests\Feature;

use App\Livewire\CifRemovalForm;
use App\Models\CifRemovalRequest;
use App\Models\Company;
use App\Models\Status;
use App\Services\BannedCifService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The public request form. A request is only a request: nothing is hidden
 * until an operator approves it in the admin panel.
 */
class CifRemovalFormTest extends TestCase
{
    use RefreshDatabase;

    private const CUI = '12345678';

    private const REG_COM = 'J40/1234/2024';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'companies.active_statuses' => [1048],
            'companies.obsolete_statuses' => [1049],
            'companies.hide_obsolete' => true,
        ]);

        $this->company();
    }

    public function test_the_form_page_is_available_in_romanian(): void
    {
        $this->get(route('cif-removal.create'))
            ->assertOk()
            ->assertSee('Cerere de')
            ->assertSee('CUI / CIF')
            ->assertSee('Motivul cererii');
    }

    public function test_it_stores_a_pending_request(): void
    {
        Livewire::test(CifRemovalForm::class)
            ->set('cui', self::CUI)
            ->set('requesterName', 'Ion Popescu')
            ->set('requesterEmail', 'ion.popescu@example.ro')
            ->set('reason', 'Solicit eliminarea datelor firmei mele din aplicație.')
            ->set('declaration', true)
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true);

        $this->assertDatabaseHas('cif_removal_requests', [
            'cui' => self::CUI,
            'company_name' => 'TEST COMPANY S.R.L.',
            'requester_name' => 'Ion Popescu',
            'requester_email' => 'ion.popescu@example.ro',
            'status' => 'pending',
        ]);
    }

    public function test_the_request_does_not_hide_the_company_on_its_own(): void
    {
        $this->submitRequest();

        $this->get(route('company.show', self::CUI))->assertOk();
    }

    public function test_it_requires_every_field(): void
    {
        Livewire::test(CifRemovalForm::class)
            ->call('submit')
            ->assertHasErrors([
                'cui' => 'required',
                'requesterName' => 'required',
                'requesterEmail' => 'required',
                'reason' => 'required',
                'declaration' => 'accepted',
            ]);

        $this->assertSame(0, CifRemovalRequest::count());
    }

    public function test_the_cui_must_be_numeric(): void
    {
        Livewire::test(CifRemovalForm::class)
            ->set('cui', 'J40/1234')
            ->set('requesterName', 'Ion Popescu')
            ->set('requesterEmail', 'ion.popescu@example.ro')
            ->set('reason', 'Solicit eliminarea datelor firmei mele din aplicație.')
            ->set('declaration', true)
            ->call('submit')
            ->assertHasErrors(['cui' => 'regex']);
    }

    public function test_the_reason_must_be_long_enough(): void
    {
        Livewire::test(CifRemovalForm::class)
            ->set('cui', self::CUI)
            ->set('requesterName', 'Ion Popescu')
            ->set('requesterEmail', 'ion.popescu@example.ro')
            ->set('reason', 'prea scurt')
            ->set('declaration', true)
            ->call('submit')
            ->assertHasErrors(['reason' => 'min']);
    }

    public function test_the_email_must_be_valid(): void
    {
        Livewire::test(CifRemovalForm::class)
            ->set('cui', self::CUI)
            ->set('requesterName', 'Ion Popescu')
            ->set('requesterEmail', 'nu-e-email')
            ->set('reason', 'Solicit eliminarea datelor firmei mele din aplicație.')
            ->set('declaration', true)
            ->call('submit')
            ->assertHasErrors(['requesterEmail' => 'email']);
    }

    public function test_an_unknown_cui_is_rejected(): void
    {
        Livewire::test(CifRemovalForm::class)
            ->set('cui', '99999999')
            ->set('requesterName', 'Ion Popescu')
            ->set('requesterEmail', 'ion.popescu@example.ro')
            ->set('reason', 'Solicit eliminarea datelor firmei mele din aplicație.')
            ->set('declaration', true)
            ->call('submit')
            ->assertHasErrors(['cui']);

        $this->assertSame(0, CifRemovalRequest::count());
    }

    public function test_an_already_banned_cui_cannot_be_requested(): void
    {
        config(['skip.always' => [self::CUI]]);
        BannedCifService::flush();

        Livewire::test(CifRemovalForm::class)
            ->set('cui', self::CUI)
            ->set('requesterName', 'Ion Popescu')
            ->set('requesterEmail', 'ion.popescu@example.ro')
            ->set('reason', 'Solicit eliminarea datelor firmei mele din aplicație.')
            ->set('declaration', true)
            ->call('submit')
            ->assertHasErrors(['cui']);

        $this->assertSame(0, CifRemovalRequest::count());
    }

    public function test_a_second_pending_request_for_the_same_cif_is_refused(): void
    {
        $this->submitRequest();

        Livewire::test(CifRemovalForm::class)
            ->set('cui', self::CUI)
            ->set('requesterName', 'Altă Persoană')
            ->set('requesterEmail', 'alta@example.ro')
            ->set('reason', 'Solicit eliminarea datelor firmei mele din aplicație.')
            ->set('declaration', true)
            ->call('submit')
            ->assertHasErrors(['cui']);

        $this->assertSame(1, CifRemovalRequest::count());
    }

    public function test_the_bot_trap_does_not_create_a_request(): void
    {
        Livewire::test(CifRemovalForm::class)
            ->set('website', 'http://spam.example')
            ->set('cui', self::CUI)
            ->set('requesterName', 'Bot')
            ->set('requesterEmail', 'bot@example.ro')
            ->set('reason', 'Solicit eliminarea datelor firmei mele din aplicație.')
            ->set('declaration', true)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(0, CifRemovalRequest::count());
    }

    public function test_it_pushes_a_gotify_notification_to_the_operator(): void
    {
        $this->enableGotify();
        Http::fake(['gotify.test/*' => Http::response(['id' => 1], 200)]);

        $this->submitRequest();

        Http::assertSent(function (Request $request): bool {
            $payload = $request->data();

            return $request->method() === 'POST'
                && str_contains($request->url(), 'gotify.test/message?token=test-token')
                && $payload['title'] === 'Cerere nouă de ștergere CIF '.self::CUI
                && str_contains($payload['message'], 'TEST COMPANY S.R.L.')
                && str_contains($payload['message'], 'ion.popescu@example.ro')
                && str_contains($payload['message'], '/admin/cif-removal-requests/');
        });
    }

    public function test_nothing_is_pushed_when_gotify_is_disabled(): void
    {
        config(['skip.gotify.enabled' => false]);
        Http::fake();

        $this->submitRequest();

        Http::assertNothingSent();
        $this->assertDatabaseHas('cif_removal_requests', ['cui' => self::CUI, 'status' => 'pending']);
    }

    public function test_a_rejected_push_does_not_lose_the_request(): void
    {
        $this->enableGotify();
        Http::fake(['gotify.test/*' => Http::response('nope', 500)]);

        $this->submitRequest();

        $this->assertDatabaseHas('cif_removal_requests', ['cui' => self::CUI, 'status' => 'pending']);
    }

    public function test_a_gotify_outage_does_not_lose_the_request(): void
    {
        $this->enableGotify();
        Http::fake(['gotify.test/*' => fn () => throw new \RuntimeException('Gotify down')]);

        $this->submitRequest();

        $this->assertDatabaseHas('cif_removal_requests', ['cui' => self::CUI, 'status' => 'pending']);
    }

    public function test_nothing_is_pushed_when_validation_fails(): void
    {
        $this->enableGotify();
        Http::fake();

        Livewire::test(CifRemovalForm::class)
            ->set('cui', 'nu-e-cif')
            ->call('submit');

        Http::assertNothingSent();
    }

    public function test_it_shows_the_company_matching_the_cui(): void
    {
        Livewire::test(CifRemovalForm::class)
            ->set('cui', self::CUI)
            ->assertSee('TEST COMPANY S.R.L.');
    }

    private function enableGotify(): void
    {
        config([
            'skip.gotify.enabled' => true,
            'skip.gotify.url' => 'https://gotify.test',
            'skip.gotify.token' => 'test-token',
        ]);
    }

    private function submitRequest(): void
    {
        Livewire::test(CifRemovalForm::class)
            ->set('cui', self::CUI)
            ->set('requesterName', 'Ion Popescu')
            ->set('requesterEmail', 'ion.popescu@example.ro')
            ->set('reason', 'Solicit eliminarea datelor firmei mele din aplicație.')
            ->set('declaration', true)
            ->call('submit')
            ->assertHasNoErrors();
    }

    private function company(): Company
    {
        $company = Company::create([
            'name' => 'TEST COMPANY S.R.L.',
            'cui' => self::CUI,
            'reg_com' => self::REG_COM,
            'euid' => 'ROONRC.'.self::REG_COM,
            'type' => 'SRL',
            'registration_date' => '2024-01-01',
        ]);

        Status::create(['registration' => self::REG_COM, 'status' => 1048]);

        return $company;
    }
}
