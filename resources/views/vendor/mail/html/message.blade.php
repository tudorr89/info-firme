@component('mail::layout')

{{-- Header: brand mark from public/apple-touch-icon.png (absolute URL, because
     a mail client has no origin to resolve a relative one against). --}}
@slot('header')
@component('mail::header', ['url' => url('/')])
lista-firme.info
@endcomponent
@endslot

{{-- Body --}}
{{ $slot }}

{{-- Subcopy --}}
@isset($subcopy)
@slot('subcopy')
@component('mail::subcopy')
{{ $subcopy }}
@endcomponent
@endslot
@endisset

{{-- Footer: Romanian, and it says why the message arrived (a GDPR mail should
     not look like a newsletter). --}}
@slot('footer')
@component('mail::footer')
Ai primit acest mesaj pentru că ai trimis o cerere de ștergere a datelor prin
<a href="{{ url('/') }}" style="color: #6b7280; text-decoration: underline;">lista-firme.info</a>.

© {{ date('Y') }} lista-firme.info
@endcomponent
@endslot

@endcomponent
