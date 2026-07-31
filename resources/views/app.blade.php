<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Search engine verification -->
    <meta name="msvalidate.01" content="63538BD42BDE107E930BCBB0DB36B709" />
    @php
        $siteName = \App\Models\Setting::where('key', 'site_name')->value('value') ?? 'PeptideMap';
        $siteDescription = \App\Models\Setting::where('key', 'site_description')->value('value')
            ?? 'The definitive platform for research peptide vendors — compare verified suppliers, inspect lab testing, and discover research peptides in one place.';
        $contactEmail = \App\Models\Setting::where('key', 'contact_email')->value('value') ?? 'info@peptidemap.com';
        $canonicalHost = 'https://peptidemap.com';
        $defaultOgImage = $canonicalHost . '/images/og-default-v2.png';

        // Get SEO data from session (set by controllers via session(['page_seo_data' => ...]))
        $seoData = session('page_seo_data');
        $seoKey = null;
        if (is_array($seoData)) {
            $seoKey = $seoData['key'] ?? null;
            $seoTitle = $seoData['title'] ?? $siteName;
            $seoDescription = $seoData['description'] ?? $siteDescription;
            $seoUrl = $seoData['url'] ?? url()->current();
            $seoImage = $seoData['image'] ?? '';
            $seoOgTitle = $seoData['og_title'] ?? $seoTitle;
            $seoOgDescription = $seoData['og_description'] ?? $seoDescription;
            $seoOgImage = $seoData['og_image'] ?? $seoImage;
            $seoOgType = $seoData['og_type'] ?? 'website';
        } else {
            $seoTitle = $seoData?->title ?? $siteName;
            $seoDescription = $seoData?->description ?? $siteDescription;
            $seoUrl = $seoData?->url ?? url()->current();
            $seoImage = $seoData?->image ?? '';
            $seoOgTitle = $seoTitle;
            $seoOgDescription = $seoDescription;
            $seoOgImage = $seoImage;
            $seoOgType = 'website';
        }

        // Never leak dev.peptidemap.com / staging URLs into canonical + OG tags —
        // rewrite every URL to the apex host regardless of which host served the request.
        $normalizeUrl = function ($url) use ($canonicalHost) {
            if (!$url) return null;
            $parts = parse_url($url);
            if (!$parts) return $url;
            $path = ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');
            return $canonicalHost . $path;
        };
        $seoUrl = $normalizeUrl($seoUrl) ?? $canonicalHost;

        // Always emit an og:image — fall back to the site default so social
        // previews never break on pages that forgot to supply one.
        if (empty($seoOgImage)) $seoOgImage = $defaultOgImage;

        // Full browser title: append site name unless it's the home page OR the
        // controller-supplied title already contains the site name (older controllers
        // pre-appended "- PeptideMap" themselves; this prevents "…— PeptideMap — PeptideMap").
        $titleHasSiteName = stripos($seoTitle, $siteName) !== false;
        $fullTitle = ($seoKey === 'home' || $seoTitle === $siteName || $titleHasSiteName)
            ? $seoTitle
            : ($seoTitle . ' — ' . $siteName);

        session()->forget('page_seo_data');
    @endphp

    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">

    <!-- Canonical -->
    <link rel="canonical" href="{{ $seoUrl }}" />

    <!-- Open Graph -->
    <meta property="og:type" content="{{ $seoOgType }}" />
    <meta property="og:site_name" content="{{ $siteName }}" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:url" content="{{ $seoUrl }}" />
    <meta property="og:title" content="{{ $seoOgTitle }}" />
    <meta property="og:description" content="{{ $seoOgDescription }}" />
    <meta property="og:image" content="{{ $seoOgImage }}" />
    <meta property="og:image:width" content="1200" />
    <meta property="og:image:height" content="630" />

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:url" content="{{ $seoUrl }}" />
    <meta name="twitter:title" content="{{ $seoOgTitle }}" />
    <meta name="twitter:description" content="{{ $seoOgDescription }}" />
    <meta name="twitter:image" content="{{ $seoOgImage }}" />

    <!-- Contact + icons -->
    <meta name="contact" content="{{ $contactEmail }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
    <link rel="icon" type="image/png" sizes="180x180" href="/favicon-180.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon-180.png">

    <!-- Organization JSON-LD (rendered on every page) -->
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $siteName,
        'url' => $canonicalHost,
        'logo' => $canonicalHost . '/images/logo.png',
        'sameAs' => [],
        'contactPoint' => [
            '@type' => 'ContactPoint',
            'email' => $contactEmail,
            'contactType' => 'customer support',
        ],
    ], JSON_UNESCAPED_SLASHES) !!}
    </script>

    <!-- WebSite JSON-LD (enables the search sitelinks box in Google) -->
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $siteName,
        'url' => $canonicalHost,
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => $canonicalHost . '/products?q={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ],
    ], JSON_UNESCAPED_SLASHES) !!}
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    @vite(['resources/js/app.js', 'resources/css/app.css'])
    @inertiaHead
</head>
<body class="antialiased">
    @inertia
</body>
</html>
