@extends('public.layout', [
    'title' => $title.' · '.diamondcms_site_name(),
    'description' => $description ?? null,
])

@section('content')
<article class="dc-legal">
    <header class="dc-legal-header">
        <p class="dc-eyebrow">Legal</p>
        <h1>{{ $title }}</h1>
        <p class="dc-legal-meta">Effective date: {{ $legal['effective_date'] }}</p>
    </header>

    <div class="dc-legal-body">
        @yield('legal')
    </div>

    <nav class="dc-legal-nav" aria-label="Legal pages">
        @if (($legal['pages']['privacy'] ?? false) && ($page ?? '') !== 'privacy')
            <a href="{{ url('/privacy') }}">Privacy Policy</a>
        @endif
        @if (($legal['pages']['cookies'] ?? false) && ($page ?? '') !== 'cookies')
            <a href="{{ url('/cookies') }}">Cookie Policy</a>
        @endif
        @if (($legal['pages']['terms'] ?? false) && ($page ?? '') !== 'terms')
            <a href="{{ url('/terms') }}">Terms of Use</a>
        @endif
    </nav>
</article>
@endsection
