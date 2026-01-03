<div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8 flex flex-col">
    <div class="max-w-7xl mx-auto w-full flex-1 flex flex-col">
        @php
            $hasSearch = $search || count(array_filter($filters)) > 0;
        @endphp

        <!-- Hero Section -->
        <div @class(['mb-12' => $hasSearch, 'flex-1 flex flex-col justify-center items-center' => !$hasSearch])>
            @if(!$hasSearch)
                <!-- Centered Search Page -->
                <div class="w-full max-w-2xl mx-auto">
                    <div class="text-center mb-8">
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-purple-400 via-pink-400 to-blue-400 mb-4">
                            Descoperă Companiile
                        </h1>
                        <p class="text-lg text-slate-400 mb-12">
                            Caută printre milioane de companii prin CUI, nume sau filtre avansate
                        </p>
                    </div>

                    <!-- Search Bar with Glassmorphism -->
                    <form wire:submit="search" class="relative">
                        <div class="absolute inset-0 bg-gradient-to-r from-purple-500/20 via-pink-500/20 to-blue-500/20 rounded-2xl blur-xl opacity-75"></div>
                        <div class="relative bg-white/10 backdrop-blur-lg border border-white/20 rounded-2xl p-6 shadow-2xl">
                            <div class="flex items-center gap-4">
                                <svg class="w-6 h-6 text-purple-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                <input
                                    type="text"
                                    name="search"
                                    wire:model="search"
                                    placeholder="Caută după CUI sau nume companie..."
                                    class="flex-1 bg-transparent text-white placeholder-slate-400 focus:outline-none text-lg"
                                    autocomplete="off"
                                    autofocus
                                    @keydown.enter="$wire.search()"
                                >
                                @if($search)
                                    <a href="/" class="p-2 hover:bg-white/10 rounded-lg transition-all duration-200">
                                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </a>
                                @endif
                                <button
                                    type="submit"
                                    class="p-2 hover:bg-white/10 rounded-lg transition-all duration-200"
                                    title="Cauta"
                                >
                                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            @else
                <!-- Search with Results Page -->
                <div class="text-center mb-8">
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-purple-400 via-pink-400 to-blue-400 mb-4">
                        Descoperă Companiile
                    </h1>
                    <p class="text-lg text-slate-400 mb-8">
                        Caută printre milioane de companii prin CUI, nume sau filtre avansate
                    </p>
                </div>

                <!-- Search Bar with Glassmorphism -->
                <form wire:submit="search" class="relative mb-6">
                    <div class="absolute inset-0 bg-gradient-to-r from-purple-500/20 via-pink-500/20 to-blue-500/20 rounded-2xl blur-xl opacity-75"></div>
                    <div class="relative bg-white/10 backdrop-blur-lg border border-white/20 rounded-2xl p-6 shadow-2xl">
                        <div class="flex items-center gap-4">
                            <svg class="w-6 h-6 text-purple-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <input
                                type="text"
                                name="search"
                                wire:model="search"
                                placeholder="Caută după CUI sau nume companie..."
                                class="flex-1 bg-transparent text-white placeholder-slate-400 focus:outline-none text-lg"
                                autocomplete="off"
                                @keydown.enter="$wire.search()"
                            >
                            @if($search)
                                <a href="/" class="p-2 hover:bg-white/10 rounded-lg transition-all duration-200">
                                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </a>
                            @endif
                            <button
                                type="submit"
                                class="p-2 hover:bg-white/10 rounded-lg transition-all duration-200"
                                title="Cauta"
                            >
                                <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Filter Toggle Button -->
                <div class="flex gap-3">
                    <button
                        @click="$dispatch('toggle-filters')"
                        class="px-6 py-3 bg-white/10 backdrop-blur-lg border border-white/20 rounded-xl hover:bg-white/20 transition-all duration-200 text-white font-medium flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                        </svg>
                        Filtre
                        @if(count(array_filter($filters)) > 0)
                            <span class="ml-2 px-2 py-1 bg-red-500/80 rounded-full text-xs font-semibold">{{ count(array_filter($filters)) }}</span>
                        @endif
                    </button>

                    @if(count(array_filter($filters)) > 0)
                        <button
                            wire:click="clearFilters"
                            class="px-4 py-3 text-red-400 hover:bg-red-500/10 rounded-xl transition-all duration-200 font-medium"
                            type="button"
                        >
                            Șterge Filtrele
                        </button>
                    @endif
                </div>
            @endif
        </div>

        @if($hasSearch)

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Filters Sidebar (Collapsible on mobile) -->
            <div
                @toggle-filters="open = !open"
                @click.away="open = false"
                x-data="{ open: false }"
                class="lg:col-span-1"
            >
                <div class="sticky top-6 hidden lg:block">
                    <div class="bg-white/10 backdrop-blur-lg border border-white/20 rounded-2xl p-6 shadow-2xl">
                        <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                            </svg>
                            Filtre
                        </h3>

                        <!-- Status Filter -->
                        <div class="mb-6">
                            <label class="text-sm font-semibold text-white mb-3 block">Stare</label>
                            <div class="space-y-2">
                                <label class="flex items-center gap-3 cursor-pointer hover:bg-white/10 p-2 rounded-lg transition-all">
                                    <input
                                        type="checkbox"
                                        value="Active"
                                        wire:model.live="filters.status"
                                        class="w-4 h-4 rounded border-white/20 bg-white/5 text-purple-500 focus:ring-purple-500"
                                    >
                                    <span class="text-slate-300">Activ</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer hover:bg-white/10 p-2 rounded-lg transition-all">
                                    <input
                                        type="checkbox"
                                        value="Inactive"
                                        wire:model.live="filters.status"
                                        class="w-4 h-4 rounded border-white/20 bg-white/5 text-purple-500 focus:ring-purple-500"
                                    >
                                    <span class="text-slate-300">Inactiv</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer hover:bg-white/10 p-2 rounded-lg transition-all">
                                    <input
                                        type="checkbox"
                                        value="Suspended"
                                        wire:model.live="filters.status"
                                        class="w-4 h-4 rounded border-white/20 bg-white/5 text-purple-500 focus:ring-purple-500"
                                    >
                                    <span class="text-slate-300">Suspendat</span>
                                </label>
                            </div>
                        </div>

                        <!-- Date Range Filter -->
                        <div class="mb-6">
                            <label class="text-sm font-semibold text-white mb-3 block">Data Înregistrării</label>
                            <div class="space-y-2">
                                <input
                                    type="date"
                                    wire:model.live="filters.dateFrom"
                                    class="w-full px-3 py-2 bg-white/5 border border-white/20 rounded-lg text-slate-300 text-sm focus:outline-none focus:border-purple-500"
                                    placeholder="De la"
                                >
                                <input
                                    type="date"
                                    wire:model.live="filters.dateTo"
                                    class="w-full px-3 py-2 bg-white/5 border border-white/20 rounded-lg text-slate-300 text-sm focus:outline-none focus:border-purple-500"
                                    placeholder="Până la"
                                >
                            </div>
                        </div>

                        <!-- County Filter -->
                        <div class="mb-6">
                            <label class="text-sm font-semibold text-white mb-3 block">Județ</label>
                            <select
                                wire:model.live="filters.county"
                                class="w-full px-3 py-2 bg-white/5 border border-white/20 rounded-lg text-slate-300 text-sm focus:outline-none focus:border-purple-500"
                            >
                                <option value="">Toate Județele</option>
                                <option value="Bucuresti">Bucuresti</option>
                                <option value="Ilfov">Ilfov</option>
                                <option value="Timis">Timis</option>
                                <option value="Cluj">Cluj</option>
                                <option value="Constanta">Constanta</option>
                                <option value="Brasov">Brasov</option>
                                <option value="Sibiu">Sibiu</option>
                                <option value="Galati">Galati</option>
                                <option value="Bihor">Bihor</option>
                                <option value="Dolj">Dolj</option>
                            </select>
                        </div>

                        <!-- CAEN Filter -->
                        <div>
                            <label class="text-sm font-semibold text-white mb-3 block">Cod CAEN</label>
                            <input
                                type="text"
                                wire:model.live="filters.caen.0"
                                placeholder="ex: 6201"
                                class="w-full px-3 py-2 bg-white/5 border border-white/20 rounded-lg text-slate-300 text-sm focus:outline-none focus:border-purple-500"
                            >
                        </div>
                    </div>
                </div>

                <!-- Mobile Filters Modal -->
                <div
                    x-show="open"
                    @click.self="open = false"
                    class="fixed inset-0 bg-black/50 z-40 lg:hidden"
                    x-transition
                >
                    <div
                        class="fixed right-0 top-0 h-full w-80 bg-slate-900 border-l border-white/20 overflow-y-auto z-50 p-6"
                        @click.stop
                    >
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-bold text-white">Filtre</h3>
                            <button @click="open = false" class="text-slate-400 hover:text-white">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Repeat filter sections here for mobile -->
                        <div class="space-y-6">
                            <!-- Status -->
                            <div>
                                <label class="text-sm font-semibold text-white mb-3 block">Stare</label>
                                <div class="space-y-2">
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            value="Active"
                                            wire:model.live="filters.status"
                                            class="w-4 h-4 rounded border-white/20 bg-white/5 text-purple-500"
                                        >
                                        <span class="text-slate-300">Activ</span>
                                    </label>
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            value="Inactive"
                                            wire:model.live="filters.status"
                                            class="w-4 h-4 rounded border-white/20 bg-white/5 text-purple-500"
                                        >
                                        <span class="text-slate-300">Inactiv</span>
                                    </label>
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            value="Suspended"
                                            wire:model.live="filters.status"
                                            class="w-4 h-4 rounded border-white/20 bg-white/5 text-purple-500"
                                        >
                                        <span class="text-slate-300">Suspendat</span>
                                    </label>
                                </div>
                            </div>

                            <!-- County -->
                            <div>
                                <label class="text-sm font-semibold text-white mb-3 block">Județ</label>
                                <select
                                    wire:model.live="filters.county"
                                    class="w-full px-3 py-2 bg-white/5 border border-white/20 rounded-lg text-slate-300 text-sm"
                                >
                                    <option value="">Toate Județele</option>
                                    <option value="Bucuresti">Bucuresti</option>
                                    <option value="Ilfov">Ilfov</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results Section -->
            <div class="lg:col-span-3">
                <!-- Results Information -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        @if($results->count() > 0)
                            <p class="text-slate-400">
                                Se afișează <span class="font-semibold text-white">{{ ($results->currentPage() - 1) * 20 + 1 }}</span> până la
                                <span class="font-semibold text-white">{{ min($results->currentPage() * 20, $results->total()) }}</span> din
                                <span class="font-semibold text-white">{{ $results->total() }}</span> companii
                            </p>
                        @elseif($search || count(array_filter($filters)) > 0)
                            <p class="text-slate-400">Nicio companie găsită</p>
                        @else
                            <p class="text-slate-500 italic">Introduceți un termen de căutare sau aplicați filtre pentru a găsi companii</p>
                        @endif
                    </div>
                </div>

                <!-- Results Grid -->
                <div class="space-y-4">
                    @forelse($results as $company)
                        <div
                            class="group bg-white/10 backdrop-blur-lg border border-white/20 rounded-2xl overflow-hidden hover:bg-white/20 transition-all duration-300 hover:shadow-2xl hover:shadow-purple-500/10"
                        >
                            <!-- Card Header (Always Visible) -->
                            <div
                                wire:click="toggleExpand({{ $company->id }})"
                                class="p-6 cursor-pointer"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-lg font-bold text-white mb-2 truncate hover:text-purple-300 transition-colors">
                                            {{ $company->name }}
                                        </h3>
                                        <div class="flex flex-wrap items-center gap-3 mb-3">
                                            <span class="inline-block px-3 py-1 bg-purple-500/30 text-purple-200 text-xs font-semibold rounded-full">
                                                CUI: {{ $company->cui }}
                                            </span>
                                            <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full bg-blue-500/30 text-blue-200">
                                                {{ $company->status?->details?->description ?? 'Unknown' }}
                                            </span>
                                        </div>
                                        <div class="flex flex-wrap gap-4 text-sm text-slate-400">
                                            @if($company->address)
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    </svg>
                                                    <span>{{ $company->address->city ?? 'N/A' }}, {{ $company->address->county ?? 'N/A' }}</span>
                                                </div>
                                            @endif
                                            @if($company->registration_date)
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                    <span>
                                                        @if(is_string($company->registration_date))
                                                            {{ \Carbon\Carbon::parse($company->registration_date)->format('M d, Y') }}
                                                        @else
                                                            {{ $company->registration_date->format('M d, Y') }}
                                                        @endif
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <button
                                        wire:click="toggleExpand({{ $company->id }})"
                                        class="ml-4 flex-shrink-0 p-2 hover:bg-white/10 rounded-lg transition-all duration-200"
                                    >
                                        <svg
                                            class="w-6 h-6 text-purple-400 transition-transform duration-300"
                                            :class="{ 'rotate-180': @js($expandedCompany === $company->id) }"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Expanded Details -->
                            @if($expandedCompany === $company->id)
                                <div
                                    class="border-t border-white/10 px-6 py-6 bg-white/5"
                                    x-transition
                                >
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <!-- Basic Information -->
                                        <div>
                                            <h4 class="text-sm font-bold text-purple-300 mb-4 flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                Informații Generale
                                            </h4>
                                            <div class="space-y-3 text-sm">
                                                @if($company->reg_com)
                                                    <div>
                                                        <p class="text-slate-500 text-xs uppercase">Numărul Înregistrării</p>
                                                        <p class="text-white font-medium">{{ $company->reg_com }}</p>
                                                    </div>
                                                @endif
                                                @if($company->euid)
                                                    <div>
                                                        <p class="text-slate-500 text-xs uppercase">EUID</p>
                                                        <p class="text-white font-medium">{{ $company->euid }}</p>
                                                    </div>
                                                @endif
                                                @if($company->type)
                                                    <div>
                                                        <p class="text-slate-500 text-xs uppercase">Tip</p>
                                                        <p class="text-white font-medium">{{ $company->type }}</p>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Address Information -->
                                        @if($company->address)
                                            <div>
                                                <h4 class="text-sm font-bold text-purple-300 mb-4 flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    </svg>
                                                    Adresă
                                                </h4>
                                                <div class="space-y-3 text-sm">
                                                    @if($company->address->street || $company->address->number)
                                                        <div>
                                                            <p class="text-slate-500 text-xs uppercase">Strada</p>
                                                            <p class="text-white font-medium">{{ $company->address->street }} {{ $company->address->number }}</p>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <p class="text-slate-500 text-xs uppercase">Oraș</p>
                                                        <p class="text-white font-medium">{{ $company->address->city }}, {{ $company->address->county }}</p>
                                                    </div>
                                                    @if($company->address->postalCode)
                                                        <div>
                                                            <p class="text-slate-500 text-xs uppercase">Cod Poștal</p>
                                                            <p class="text-white font-medium">{{ $company->address->postalCode }}</p>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif

                                        <!-- Contact Information -->
                                        @if($company->info)
                                            <div>
                                                <h4 class="text-sm font-bold text-purple-300 mb-4 flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                    </svg>
                                                    Contact
                                                </h4>
                                                <div class="space-y-3 text-sm">
                                                    @if($company->info->phone)
                                                        <div>
                                                            <p class="text-slate-500 text-xs uppercase">Telefon</p>
                                                            <p class="text-white font-medium">{{ $company->info->phone }}</p>
                                                        </div>
                                                    @endif
                                                    @if($company->info->fax)
                                                        <div>
                                                            <p class="text-slate-500 text-xs uppercase">Fax</p>
                                                            <p class="text-white font-medium">{{ $company->info->fax }}</p>
                                                        </div>
                                                    @endif
                                                    @if($company->info->bankAccount)
                                                        <div>
                                                            <p class="text-slate-500 text-xs uppercase">Cont Bancar</p>
                                                            <p class="text-white font-medium font-mono text-xs">{{ $company->info->bankAccount }}</p>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif

                                        <!-- CAEN Classifications -->
                                        @if($company->caen->count() > 0)
                                            <div>
                                                <h4 class="text-sm font-bold text-purple-300 mb-4 flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                                    </svg>
                                                    Activități (CAEN)
                                                </h4>
                                                <div class="space-y-2">
                                                    @foreach($company->caen as $caen)
                                                        <button
                                                            wire:click="filterByCaen({{ $caen->code }})"
                                                            class="w-full text-left px-3 py-2 bg-white/5 border border-white/10 rounded-lg hover:bg-white/10 hover:border-purple-500/50 transition-all duration-200 cursor-pointer group"
                                                        >
                                                            <div class="flex items-start justify-between gap-2">
                                                                <div class="flex-1">
                                                                    <div class="flex items-center gap-2">
                                                                        <p class="text-purple-400 font-semibold text-sm group-hover:text-purple-300 transition-colors">
                                                                            {{ $caen->code }}
                                                                        </p>
                                                                        <span class="text-xs text-slate-500 bg-white/5 px-2 py-1 rounded">v{{ $caen->version }}</span>
                                                                    </div>
                                                                    @php
                                                                        $caenDetails = $caen->caen_details;
                                                                    @endphp
                                                                    @if($caenDetails && $caenDetails->name)
                                                                        <p class="text-slate-400 text-xs mt-1 group-hover:text-slate-300 transition-colors line-clamp-2">
                                                                            {{ $caenDetails->name }}
                                                                        </p>
                                                                    @else
                                                                        <p class="text-slate-500 text-xs mt-1 italic">
                                                                            Detalii CAEN nu sunt disponibile
                                                                        </p>
                                                                    @endif
                                                                </div>
                                                                <svg class="w-4 h-4 text-purple-400 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 9l3 3m0 0l-3 3m3-3H8m13 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                                </svg>
                                                            </div>
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        <!-- Representatives -->
                                        @if($company->legalRepresentatives->count() > 0 || $company->naturalPersonRepresentatives->count() > 0)
                                            <div>
                                                <h4 class="text-sm font-bold text-purple-300 mb-4 flex items-center gap-2">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 12H9m6 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    Reprezentanți
                                                </h4>
                                                <div class="space-y-2 text-sm">
                                                    @foreach($company->legalRepresentatives as $rep)
                                                        <div class="px-3 py-2 bg-white/5 border border-white/10 rounded-lg">
                                                            <p class="text-white font-medium">{{ $rep->name ?? 'N/A' }}</p>
                                                            <p class="text-slate-400 text-xs">Reprezentant Legal</p>
                                                        </div>
                                                    @endforeach
                                                    @foreach($company->naturalPersonRepresentatives as $rep)
                                                        <div class="px-3 py-2 bg-white/5 border border-white/10 rounded-lg">
                                                            <p class="text-white font-medium">{{ $rep->name ?? 'N/A' }}</p>
                                                            <p class="text-slate-400 text-xs">Reprezentant Persoană Fizică</p>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- EU Branches (if any) -->
                                    @if($company->euBranches->count() > 0)
                                        <div class="mt-6 pt-6 border-t border-white/10">
                                            <h4 class="text-sm font-bold text-purple-300 mb-4 flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20H7m6-4h6v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                                                </svg>
                                                Sucursale UE
                                            </h4>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                @foreach($company->euBranches as $branch)
                                                    <div class="px-3 py-2 bg-white/5 border border-white/10 rounded-lg text-sm">
                                                        <p class="text-white font-medium">{{ $branch->country ?? 'Țară Necunoscută' }}</p>
                                                        <p class="text-slate-400 text-xs mt-1">ID Sucursală: {{ $branch->eu_branch_id ?? 'N/A' }}</p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @empty
                        <!-- Empty State -->
                        <div class="flex flex-col items-center justify-center py-20">
                            <svg class="w-20 h-20 text-slate-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <h3 class="text-xl font-bold text-white mb-2">Nicio companie găsită</h3>
                            <p class="text-slate-400 text-center max-w-sm">
                                @if($search)
                                    Încearcă să ajustezi căutarea sau filtrele. Am căutat "{{ $search }}" dar nu am găsit rezultate.
                                @else
                                    Începe prin a căuta o companie după nume sau CUI, sau folosiți filtrele pentru a restrânge căutarea.
                                @endif
                            </p>
                        </div>
                    @endforelse
                </div>

                <!-- Pagination -->
                @if($results->count() > 0)
                    <div class="mt-10 flex justify-center">
                        <div class="bg-white/10 backdrop-blur-lg border border-white/20 rounded-xl p-4">
                            {{ $results->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
