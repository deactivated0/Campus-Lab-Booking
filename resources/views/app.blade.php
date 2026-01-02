<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @routes

        @php
            $vite_manifest = public_path('build/manifest.json');
            $vite_available = file_exists($vite_manifest) && is_readable($vite_manifest);
        @endphp

        @if ($vite_available)
            @php
                try {
                    // Use the Vite helper directly inside a try/catch so an invalid or
                    // unreadable manifest doesn't throw a fatal exception to the user.
                    echo app('Illuminate\Foundation\Vite')(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"]);
                } catch (\Throwable $e) {
                    $vite_available = false;
                    if (config('app.debug')) {
                        echo '<script>console.warn("Vite manifest error: '.htmlspecialchars($e->getMessage(), ENT_QUOTES).'");</script>';
                    }
                }
            @endphp
        @endif

        @if (! $vite_available)
            {{-- Fallback to simple static assets when Vite manifest is not present or invalid --}}
            <link rel="stylesheet" href="{{ asset('css/app.css') }}">
            <script src="{{ asset('js/app.js') }}" defer></script>

            @if (config('app.debug'))
                <script>console.warn('Vite manifest not found at public/build/manifest.json. Run "npm run dev" or "npm run build" to generate assets.');</script>
            @endif
        @endif

        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
