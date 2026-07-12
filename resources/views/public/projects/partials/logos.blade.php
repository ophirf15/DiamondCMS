@php
    $logos = is_array($logos ?? null) ? $logos : [];
    $logoStyle = $logoStyle ?? 'chips';
    $variant = $variant ?? 'stack'; // title | stack
    $showLabels = $variant !== 'title' && $logoStyle !== 'icons';
@endphp
@if ($logos !== [])
    <div
        class="dc-project-logos dc-project-logos--{{ $logoStyle }} dc-project-logos--{{ $variant }}"
        aria-label="{{ $variant === 'title' ? 'Project brand' : 'Project logos and tools' }}"
    >
        @foreach ($logos as $logo)
            @php
                $label = (string) ($logo['label'] ?? 'Logo');
                $icon = trim((string) ($logo['icon'] ?? ''));
                $image = trim((string) ($logo['image'] ?? ''));
                $href = trim((string) ($logo['url'] ?? ''));
                $mark = '';
                if ($image !== '') {
                    $mark = '<img class="dc-project-logo-img" src="'.e($image).'" alt="'.e($label).'" loading="lazy" decoding="async">';
                } elseif ($icon !== '') {
                    $mark = \App\Domains\Design\Support\SocialIcons::markup($icon, true);
                } else {
                    $mark = '<span class="dc-project-logo-fallback">'.e(mb_substr($label, 0, 1)).'</span>';
                }
                if ($variant === 'title' || $logoStyle === 'icons') {
                    $inner = $mark.'<span class="sr-only">'.e($label).'</span>';
                } elseif ($showLabels) {
                    $inner = $mark.'<span class="dc-project-logo-label">'.e($label).'</span>';
                } else {
                    $inner = $mark;
                }
            @endphp
            @if ($href !== '')
                <a class="dc-project-logo" href="{{ $href }}" rel="noopener noreferrer" target="_blank" title="{{ $label }}">{!! $inner !!}</a>
            @else
                <span class="dc-project-logo" title="{{ $label }}">{!! $inner !!}</span>
            @endif
        @endforeach
    </div>
@endif
