@component('mail::message')
# Am primit cererea ta

Bună, {{ $request->requester_name }},

îți mulțumim. Am înregistrat cererea ta de eliminare a firmei **{{ $request->company_name ?? 'cu CUI '.$request->cui }}** (CUI **{{ $request->cui }}**) din lista-firme.info.

<table role="presentation" cellpadding="0" cellspacing="0" style="margin: 18px 0; border-collapse: collapse; background-color: #f5f3ff; border-left: 4px solid #8b5cf6; border-radius: 6px;">
<tr>
<td style="padding: 14px 16px; color: #4c1d95; font-size: 15px; line-height: 1.6;">
<strong>Număr de înregistrare:</strong> #{{ $request->id }}<br>
<strong>Trimisă la:</strong> {{ $request->created_at?->format('d.m.Y H:i') ?? '—' }}
</td>
</tr>
</table>

Analizăm cererea și îți răspundem la această adresă de e-mail în cel mult **o lună**, conform art. 12 alineatul (3) din Regulamentul (UE) 2016/679 (GDPR).

Dacă nu ai trimis tu această cerere, răspunde la acest e-mail ca să o anulăm.

Mulțumim,<br>
Echipa lista-firme.info
@endcomponent
