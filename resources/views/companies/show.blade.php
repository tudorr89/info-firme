@extends('layouts.app')

@section('seoMeta')
{!! view('components.seo-meta', [
    'title' => $company->name . ' - CUI ' . $company->cui . ' | lista-firme.info',
    'description' => 'Informații despre ' . $company->name . ' - CUI: ' . $company->cui . '. Adresă, contact, reprezentanți legali, cod CAEN și detalii oficiale.',
    'type' => 'organization',
    'company' => $company,
    'canonical' => route('company.show', $company->cui),
])->render() !!}
@endsection

@section('content')
<div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900">
    <div class="max-w-4xl mx-auto">
        <a href="{{ route('company.search') }}" class="mb-6 inline-flex px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg border border-white/20 transition-all">
            ← Inapoi
        </a>

        <div class="bg-white/10 backdrop-blur-lg border border-white/20 rounded-2xl p-8 shadow-2xl">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-white mb-2">{{ $company->name }}</h1>
                <div class="flex gap-4 flex-wrap text-sm">
                    <span class="px-3 py-1 bg-purple-500/30 text-purple-200 rounded-full">CUI: {{ $company->cui }}</span>
                    @if($company->status)
                        <span class="px-3 py-1 bg-blue-500/30 text-blue-200 rounded-full">{{ $company->status->details?->description ?? 'N/A' }}</span>
                    @endif
                    @if($company->type)
                        <span class="px-3 py-1 bg-green-500/30 text-green-200 rounded-full">{{ $company->type }}</span>
                    @endif
                </div>
            </div>

            <!-- Grid Layout -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- General Info -->
                <div>
                    <h3 class="text-lg font-semibold text-purple-300 mb-4">Informatii Generale</h3>
                    <div class="space-y-3 text-sm">
                        @if($company->registration_date)
                            <div>
                                <p class="text-slate-400 text-xs uppercase">Data Inregistrarii</p>
                                <p class="text-white font-medium">{{ $company->registration_date->format('d M Y') }}</p>
                            </div>
                        @endif
                        @if($company->reg_com)
                            <div>
                                <p class="text-slate-400 text-xs uppercase">Numar Inregistrare</p>
                                <p class="text-white font-medium">{{ $company->reg_com }}</p>
                            </div>
                        @endif
                        @if($company->euid)
                            <div>
                                <p class="text-slate-400 text-xs uppercase">EUID</p>
                                <p class="text-white font-medium">{{ $company->euid }}</p>
                            </div>
                        @endif
                        @if($company->website)
                            <div>
                                <p class="text-slate-400 text-xs uppercase">Website</p>
                                <a href="{{ $company->website }}" target="_blank" rel="noopener noreferrer" class="text-blue-400 hover:text-blue-300">{{ $company->website }}</a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Address Info -->
                @if($company->address)
                    <div>
                        <h3 class="text-lg font-semibold text-purple-300 mb-4">Adresa</h3>
                        <div class="space-y-2 text-sm text-slate-300">
                            @if($company->address->street)
                                <p>{{ $company->address->street }} {{ $company->address->number }}</p>
                            @endif
                            <p>{{ $company->address->city }}, {{ $company->address->county }}</p>
                            @if($company->address->postalCode)
                                <p>Cod Postal: {{ $company->address->postalCode }}</p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Contact Info -->
            @if($company->info)
                <div class="mt-8 pt-8 border-t border-white/10">
                    <h3 class="text-lg font-semibold text-purple-300 mb-4">Contact</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        @if($company->info->phone)
                            <div>
                                <p class="text-slate-400 text-xs uppercase mb-1">Telefon</p>
                                <p class="text-white">{{ $company->info->phone }}</p>
                            </div>
                        @endif
                        @if($company->info->fax)
                            <div>
                                <p class="text-slate-400 text-xs uppercase mb-1">Fax</p>
                                <p class="text-white">{{ $company->info->fax }}</p>
                            </div>
                        @endif
                        @if($company->info->bankAccount)
                            <div>
                                <p class="text-slate-400 text-xs uppercase mb-1">Cont Bancar</p>
                                <p class="text-white font-mono text-xs">{{ $company->info->bankAccount }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- CAEN -->
            @if($company->caen->count() > 0)
                <div class="mt-8 pt-8 border-t border-white/10">
                    <h3 class="text-lg font-semibold text-purple-300 mb-4">Activitati (CAEN)</h3>
                    <div class="space-y-2">
                        @foreach($company->caen as $caen)
                            <div class="px-3 py-2 bg-white/5 border border-white/10 rounded-lg">
                                <p class="text-purple-300 font-semibold text-sm">{{ $caen->code }}</p>
                                @if($caen->details)
                                    <p class="text-slate-400 text-xs mt-1">{{ $caen->details->name }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Representatives -->
            @if($company->legalRepresentatives->count() > 0 || $company->naturalPersonRepresentatives->count() > 0)
                <div class="mt-8 pt-8 border-t border-white/10">
                    <h3 class="text-lg font-semibold text-purple-300 mb-4">Reprezentanti</h3>
                    <div class="space-y-2 text-sm">
                        @foreach($company->legalRepresentatives as $rep)
                            <div class="px-3 py-2 bg-white/5 border border-white/10 rounded-lg">
                                <p class="text-white font-medium">{{ $rep->name }}</p>
                                <p class="text-slate-400 text-xs">Legal</p>
                            </div>
                        @endforeach
                        @foreach($company->naturalPersonRepresentatives as $rep)
                            <div class="px-3 py-2 bg-white/5 border border-white/10 rounded-lg">
                                <p class="text-white font-medium">{{ $rep->name }}</p>
                                <p class="text-slate-400 text-xs">Persoana Fizica</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
