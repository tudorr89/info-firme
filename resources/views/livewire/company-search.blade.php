<div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900">
    <div class="max-w-4xl mx-auto">
        @if(!$selectedCompany)
            <!-- Search View -->
            <div class="flex flex-col justify-center items-center min-h-[60vh]">
                <div class="w-full max-w-2xl">
                    <h1 class="text-4xl sm:text-5xl font-bold text-center bg-clip-text text-transparent bg-gradient-to-r from-purple-400 via-pink-400 to-blue-400 mb-12">
                        Cauta Companii
                    </h1>

                    <!-- Search Input -->
                    <div class="relative">
                        <div class="absolute inset-0 bg-gradient-to-r from-purple-500/20 via-pink-500/20 to-blue-500/20 rounded-2xl blur-xl opacity-75"></div>
                        <div class="relative bg-white/10 backdrop-blur-lg border border-white/20 rounded-2xl p-6 shadow-2xl">
                            <div class="flex items-center gap-4">
                                <svg class="w-6 h-6 text-purple-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                <input
                                    type="text"
                                    wire:model.live="search"
                                    placeholder="Cauta dupa CUI sau nume..."
                                    class="flex-1 bg-transparent text-white placeholder-slate-400 focus:outline-none text-lg"
                                    autocomplete="off"
                                    autofocus
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Dropdown Results -->
                    @if(count($topResults) > 0)
                        <div class="mt-2 bg-white/10 backdrop-blur-lg border border-white/20 rounded-xl overflow-hidden shadow-2xl">
                            @foreach($topResults as $company)
                                <button
                                    wire:click="selectCompany({{ $company->id }})"
                                    class="w-full text-left px-6 py-4 hover:bg-white/10 border-b border-white/10 last:border-b-0 transition-all duration-200 flex flex-col gap-1"
                                >
                                    <div class="font-semibold text-white">{{ $company->name }}</div>
                                    <div class="text-sm text-slate-400">CUI: {{ $company->cui }}</div>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @else
            <!-- Company Details View -->
            <div>
                <button
                    wire:click="goBack"
                    class="mb-6 px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg border border-white/20 transition-all"
                >
                    ← Inapoi
                </button>

                <div class="bg-white/10 backdrop-blur-lg border border-white/20 rounded-2xl p-8 shadow-2xl">
                    <!-- Header -->
                    <div class="mb-8">
                        <h1 class="text-3xl font-bold text-white mb-2">{{ $selectedCompany->name }}</h1>
                        <div class="flex gap-4 flex-wrap text-sm">
                            <span class="px-3 py-1 bg-purple-500/30 text-purple-200 rounded-full">CUI: {{ $selectedCompany->cui }}</span>
                            @if($selectedCompany->status)
                                <span class="px-3 py-1 bg-blue-500/30 text-blue-200 rounded-full">{{ $selectedCompany->status->details?->description ?? 'N/A' }}</span>
                            @endif
                            @if($selectedCompany->type)
                                <span class="px-3 py-1 bg-green-500/30 text-green-200 rounded-full">{{ $selectedCompany->type }}</span>
                            @endif
                        </div>
                    </div>

                    <!-- Grid Layout -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- General Info -->
                        <div>
                            <h3 class="text-lg font-semibold text-purple-300 mb-4">Informatii Generale</h3>
                            <div class="space-y-3 text-sm">
                                @if($selectedCompany->registration_date)
                                    <div>
                                        <p class="text-slate-400 text-xs uppercase">Data Inregistrarii</p>
                                        <p class="text-white font-medium">{{ $selectedCompany->registration_date->format('d M Y') }}</p>
                                    </div>
                                @endif
                                @if($selectedCompany->reg_com)
                                    <div>
                                        <p class="text-slate-400 text-xs uppercase">Numar Inregistrare</p>
                                        <p class="text-white font-medium">{{ $selectedCompany->reg_com }}</p>
                                    </div>
                                @endif
                                @if($selectedCompany->euid)
                                    <div>
                                        <p class="text-slate-400 text-xs uppercase">EUID</p>
                                        <p class="text-white font-medium">{{ $selectedCompany->euid }}</p>
                                    </div>
                                @endif
                                @if($selectedCompany->website)
                                    <div>
                                        <p class="text-slate-400 text-xs uppercase">Website</p>
                                        <a href="{{ $selectedCompany->website }}" target="_blank" class="text-blue-400 hover:text-blue-300">{{ $selectedCompany->website }}</a>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Address Info -->
                        @if($selectedCompany->address)
                            <div>
                                <h3 class="text-lg font-semibold text-purple-300 mb-4">Adresa</h3>
                                <div class="space-y-2 text-sm text-slate-300">
                                    @if($selectedCompany->address->street)
                                        <p>{{ $selectedCompany->address->street }} {{ $selectedCompany->address->number }}</p>
                                    @endif
                                    <p>{{ $selectedCompany->address->city }}, {{ $selectedCompany->address->county }}</p>
                                    @if($selectedCompany->address->postalCode)
                                        <p>Cod Postal: {{ $selectedCompany->address->postalCode }}</p>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Contact Info -->
                    @if($selectedCompany->info)
                        <div class="mt-8 pt-8 border-t border-white/10">
                            <h3 class="text-lg font-semibold text-purple-300 mb-4">Contact</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                @if($selectedCompany->info->phone)
                                    <div>
                                        <p class="text-slate-400 text-xs uppercase mb-1">Telefon</p>
                                        <p class="text-white">{{ $selectedCompany->info->phone }}</p>
                                    </div>
                                @endif
                                @if($selectedCompany->info->fax)
                                    <div>
                                        <p class="text-slate-400 text-xs uppercase mb-1">Fax</p>
                                        <p class="text-white">{{ $selectedCompany->info->fax }}</p>
                                    </div>
                                @endif
                                @if($selectedCompany->info->bankAccount)
                                    <div>
                                        <p class="text-slate-400 text-xs uppercase mb-1">Cont Bancar</p>
                                        <p class="text-white font-mono text-xs">{{ $selectedCompany->info->bankAccount }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- CAEN -->
                    @if($selectedCompany->caen->count() > 0)
                        <div class="mt-8 pt-8 border-t border-white/10">
                            <h3 class="text-lg font-semibold text-purple-300 mb-4">Activitati (CAEN)</h3>
                            <div class="space-y-2">
                                @foreach($selectedCompany->caen as $caen)
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
                    @if($selectedCompany->legalRepresentatives->count() > 0 || $selectedCompany->naturalPersonRepresentatives->count() > 0)
                        <div class="mt-8 pt-8 border-t border-white/10">
                            <h3 class="text-lg font-semibold text-purple-300 mb-4">Reprezentanti</h3>
                            <div class="space-y-2 text-sm">
                                @foreach($selectedCompany->legalRepresentatives as $rep)
                                    <div class="px-3 py-2 bg-white/5 border border-white/10 rounded-lg">
                                        <p class="text-white font-medium">{{ $rep->name }}</p>
                                        <p class="text-slate-400 text-xs">Legal</p>
                                    </div>
                                @endforeach
                                @foreach($selectedCompany->naturalPersonRepresentatives as $rep)
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
        @endif
    </div>
</div>
