<x-mail::layout>
    {{-- Header --}}
    <x-slot:header>
        <x-mail::header :url="url('/')">
            lista-firme.info
        </x-mail::header>
    </x-slot:header>

    {{-- Body --}}
    {{ $slot }}

    {{-- Subcopy --}}
    @isset($subcopy)
        <x-slot:subcopy>
            <x-mail::subcopy>
                {{ $subcopy }}
            </x-mail::subcopy>
        </x-slot:subcopy>
    @endisset

    {{-- Footer --}}
    <x-slot:footer>
        <x-mail::footer>
Ai primit acest mesaj pentru că ai trimis o cerere de ștergere a datelor prin lista-firme.info ({{ url('/') }}).

© {{ date('Y') }} lista-firme.info
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>
