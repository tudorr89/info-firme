<div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900">
    <div class="max-w-4xl mx-auto">
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
    </div>
</div>
