<?php

namespace Tests\Feature;

use App\Jobs\SendCifRemovalDecisionEmail;
use App\Jobs\SendCifRemovalRequestReceivedEmail;
use App\Livewire\CifRemovalForm;
use App\Mail\CifRemovalRequestDecided;
use App\Mail\CifRemovalRequestReceived;
use App\Models\CifRemovalRequest;
use App\Models\Company;
use App\Models\Status;
use App\Services\CifRemovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A GDPR removal request must be acknowledged and its outcome communicated to
 * the address the data subject supplied. Both mails travel through a queued job,
 * so the form never waits on the mail server and a delivery failure cannot lose
 * the request.
 */
class CifRemovalEmailTest extends TestCase
{
    use RefreshDatabase;

    private const CUI = '12345678';

    private const REG_COM = 'J40/1234/2024';

    private const EMAIL = 'ion.popescu@example.ro';

    private const COMPANY_NAME = 'TEST COMPANY S.R.L.';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'companies.active_statuses' => [1048],
            'companies.obsolete_statuses' => [1049],
            'companies.hide_obsolete' => true,
            'skip.always' => [],
        ]);
    }

    public function test_submitting_a_request_queues_the_receipt_email(): void
    {
        Queue::fake();

        $this->company();
        $this->submitRequest();

        Queue::assertPushed(
            SendCifRemovalRequestReceivedEmail::class,
            fn (SendCifRemovalRequestReceivedEmail $job): bool => $job->request->cui === self::CUI
                && $job->request->requester_email === self::EMAIL,
        );
    }

    public function test_the_receipt_email_reaches_the_requester_with_a_reference_number(): void
    {
        Mail::fake();

        $request = $this->pendingRequest();

        (new SendCifRemovalRequestReceivedEmail($request))->handle();

        Mail::assertSent(CifRemovalRequestReceived::class, function (CifRemovalRequestReceived $mail) use ($request): bool {
            $body = $mail->render();

            return $mail->hasTo(self::EMAIL)
                && str_contains($mail->subject, self::CUI)
                && str_contains($body, '#'.$request->getKey())
                && str_contains($body, self::COMPANY_NAME)
                // Art. 12(3): the data subject has to be told when to expect an answer.
                && str_contains($body, 'o lună');
        });
    }

    public function test_approving_a_request_queues_the_decision_email(): void
    {
        Queue::fake();

        $this->company();
        $request = $this->pendingRequest();

        CifRemovalService::approve($request);

        Queue::assertPushed(
            SendCifRemovalDecisionEmail::class,
            fn (SendCifRemovalDecisionEmail $job): bool => $job->request->getKey() === $request->getKey(),
        );
    }

    public function test_the_decision_email_says_the_request_was_approved(): void
    {
        Mail::fake();

        $this->company();
        $request = $this->pendingRequest();

        CifRemovalService::approve($request);

        Mail::assertSent(CifRemovalRequestDecided::class, function (CifRemovalRequestDecided $mail): bool {
            $body = $mail->render();

            return $mail->hasTo(self::EMAIL)
                && str_contains($mail->subject, 'aprobată')
                && str_contains($body, 'a fost aprobată')
                && str_contains($body, 'au fost șterse');
        });
    }

    public function test_the_decision_email_says_the_request_was_rejected(): void
    {
        Mail::fake();

        $this->company();
        $request = $this->pendingRequest();

        CifRemovalService::reject($request);

        Mail::assertSent(CifRemovalRequestDecided::class, function (CifRemovalRequestDecided $mail): bool {
            $body = $mail->render();

            return $mail->hasTo(self::EMAIL)
                && str_contains($mail->subject, 'respinsă')
                && str_contains($body, 'a fost respinsă')
                && str_contains($body, 'rămâne afișată');
        });
    }

    public function test_approving_the_same_request_twice_announces_the_decision_once(): void
    {
        Mail::fake();

        $this->company();
        $request = $this->pendingRequest();

        CifRemovalService::approve($request);
        CifRemovalService::approve($request->fresh());

        Mail::assertSent(CifRemovalRequestDecided::class, 1);
    }

    public function test_rejecting_the_same_request_twice_announces_the_decision_once(): void
    {
        Mail::fake();

        $this->company();
        $request = $this->pendingRequest();

        CifRemovalService::reject($request);
        CifRemovalService::reject($request->fresh());

        Mail::assertSent(CifRemovalRequestDecided::class, 1);
    }

    public function test_the_internal_review_note_never_reaches_the_requester(): void
    {
        Mail::fake();

        $this->company();
        $request = $this->pendingRequest();

        CifRemovalService::reject($request, null, 'Notă internă: pare spam, verificat manual.');

        Mail::assertSent(
            CifRemovalRequestDecided::class,
            fn (CifRemovalRequestDecided $mail): bool => ! str_contains($mail->render(), 'pare spam'),
        );
    }

    private function submitRequest(): void
    {
        Livewire::test(CifRemovalForm::class)
            ->set('cui', self::CUI)
            ->set('requesterName', 'Ion Popescu')
            ->set('requesterEmail', self::EMAIL)
            ->set('reason', 'Solicit eliminarea datelor firmei mele din aplicație.')
            ->set('declaration', true)
            ->call('submit')
            ->assertHasNoErrors();
    }

    private function pendingRequest(): CifRemovalRequest
    {
        return CifRemovalRequest::create([
            'cui' => self::CUI,
            'company_name' => self::COMPANY_NAME,
            'requester_name' => 'Ion Popescu',
            'requester_email' => self::EMAIL,
            'reason' => 'Solicit eliminarea datelor firmei mele din aplicație.',
            'ip_address' => '127.0.0.1',
        ]);
    }

    private function company(): Company
    {
        $company = Company::create([
            'name' => self::COMPANY_NAME,
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
