<div class="min-h-screen flex flex-col justify-center px-4 sm:px-6 lg:px-8 py-16">
    <div class="max-w-3xl mx-auto w-full">
        <!-- Hero -->
        <div class="text-center mb-10">
            <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/5 border border-white/10 text-xs font-medium text-slate-300">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                Peste 3.9 milioane de firme românești
            </span>
            <h1 class="mt-6 text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-white">
                Caută orice <span class="text-transparent bg-clip-text bg-gradient-to-r from-violet-400 via-fuchsia-400 to-indigo-400">companie</span> din România
            </h1>
            <p class="mt-5 text-lg text-slate-400 max-w-xl mx-auto">
                Date oficiale despre CUI, adresă, reprezentanți legali și cod CAEN — direct din surse publice.
            </p>
        </div>

        <!-- Search -->
        <div class="relative">
            <div class="absolute -inset-1 bg-gradient-to-r from-violet-600/40 via-fuchsia-500/40 to-indigo-600/40 rounded-3xl blur-lg opacity-70"></div>

            <div class="relative bg-slate-900/80 backdrop-blur-xl border border-white/10 rounded-3xl shadow-2xl overflow-hidden">
                <div class="flex items-center gap-3 px-5 py-4">
                    <svg class="w-6 h-6 text-violet-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Caută după CUI sau nume de companie..."
                        class="flex-1 bg-transparent text-white placeholder-slate-500 focus:outline-none text-lg"
                        autocomplete="off"
                        autofocus
                    >

                    <svg wire:loading wire:target="search" class="w-5 h-5 text-violet-400 shrink-0 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>

                    @if($search)
                        <button
                            type="button"
                            wire:click="$set('search', '')"
                            wire:loading.remove
                            wire:target="search"
                            class="shrink-0 p-1.5 rounded-lg text-slate-500 hover:text-white hover:bg-white/10 transition-colors"
                            aria-label="Șterge căutarea"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    @endif
                </div>

                @if(count($topResults) > 0)
                    <div class="border-t border-white/10 divide-y divide-white/5 max-h-96 overflow-y-auto">
                        @foreach($topResults as $company)
                            <button
                                wire:click="selectCompany({{ $company->id }})"
                                class="w-full flex items-center justify-between gap-4 px-5 py-4 text-left hover:bg-white/5 transition-colors"
                            >
                                <div class="min-w-0">
                                    <p class="font-semibold text-white truncate">{{ $company->name }}</p>
                                    <p class="text-sm text-slate-400">CUI: {{ $company->cui }}</p>
                                </div>
                                <svg class="w-5 h-5 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        @endforeach
                    </div>
                @elseif(mb_strlen(trim($search)) >= 2)
                    <div wire:loading.remove wire:target="search" class="border-t border-white/10 px-5 py-6 text-center">
                        @if($tooGeneric)
                            <p class="text-sm text-slate-400">
                                „<span class="text-white">{{ $search }}</span>" este prea general.
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Adaugă un cuvânt din denumirea firmei sau caută după CUI.
                            </p>
                        @else
                            <p class="text-sm text-slate-400">
                                Nicio companie găsită pentru „<span class="text-white">{{ $search }}</span>".
                            </p>
                            @if(mb_strlen(trim($search)) < 3)
                                <p class="mt-1 text-xs text-slate-500">
                                    Pentru căutarea după nume sunt necesare minim 3 caractere.
                                </p>
                            @endif
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <p class="mt-6 text-center text-xs text-slate-500">
            Informațiile provin din surse publice oficiale (ONRC, ANAF) și au caracter informativ.
        </p>
    </div>
</div>
