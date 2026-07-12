<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? diamondcms_site_name() }}</title>
    @include('partials.brand-head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {!! \App\Domains\Design\Support\DesignManager::cssVariables() !!}
    {!! diamondcms_custom_head() !!}
</head>
<body class="dc-site min-h-screen bg-background text-foreground antialiased">
    <header class="dc-header">
        <a class="dc-logo inline-flex items-center gap-2" href="{{ route('home') }}">
            <img src="{{ diamondcms_logo_url() }}" alt="" class="h-7 w-auto text-primary">
            <span>{{ diamondcms_site_name() }}</span>
        </a>
        <nav aria-label="Primary">
            @php($headerItems = diamondcms_menu('header'))
            @forelse ($headerItems as $item)
                <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
            @empty
                {{-- Intentionally empty until menus are configured --}}
            @endforelse
            @auth
                <a href="{{ url('/admin/dashboard') }}">Admin</a>
            @endauth
        </nav>
    </header>
    <main>
        @yield('content')
    </main>
    <footer class="dc-footer">
        <nav aria-label="Footer">
            @foreach (diamondcms_menu('footer') as $item)
                <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
            @endforeach
        </nav>
        <span>Powered by DiamondCMS {{ diamondcms_version() }}</span>
    </footer>
</body>
</html>
