<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    @php
        $siteName = \App\Models\Setting::where('key', 'site_name')->value('value') ?? 'PeptideSync';
        $siteDescription = \App\Models\Setting::where('key', 'site_description')->value('value') ?? 'Compare peptide brands, prices, and reviews';
        $contactEmail = \App\Models\Setting::where('key', 'contact_email')->value('value') ?? 'contact@peptidemaps.com';
        
        // Get SEO data from session if available
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
        } else {
            $seoTitle = $seoData?->title ?? $siteName;
            $seoDescription = $seoData?->description ?? $siteDescription;
            $seoUrl = $seoData?->url ?? url()->current();
            $seoImage = $seoData?->image ?? '';
            $seoOgTitle = $seoTitle;
            $seoOgDescription = $seoDescription;
            $seoOgImage = $seoImage;
        }

        // Build final browser title: append site name for all pages except home
        $fullTitle = ($seoKey === 'home') ? $seoTitle : ($seoTitle . ' - ' . $siteName);

        // Prevent stale SEO data leaking into later requests that don't set it
        session()->forget('page_seo_data');
    @endphp
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ $seoUrl }}" />
    <meta property="og:title" content="{{ $seoOgTitle }}" />
    <meta property="og:description" content="{{ $seoOgDescription }}" />
    @if($seoOgImage)
    <meta property="og:image" content="{{ $seoOgImage }}" />
    @endif
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:url" content="{{ $seoUrl }}" />
    <meta name="twitter:title" content="{{ $seoOgTitle }}" />
    <meta name="twitter:description" content="{{ $seoOgDescription }}" />
    @if($seoOgImage)
    <meta name="twitter:image" content="{{ $seoOgImage }}" />
    @endif
    
    <!-- Canonical URL -->
    <link rel="canonical" href="{{ $seoUrl }}" />
    
    <!-- Contact Email -->
    <meta name="contact" content="{{ $contactEmail }}" />
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
    <link rel="icon" type="image/png" sizes="180x180" href="/favicon-180.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon-180.png">
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