<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#DC2626">
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

<body class="antialiased bg-gradient-to-b from-slate-900 via-purple-900 to-slate-900 text-slate-300 dark:text-slate-300">
    <!-- Animated background gradient -->
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute -top-1/2 -left-1/2 w-full h-full bg-gradient-to-br from-purple-500/20 via-transparent to-transparent rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute -bottom-1/2 -right-1/2 w-full h-full bg-gradient-to-tl from-blue-500/20 via-transparent to-transparent rounded-full blur-3xl animate-pulse" style="animation-delay: 1s"></div>
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-gradient-to-r from-pink-500/10 via-transparent to-transparent rounded-full blur-3xl animate-pulse" style="animation-delay: 2s"></div>
    </div>

    <!-- Header with Logo -->
    <div class="fixed top-0 left-0 right-0 z-50 border-b border-white/10 backdrop-blur-md bg-slate-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between">
            <a href="/" class="flex items-center gap-3 hover:opacity-80 transition-opacity">
                <x-logo />
                <span class="text-xl font-bold text-white">
                    lista-firme.info
                </span>
            </a>
        </div>
    </div>

    <!-- Main content with top padding for fixed header -->
    <div class="pt-16">
        @yield('content', $slot ?? '')
    </div>

    <!-- Footer -->
    <footer class="border-t border-white/10 bg-slate-900/50 backdrop-blur-md mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex flex-col items-center sm:items-start gap-1">
                    <p class="text-sm text-slate-400">
                        © 2026 lista-firme.info. Toate drepturile rezervate.
                    </p>
                    <p class="text-sm text-slate-500">
                        Făcut cu <span class="text-red-400">❤</span> de
                        <a href="mailto:tudorr89@gmail.com" class="text-purple-400 hover:text-purple-300 transition-colors">tudor</a>
                    </p>
                </div>
                <a href="https://github.com/tudorr89/info-firme" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg hover:bg-white/10 transition-colors text-slate-300 hover:text-white text-sm">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.49.5.092.682-.217.682-.482 0-.237-.008-.866-.013-1.7-2.782.603-3.369-1.343-3.369-1.343-.454-1.156-1.11-1.463-1.11-1.463-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.545 2.914 1.209.092-.937.349-1.546.635-1.903-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.025A9.578 9.578 0 0112 6.836c.85.004 1.705.114 2.504.336 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                    </svg>
                    GitHub
                </a>
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
