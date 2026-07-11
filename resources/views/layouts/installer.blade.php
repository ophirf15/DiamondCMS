<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Install DiamondCMS' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,650&family=Sora:wght@400;550;650&display=swap" rel="stylesheet">
    @unless (app()->environment('testing'))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endunless
</head>
<body class="dc-install-body">
    <main class="dc-install">
        <header class="dc-install-brand">
            <p class="dc-eyebrow">DiamondCMS {{ diamondcms_version() }}</p>
            <h1>{{ $title ?? 'Install DiamondCMS' }}</h1>
            <p class="lead">Guided setup for a shared-host PHP and MySQL install. No shell access required.</p>
        </header>
        @yield('content')
    </main>
</body>
</html>
