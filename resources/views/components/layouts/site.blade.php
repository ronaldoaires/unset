@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'canonical' => null,
    'noindex' => false,
    'type' => 'website',
    'jsonLd' => null,
    'suffix' => true,
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

    <x-site.seo
        :title="$title"
        :description="$description"
        :image="$image"
        :canonical="$canonical"
        :noindex="$noindex"
        :type="$type"
        :json-ld="$jsonLd"
        :suffix="$suffix" />

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

{{-- The bottom padding keeps content and footer clear of the mobile bottom bar. --}}
<body class="flex min-h-screen flex-col bg-canvas pb-20 text-ink antialiased md:pb-0">

    <x-site.header />
    <x-site.verify-banner />
    <x-site.flash />

    <main class="flex-1">
        {{ $slot }}
    </main>

    <x-site.footer />
    <x-site.bottom-nav />
</body>

</html>