@component('mail::message')
# Cerere nouă de ștergere CIF

A fost trimisă o cerere nouă prin formularul public. Detalii:

@component('mail::table')
| | |
|:--|:--|
| **CUI / CIF** | {{ $cifRequest->cui }} |
| **Firma** | {{ $cifRequest->company_name ?? '—' }} |
| **Solicitant** | {{ $cifRequest->requester_name }} |
| **E-mail** | {{ $cifRequest->requester_email }} |
| **Trimisă la** | {{ $cifRequest->created_at?->format('d.m.Y H:i') }} |
| **Adresa IP** | {{ $cifRequest->ip_address ?? '—' }} |
@endcomponent

### Motivul invocat

{{ $cifRequest->reason }}

@component('mail::button', ['url' => $reviewUrl])
Deschide cererea în panoul de administrare
@endcomponent

Poți răspunde direct la acest mesaj pentru a-i scrie solicitantului.

Mulțumim,<br>
{{ config('app.name') }}
@endcomponent
