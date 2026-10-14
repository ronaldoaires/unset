@props([
    'title'       => null,
    'description' => null,
    'image'       => null,   // must be an absolute URL
    'canonical'   => null,
    'noindex'     => false,
    'type'        => 'website',
    'jsonLd'      => null,
    'suffix'      => true,   // appends " | Site name" to the title
])

@php
    $siteName    = config('app.name');
    $fullTitle   = $title ? ($suffix ? $title . ' | ' . $siteName : $title) : $siteName;
    $description = $description ? \Illuminate\Support\Str::limit(strip_tags($description), 160) : null;
    // url()->current() has no query string, so filtered/paginated URLs
    // canonicalize to the clean address unless a canonical is passed in.
    $canonicalUrl = $canonical ?? url()->current();
@endphp

<title>{{ $fullTitle }}</title>

@if ($description)
    <meta name="description" content="{{ $description }}">
@endif

@if ($noindex)
    <meta name="robots" content="noindex,follow">
@else
    <meta name="robots" content="index,follow,max-image-preview:large">
    <link rel="canonical" href="{{ $canonicalUrl }}">
@endif

{{-- Open Graph / Twitter: controls how the link looks when shared --}}
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:locale" content="pt_BR">
<meta property="og:type" content="{{ $type }}">
<meta property="og:title" content="{{ $fullTitle }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
@if ($description)
    <meta property="og:description" content="{{ $description }}">
@endif
@if ($image)
    <meta property="og:image" content="{{ $image }}">
@endif
<meta name="twitter:card" content="{{ $image ? 'summary_large_image' : 'summary' }}">

@if ($jsonLd)
    {{-- JSON_HEX_* flags keep user-provided text from closing the script tag --}}
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endif