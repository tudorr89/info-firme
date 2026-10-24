<div class="px-4 sm:px-6 lg:px-8 py-12">
    <div class="max-w-2xl mx-auto">
        <div class="mb-10">
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">
                Cerere de <span class="text-transparent bg-clip-text bg-gradient-to-r from-violet-400 to-indigo-400">ștergere</span> a unui CIF
            </h1>
            <p class="mt-4 text-slate-400 leading-relaxed">
                Folosește acest formular dacă vrei ca firma ta să nu mai apară pe lista-firme.info.
                Cererea este analizată de operator, iar dacă este aprobată, firma dispare din căutare,
                din paginile publice și din API.
            </p>
        </div>

        @if($submitted)
            <div class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-6">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <h2 class="text-lg font-semibold text-white">Cererea a fost înregistrată</h2>
                        <p class="mt-2 text-sm text-slate-300 leading-relaxed">
                            Îți mulțumim. Analizăm cererea și te contactăm pe adresa de e-mail furnizată.
                            Dacă cererea este aprobată, CIF-ul nu va mai fi afișat.
                        </p>
                    </div>
                </div>
            </div>
        @else
            <form wire:submit="submit" class="rounded-3xl border border-white/10 bg-slate-900/80 backdrop-blur-xl p-6 sm:p-8 shadow-2xl space-y-6">
                {{-- Bot trap --}}
                <div class="hidden" aria-hidden="true">
                    <label for="website">Website</label>
                    <input type="text" id="website" wire:model="website" tabindex="-1" autocomplete="off">
                </div>

                {{-- CUI --}}
                <div>
                    <label for="cui" class="block text-sm font-medium text-slate-200">
                        CUI / CIF <span class="text-red-400">*</span>
                    </label>
                    <input
                        type="text"
                        id="cui"
                        wire:model.live.debounce.400ms="cui"
                        inputmode="numeric"
                        placeholder="Ex: 12345678"
                        class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-white placeholder-slate-500 focus:border-violet-400/60 focus:outline-none focus:ring-2 focus:ring-violet-500/30"
                    >
                    @if($companyName)
                        <p class="mt-2 text-sm text-slate-400">
                            Firmă identificată: <span class="text-white font-medium">{{ $companyName }}</span>
                        </p>
                    @endif
                    @error('cui')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Nume --}}
                <div>
                    <label for="requesterName" class="block text-sm font-medium text-slate-200">
                        Nume și prenume <span class="text-red-400">*</span>
                    </label>
                    <input
                        type="text"
                        id="requesterName"
                        wire:model="requesterName"
                        autocomplete="name"
                        class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-white placeholder-slate-500 focus:border-violet-400/60 focus:outline-none focus:ring-2 focus:ring-violet-500/30"
                    >
                    @error('requesterName')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label for="requesterEmail" class="block text-sm font-medium text-slate-200">
                        Adresă de e-mail <span class="text-red-400">*</span>
                    </label>
                    <input
                        type="email"
                        id="requesterEmail"
                        wire:model="requesterEmail"
                        autocomplete="email"
                        placeholder="nume@exemplu.ro"
                        class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-white placeholder-slate-500 focus:border-violet-400/60 focus:outline-none focus:ring-2 focus:ring-violet-500/30"
                    >
                    @error('requesterEmail')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Motiv --}}
                <div>
                    <label for="reason" class="block text-sm font-medium text-slate-200">
                        Motivul cererii <span class="text-red-400">*</span>
                    </label>
                    <textarea
                        id="reason"
                        wire:model="reason"
                        rows="6"
                        placeholder="Explică pe scurt de ce soliciți eliminarea acestei firme."
                        class="mt-2 w-full rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-white placeholder-slate-500 focus:border-violet-400/60 focus:outline-none focus:ring-2 focus:ring-violet-500/30"
                    ></textarea>
                    <p class="mt-1 text-xs text-slate-500">Minim 20 de caractere.</p>
                    @error('reason')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Declaratie --}}
                <div>
                    <label for="declaration" class="flex items-start gap-3 text-sm text-slate-300">
                        <input
                            type="checkbox"
                            id="declaration"
                            wire:model="declaration"
                            class="mt-0.5 h-4 w-4 rounded border-white/20 bg-white/5 text-violet-500 focus:ring-violet-500/40"
                        >
                        <span>
                            Declar pe propria răspundere că sunt îndreptățit să solicit eliminarea acestui
                            CIF (reprezint firmă sau împuternicitul ei) și că datele furnizate sunt corecte.
                        </span>
                    </label>
                    @error('declaration')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-violet-500/25 transition hover:from-violet-500 hover:to-indigo-500 focus:outline-none focus:ring-2 focus:ring-violet-500/50"
                >
                    <svg wire:loading wire:target="submit" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    Trimite cererea
                </button>

                <p class="text-xs text-slate-500 leading-relaxed">
                    Datele transmise (CUI, nume, e-mail, motiv și adresa IP) sunt folosite exclusiv pentru
                    soluționarea acestei cereri, conform
                    <a href="{{ route('legal.privacy') }}" class="text-violet-300 hover:text-violet-200 transition-colors">Politicii de Confidențialitate</a>.
                </p>
            </form>
        @endif

        <div class="mt-8 rounded-2xl border border-white/10 bg-white/5 p-6 text-sm text-slate-400 leading-relaxed">
            <h2 class="text-base font-semibold text-white mb-3">Ce se întâmplă după trimitere</h2>
            <ol class="list-decimal list-inside space-y-2">
                <li>Cererea este înregistrată și analizată de operator.</li>
                <li>Primești un răspuns pe adresa de e-mail furnizată.</li>
                <li>Dacă cererea este aprobată, firma nu mai apare în căutare, pe paginile publice sau în API.</li>
            </ol>
            <p class="mt-4">
                Pentru întrebări ne poți scrie la
                <a href="mailto:office@hostingonthe.cloud" class="text-violet-300 hover:text-violet-200 transition-colors">office@hostingonthe.cloud</a>.
            </p>
        </div>
    </div>
</div>
