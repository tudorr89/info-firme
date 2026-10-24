{{--
    Admin panel brand mark: the site's document glyph on the violet→indigo tile
    used in the public header. Self-contained SVG (no Tailwind classes) so it
    renders identically in light and dark mode, and survives Filament's
    compiled CSS not knowing about our utility classes.
--}}
<svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="lista-firme.info">
    <defs>
        <linearGradient id="lf-brand-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#8b5cf6" />
            <stop offset="100%" stop-color="#4f46e5" />
        </linearGradient>
    </defs>

    <rect width="48" height="48" rx="12" fill="url(#lf-brand-gradient)" />

    {{-- Folder / document --}}
    <path
        d="M13.5 19.2a2.7 2.7 0 0 1 2.7-2.7h5.1l2.6 2.8h9.4a2.7 2.7 0 0 1 2.7 2.7v9.8a2.7 2.7 0 0 1-2.7 2.7H16.2a2.7 2.7 0 0 1-2.7-2.7Z"
        fill="#ffffff"
        fill-opacity="0.95"
    />

    {{-- List lines --}}
    <g stroke="#6d28d9" stroke-width="2.1" stroke-linecap="round">
        <line x1="18.5" y1="24.4" x2="29.5" y2="24.4" />
        <line x1="18.5" y1="29" x2="26" y2="29" />
    </g>
</svg>
