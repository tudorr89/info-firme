@props([
    'title' => config('app.name') . ' - Căutare Companii',
    'description' => 'Caută și descoperă informații despre companii românești. Peste 3.9 milioane de firme cu date oficiale.',
    'type' => 'website',
    'company' => null,
    'canonical' => null,
])

<!-- Basic Meta Tags -->
<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">
@if($canonical)
    <link rel="canonical" href="{{ $canonical }}">
@endif

<!-- Robots -->
<meta name="robots" content="index, follow, max-image-preview:large">
<meta name="googlebot" content="index, follow">

<!-- Language & Locale -->
<meta property="og:locale" content="ro_RO">

<!-- Open Graph Meta Tags -->
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:type" content="{{ $type }}">
<meta property="og:site_name" content="lista-firme.info">
@if($canonical)
    <meta property="og:url" content="{{ $canonical }}">
@endif
<meta property="og:image" content="{{ asset('og-image.png') }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">

<!-- Twitter Card Meta Tags -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ asset('og-image.png') }}">

<!-- Schema.org Structured Data -->
@php
    if ($type === 'organization' && $company) {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $company->name,
            'url' => route('company.show', $company->cui),
            'identifier' => [
                '@type' => 'PropertyValue',
                'name' => 'CUI',
                'value' => $company->cui,
            ],
        ];

        if ($company->website) {
            $schema['sameAs'] = $company->website;
        }

        if ($company->registration_date) {
            $schema['foundingDate'] = $company->registration_date->format('Y-m-d');
        }

        if ($company->address) {
            $schema['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => trim(($company->address->street ?? '') . ' ' . ($company->address->number ?? '')),
                'addressLocality' => $company->address->city ?? '',
                'addressRegion' => $company->address->county ?? '',
                'postalCode' => $company->address->postalCode ?? '',
                'addressCountry' => 'RO',
            ];
        }

        if ($company->info && $company->info->phone) {
            $schema['telephone'] = $company->info->phone;
        }
    } else {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'lista-firme.info',
            'url' => url('/'),
            'description' => 'Căutare și descoperire de informații despre companii românești',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => url('/') . '?search={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }
@endphp
<script type="application/ld+json">
{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
