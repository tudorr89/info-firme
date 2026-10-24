<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f172a">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">

    <!-- SEO Meta Tags -->
    @if(isset($seoMeta))
        {!! $seoMeta !!}
    @else
        @yield('seoMeta', view('components.seo-meta')->render())
    @endif

    <!-- Resource Hints for Performance -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="antialiased bg-slate-950 text-slate-200 selection:bg-violet-500/30 selection:text-white">
    <!-- Animated background -->
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(124,58,237,0.14),transparent_60%)]"></div>
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-violet-600/20 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute -bottom-40 -right-32 w-[28rem] h-[28rem] bg-indigo-600/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s"></div>
        <div class="absolute top-1/3 left-1/2 w-72 h-72 bg-fuchsia-600/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 4s"></div>
    </div>

    <!-- Header -->
    <header class="sticky top-0 z-50 border-b border-white/10 bg-slate-950/70 backdrop-blur-xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="{{ route('company.search') }}" class="flex items-center gap-3 group">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-indigo-600 shadow-lg shadow-violet-500/25 transition-shadow group-hover:shadow-violet-500/40">
                        <x-logo />
                    </span>
                    <span class="text-lg font-bold text-white tracking-tight">
                        lista<span class="text-transparent bg-clip-text bg-gradient-to-r from-violet-400 to-indigo-400">-firme</span>.info
                    </span>
                </a>

                <nav class="flex items-center gap-1">
                    <a href="{{ route('company.search') }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium text-slate-300 hover:text-white hover:bg-white/10 transition-colors">
                        Căutare
                    </a>
                    <a href="{{ route('legal.terms') }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium text-slate-300 hover:text-white hover:bg-white/10 transition-colors">
                        Termeni
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main content -->
    <main class="min-h-[calc(100vh-4rem)]">
        @yield('content', $slot ?? '')
    </main>

    <!-- Footer -->
    <footer class="border-t border-white/10 bg-slate-950/70 backdrop-blur-xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                <div>
                    <a href="{{ route('company.search') }}" class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-violet-500 to-indigo-600">
                            <x-logo />
                        </span>
                        <span class="text-white font-bold">lista-firme.info</span>
                    </a>
                    <p class="mt-4 text-sm text-slate-400 leading-relaxed">
                        Caută și descoperă informații publice despre companii românești: CUI, adresă, reprezentanți legali și cod CAEN.
                    </p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-white uppercase tracking-wider">Informații legale</h3>
                    <ul class="mt-4 space-y-2 text-sm text-slate-400">
                        <li>RTI Global Services SRL</li>
                        <li>CUI: 51859625</li>
                        <li>Nr. Reg. Com.: J2025037467000</li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-white uppercase tracking-wider">Link-uri</h3>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li>
                            <a href="{{ route('legal.terms') }}" class="text-slate-400 hover:text-white transition-colors">
                                Termeni și Condiții
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('legal.privacy') }}" class="text-slate-400 hover:text-white transition-colors">
                                Politica de Confidențialitate
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('cif-removal.create') }}" class="text-slate-400 hover:text-white transition-colors">
                                Cerere de ștergere CIF
                            </a>
                        </li>
                        <li>
                            <a href="mailto:office@hostingonthe.cloud" class="text-slate-400 hover:text-white transition-colors">
                                office@hostingonthe.cloud
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-10 pt-6 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-3">
                <p class="text-xs text-slate-500">
                    © {{ date('Y') }} lista-firme.info. Toate drepturile rezervate.
                </p>
                <p class="text-xs text-slate-500">
                    Date publice din surse oficiale. Nu suntem afiliați cu ANAF sau ONRC.
                </p>
            </div>
        </div>
    </footer>

    @livewireScripts

    <script>
        document.addEventListener('livewire:init', function() {
            Livewire.hook('request', ({ fail }) => {
                if (fail && fail.status === 419) {
                    alert('Your session expired');
                }
            });
        });
    </script>
</body>

</html>
