<!doctype html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    @if (\App\Domains\Design\Support\DesignManager::resolvedDefaultTheme() !== 'auto')
        data-theme="{{ \App\Domains\Design\Support\DesignManager::resolvedDefaultTheme() }}"
    @endif
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? diamondcms_site_name() }}</title>
    @isset($description)<meta name="description" content="{{ $description }}">@endisset
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:title" content="{{ $title ?? diamondcms_site_name() }}">
    @isset($description)<meta property="og:description" content="{{ $description }}">@endisset
    @include('partials.brand-head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,650&family=Sora:wght@400;550;650&display=swap" rel="stylesheet">
    @unless (app()->environment('testing'))
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/public.ts'])
    @endunless
    {!! \App\Domains\Design\Support\DesignManager::cssVariables() !!}
    {!! diamondcms_custom_head() !!}
</head>
@php
    $shell = $shell ?? 'default';
    $headerStyle = \App\Domains\Design\Support\DesignManager::headerStyle();
    $footerStyle = \App\Domains\Design\Support\DesignManager::footerStyle();
    $buttonStyle = \App\Domains\Design\Support\DesignManager::buttonStyle();
    $chrome = \App\Domains\Design\Support\DesignManager::chrome();
    $mobileNav = \App\Domains\Design\Support\DesignManager::mobileNav();
    $visitorToggle = \App\Domains\Design\Support\DesignManager::visitorToggleEnabled();
    $themeLocked = \App\Domains\Design\Support\DesignManager::themeLocked();
    $defaultTheme = \App\Domains\Design\Support\DesignManager::resolvedDefaultTheme();
    $surface = \App\Domains\Design\Support\DesignManager::surfaceAttr();
    $uiKit = \App\Domains\Design\Support\DesignManager::uiKit();
    $resumeDensity = \App\Domains\Design\Support\DesignManager::resumeAttr('density', \App\Domains\Design\Support\DesignManager::resumeDensities(), 'comfortable');
    $resumeRhythm = \App\Domains\Design\Support\DesignManager::resumeAttr('sectionRhythm', \App\Domains\Design\Support\DesignManager::resumeSectionRhythms(), 'relaxed');
    $resumeExperience = \App\Domains\Design\Support\DesignManager::resumeAttr('experienceStyle', \App\Domains\Design\Support\DesignManager::resumeExperienceStyles(), 'stacked');
@endphp
<body
    class="dc-site {{ $shell === 'sidebar-dark' ? 'dc-shell-sidebar-dark' : 'dc-shell-default' }} dc-header-{{ $headerStyle }} dc-footer-{{ $footerStyle }} dc-btn-{{ $buttonStyle }} dc-surface-{{ $surface }}"
    data-dc-motion="{{ \App\Domains\Design\Support\DesignManager::motionEnabled() ? 'on' : 'off' }}"
    data-dc-button="{{ $buttonStyle }}"
    data-dc-surface="{{ $surface }}"
    data-dc-density="{{ $uiKit['density'] ?? 'comfortable' }}"
    data-dc-control="{{ $uiKit['controlStyle'] ?? 'soft' }}"
    data-dc-mobile-nav="{{ $mobileNav }}"
    data-dc-resume-density="{{ $resumeDensity }}"
    data-dc-resume-rhythm="{{ $resumeRhythm }}"
    data-dc-resume-experience="{{ $resumeExperience }}"
    data-dc-theme-default="{{ $defaultTheme }}"
    data-dc-theme-lock="{{ $themeLocked ? '1' : '0' }}"
    data-dc-theme-toggle="{{ $visitorToggle ? '1' : '0' }}"
>
    <a class="skip-link" href="#content">Skip to content</a>

    @if ($shell === 'sidebar-dark')
        <div class="dc-sidebar-shell">
            <aside class="dc-sidebar-rail" data-dc-animate="rise" data-dc-nav-root>
                <a class="dc-sidebar-brand" href="{{ route('home') }}">
                    <img src="{{ diamondcms_logo_url() }}" alt="" class="dc-sidebar-photo">
                    <strong>{{ diamondcms_site_name() }}</strong>
                </a>
                @include('public.partials.nav-toggle', ['navId' => 'dc-sidebar-nav'])
                <nav id="dc-sidebar-nav" class="dc-sidebar-nav" aria-label="Primary" data-dc-primary-nav>
                    @php($headerItems = diamondcms_menu('header'))
                    @forelse ($headerItems as $item)
                        <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                    @empty
                        <a href="{{ route('home') }}">Home</a>
                        <a href="{{ route('projects.index') }}">Portfolio</a>
                    @endforelse
                    @auth
                        <a href="{{ url('/admin/dashboard') }}">Admin</a>
                    @endauth
                </nav>
                @if ($visitorToggle)
                    <button type="button" class="dc-theme-toggle" data-dc-theme-toggle-btn aria-label="Toggle light and dark mode">Theme</button>
                @endif
            </aside>
            <div class="dc-sidebar-main">
                <main id="content">
                    @if (session('status'))
                        <p class="dc-status" role="status">{{ session('status') }}</p>
                    @endif
                    @yield('content')
                </main>
                @include('public.partials.footer', ['footerStyle' => $footerStyle, 'chrome' => $chrome])
            </div>
        </div>
    @else
        <header class="dc-header dc-header--{{ $headerStyle }}" data-dc-nav-root>
            @if ($headerStyle === 'centered')
                <a class="dc-logo dc-logo--center" href="{{ route('home') }}">
                    <img src="{{ diamondcms_logo_url() }}" alt="" class="h-7 w-auto">
                    <span>{{ diamondcms_site_name() }}</span>
                </a>
                <div class="dc-header-actions">
                    @if ($visitorToggle)
                        <button type="button" class="dc-theme-toggle" data-dc-theme-toggle-btn aria-label="Toggle light and dark mode">Theme</button>
                    @endif
                    @include('public.partials.nav-toggle')
                </div>
                <nav id="dc-primary-nav" class="dc-header-nav dc-header-nav--center" aria-label="Primary" data-dc-primary-nav>
                    @include('public.partials.nav-links')
                </nav>
            @elseif ($headerStyle === 'pill')
                <a class="dc-logo inline-flex items-center gap-2" href="{{ route('home') }}">
                    <img src="{{ diamondcms_logo_url() }}" alt="" class="h-7 w-auto">
                    <span>{{ diamondcms_site_name() }}</span>
                </a>
                <nav id="dc-primary-nav" class="dc-header-nav dc-header-nav--pill" aria-label="Primary" data-dc-primary-nav>
                    @include('public.partials.nav-links')
                </nav>
                <div class="dc-header-actions">
                    @if ($visitorToggle)
                        <button type="button" class="dc-theme-toggle" data-dc-theme-toggle-btn aria-label="Toggle light and dark mode">Theme</button>
                    @endif
                    @include('public.partials.nav-toggle')
                </div>
            @elseif ($headerStyle === 'split')
                <nav id="dc-primary-nav" class="dc-header-nav" aria-label="Primary" data-dc-primary-nav>
                    @include('public.partials.nav-links')
                </nav>
                <a class="dc-logo inline-flex items-center gap-2" href="{{ route('home') }}">
                    <img src="{{ diamondcms_logo_url() }}" alt="" class="h-7 w-auto">
                    <span>{{ diamondcms_site_name() }}</span>
                </a>
                <div class="dc-header-actions">
                    @if ($visitorToggle)
                        <button type="button" class="dc-theme-toggle" data-dc-theme-toggle-btn aria-label="Toggle light and dark mode">Theme</button>
                    @endif
                    <a class="dc-button dc-header-cta" href="{{ url('/contact') }}">Contact</a>
                    @include('public.partials.nav-toggle')
                </div>
            @else
                <a class="dc-logo inline-flex items-center gap-2" href="{{ route('home') }}">
                    <img src="{{ diamondcms_logo_url() }}" alt="" class="h-7 w-auto">
                    <span>{{ diamondcms_site_name() }}</span>
                </a>
                <nav id="dc-primary-nav" class="dc-header-nav {{ $headerStyle === 'minimal' ? 'dc-header-nav--minimal' : '' }}" aria-label="Primary" data-dc-primary-nav>
                    @include('public.partials.nav-links')
                </nav>
                <div class="dc-header-actions">
                    @if ($visitorToggle)
                        <button type="button" class="dc-theme-toggle" data-dc-theme-toggle-btn aria-label="Toggle light and dark mode">Theme</button>
                    @endif
                    @include('public.partials.nav-toggle')
                </div>
            @endif
        </header>
        <main id="content">
            @if (session('status'))
                <p class="dc-status" role="status">{{ session('status') }}</p>
            @endif
            @yield('content')
        </main>
        @include('public.partials.footer', ['footerStyle' => $footerStyle, 'chrome' => $chrome])
    @endif
</body>
</html>
