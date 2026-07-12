@php
    $logos = is_array($logos ?? null) ? $logos : [];
    $logoStyle = $logoStyle ?? 'chips';
    $logoPlacement = $logoPlacement ?? 'beside-title';
    $title = (string) ($title ?? '');
@endphp
<header class="dc-project-heading dc-project-heading--{{ $logoPlacement }}">
    @if ($logoPlacement === 'beside-title' && $logos !== [])
        @include('public.projects.partials.logos', [
            'logos' => $logos,
            'logoStyle' => $logoStyle,
            'variant' => 'title',
        ])
        <h1>{{ $title }}</h1>
    @else
        <h1>{{ $title }}</h1>
    @endif
</header>
@if ($logoPlacement === 'below' && $logos !== [])
    @include('public.projects.partials.logos', [
        'logos' => $logos,
        'logoStyle' => $logoStyle,
        'variant' => 'stack',
    ])
@endif
