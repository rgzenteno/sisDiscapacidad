<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">

        <!-- Precarga el logo (prioridad baja, "se va a necesitar más adelante")
             para que el overlay de PreloaderOverlay.vue no tenga que esperar la
             descarga la primera vez que se muestra. `prefetch` en vez de
             `preload` porque el uso real no es inmediato al cargar la página
             (recién ocurre cuando se dispara una acción) — con `preload` el
             navegador loguea una advertencia si no se usa a los pocos segundos. -->
        <link rel="prefetch" as="image" href="{{ asset('logo.png') }}">

        <!-- Scripts -->
        <!-- XLSX para Excel -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
