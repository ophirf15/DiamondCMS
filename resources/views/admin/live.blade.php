<!doctype html>
<html
    lang="en"
    @if (\App\Domains\Design\Support\DesignManager::resolvedDefaultTheme() !== 'auto')
        data-theme="{{ \App\Domains\Design\Support\DesignManager::resolvedDefaultTheme() }}"
    @endif
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Live editor — {{ $page->title }} · DiamondCMS</title>
    @include('partials.brand-head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,650&family=Sora:wght@400;550;650&display=swap" rel="stylesheet">
    @unless (app()->environment('testing'))
        @vite(['resources/css/app.css', 'resources/js/live.ts'])
    @endunless
    {!! \App\Domains\Design\Support\DesignManager::cssVariables() !!}
</head>
<body
    class="dc-site dc-live-editing min-h-screen antialiased dc-header-{{ \App\Domains\Design\Support\DesignManager::headerStyle() }} dc-footer-{{ \App\Domains\Design\Support\DesignManager::footerStyle() }} dc-btn-{{ \App\Domains\Design\Support\DesignManager::buttonStyle() }}"
    data-dc-motion="off"
    data-dc-button="{{ \App\Domains\Design\Support\DesignManager::buttonStyle() }}"
    data-dc-mobile-nav="{{ \App\Domains\Design\Support\DesignManager::mobileNav() }}"
    data-dc-theme-default="{{ \App\Domains\Design\Support\DesignManager::resolvedDefaultTheme() }}"
    data-dc-theme-lock="{{ \App\Domains\Design\Support\DesignManager::themeLocked() ? '1' : '0' }}"
    data-dc-theme-toggle="{{ \App\Domains\Design\Support\DesignManager::visitorToggleEnabled() ? '1' : '0' }}"
>
    <div
        id="live-editor-app"
        data-boot='@json($boot)'
    >
        <div class="flex min-h-screen items-center justify-center p-8 text-center text-sm" style="color: var(--dc-muted)">
            Loading live editor… Run <code>npm run dev</code> or <code>npm run build</code> if this stays blank.
        </div>
    </div>
</body>
</html>
