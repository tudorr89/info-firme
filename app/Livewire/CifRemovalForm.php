<?php

namespace App\Livewire;

use App\Mail\CifRemovalRequestReceived;
use App\Models\CifRemovalRequest;
use App\Models\Company;
use App\Services\BannedCifService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app')]
class CifRemovalForm extends Component
{
    public string $cui = '';

    public string $requesterName = '';

    public string $requesterEmail = '';

    public string $reason = '';

    public bool $declaration = false;

    /**
     * Simple bot trap: a real visitor never fills a field hidden with CSS.
     */
    public string $website = '';

    public bool $submitted = false;

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'cui' => ['required', 'string', 'regex:/^[0-9]{2,10}$/'],
            'requesterName' => ['required', 'string', 'min:3', 'max:255'],
            'requesterEmail' => ['required', 'email', 'max:255'],
            'reason' => ['required', 'string', 'min:20', 'max:2000'],
            'declaration' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cui.required' => 'Introdu codul fiscal (CUI/CIF) al firmei.',
            'cui.regex' => 'CUI/CIF trebuie să conțină între 2 și 10 cifre.',
            'requesterName.required' => 'Introdu numele și prenumele tău.',
            'requesterName.min' => 'Numele trebuie să aibă cel puțin 3 caractere.',
            'requesterEmail.required' => 'Introdu o adresă de e-mail la care te putem contacta.',
            'requesterEmail.email' => 'Adresa de e-mail nu este validă.',
            'reason.required' => 'Explică motivul cererii.',
            'reason.min' => 'Motivul trebuie să aibă cel puțin 20 de caractere.',
            'reason.max' => 'Motivul poate avea cel mult 2000 de caractere.',
            'declaration.accepted' => 'Trebuie să confirmi declarația pentru a trimite cererea.',
        ];
    }

    /**
     * Show the company the CIF belongs to, as confirmation for the visitor.
     */
    public function companyName(): ?string
    {
        if (! preg_match('/^[0-9]{2,10}$/', $this->cui)) {
            return null;
        }

        return Company::where('cui', $this->cui)->value('name');
    }

    public function submit(): void
    {
        // Silently accept the bot trap so the bot does not learn anything.
        if ($this->website !== '') {
            $this->submitted = true;

            return;
        }

        $this->validate();

        $throttleKey = 'cif-removal:'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $this->addError('cui', 'Ai trimis prea multe cereri. Încearcă din nou peste o oră.');

            return;
        }

        if (BannedCifService::isBanned($this->cui)) {
            $this->addError('cui', 'Acest CUI/CIF nu mai este afișat pe lista-firme.info.');

            return;
        }

        if (! Company::where('cui', $this->cui)->exists()) {
            $this->addError('cui', 'Nu am găsit nicio firmă cu acest CUI/CIF. Verifică numărul și încearcă din nou.');

            return;
        }

        if (CifRemovalRequest::query()->where('cui', $this->cui)->pending()->exists()) {
            $this->addError('cui', 'Există deja o cerere în așteptare pentru acest CUI/CIF. Te vom contacta pe e-mail.');

            return;
        }

        RateLimiter::hit($throttleKey, 3600);

        $request = CifRemovalRequest::create([
            'cui' => $this->cui,
            'company_name' => Company::where('cui', $this->cui)->value('name'),
            'requester_name' => $this->requesterName,
            'requester_email' => $this->requesterEmail,
            'reason' => $this->reason,
            'ip_address' => request()->ip(),
        ]);

        $this->notifyOperator($request);

        $this->submitted = true;
        $this->reset('cui', 'requesterName', 'requesterEmail', 'reason', 'declaration');
    }

    /**
     * A mail failure must never lose the request that was just stored.
     */
    private function notifyOperator(CifRemovalRequest $request): void
    {
        $recipient = (string) config('skip.notification_email');

        if ($recipient === '') {
            return;
        }

        try {
            Mail::to($recipient)->send(new CifRemovalRequestReceived($request));
        } catch (Throwable $exception) {
            Log::error('CIF removal request e-mail could not be sent', [
                'cui' => $request->cui,
                'request_id' => $request->getKey(),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function render(): View
    {
        $seoMeta = view('components.seo-meta', [
            'title' => 'Cerere de ștergere a unui CIF | lista-firme.info',
            'description' => 'Solicită eliminarea unei firme din lista-firme.info, în baza dreptului la opoziție prevăzut de Regulamentul UE 2016/679 (GDPR).',
            'canonical' => route('cif-removal.create'),
            'robots' => 'noindex, follow',
        ])->render();

        return view('livewire.cif-removal-form', [
            'companyName' => $this->companyName(),
        ])->layoutData(['seoMeta' => $seoMeta]);
    }
}
