<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    @php
        $siteName = \App\Models\Setting::where('key', 'site_name')->value('value') ?? 'PeptideSync';
        $siteDescription = \App\Models\Setting::where('key', 'site_description')->value('value') ?? 'Compare peptide brands, prices, and reviews';
    @endphp
    <title>{{ $siteName }}</title>
    <meta name="description" content="{{ $siteDescription }}">
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