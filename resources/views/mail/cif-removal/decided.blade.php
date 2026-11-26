@component('mail::message')
# Cererea ta a fost {{ $approved ? 'aprobată' : 'respinsă' }}

Bună, {{ $request->requester_name }},

cererea ta de eliminare a firmei **{{ $request->company_name ?? 'cu CUI '.$request->cui }}** (CUI **{{ $request->cui }}**) a fost {{ $approved ? 'aprobată' : 'respinsă' }}.

<table role="presentation" cellpadding="0" cellspacing="0" style="margin: 18px 0; border-collapse: collapse; background-color: {{ $approved ? '#ecfdf5' : '#fef2f2' }}; border-left: 4px solid {{ $approved ? '#10b981' : '#ef4444' }}; border-radius: 6px;">
<tr>
<td style="padding: 14px 16px; color: {{ $approved ? '#065f46' : '#991b1b' }}; font-size: 15px; line-height: 1.6;">
@if($approved)
<strong>Datele firmei au fost șterse</strong> din lista-firme.info, iar codul fiscal nu va mai fi afișat nici în viitor.
@else
<strong>Firma rămâne afișată</strong> pe lista-firme.info. Dacă nu ești de acord cu decizia, poți răspunde la acest e-mail cu detalii suplimentare.
@endif
</td>
</tr>
</table>

<table role="presentation" cellpadding="0" cellspacing="0" style="margin: 18px 0; border-collapse: collapse;">
<tr>
<td style="padding: 0; color: #4b5563; font-size: 14px; line-height: 1.7;">
<strong>Număr de înregistrare:</strong> #{{ $request->id }}<br>
<strong>Soluționată la:</strong> {{ $request->reviewed_at?->format('d.m.Y H:i') ?? '—' }}
</td>
</tr>
</table>

Mulțumim,<br>
Echipa lista-firme.info
@endcomponent
