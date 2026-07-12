@extends('public.layout', ['title' => $variant->name.' · '.($profile->name ?? diamondcms_site_name())])

@php
    use App\Domains\Resume\Support\ResumeManager;

    $grouped = ResumeManager::groupSections($sections);
    $hasPdf = filled($variant->download_pdf ?? null);
    $hasDocx = filled($variant->download_docx ?? null);
    $hasDownloads = $hasPdf || $hasDocx;

    $websiteLabel = trim((string) ($profile->website ?? ''));
    if ($websiteLabel !== '') {
        $websiteLabel = preg_replace('#^https?://#i', '', $websiteLabel) ?? $websiteLabel;
        $websiteLabel = rtrim($websiteLabel, '/');
    }
    $profileLinks = ResumeManager::profileLinks($profile);
@endphp

@section('content')
<article class="dc-resume" data-dc-animate="rise">
    <aside class="dc-resume-aside">
        <h1>{{ $profile->name }}</h1>
        @if ($profile->headline)
            <p class="dc-resume-headline">{{ $profile->headline }}</p>
        @endif
        <div class="dc-resume-contact">
            @if ($profile->email)
                <p><a href="mailto:{{ $profile->email }}">{{ $profile->email }}</a></p>
            @endif
            @if ($profile->phone)
                <p>{{ $profile->phone }}</p>
            @endif
            @if ($profile->location)
                <p>{{ $profile->location }}</p>
            @endif
            @if ($websiteLabel !== '')
                <p>
                    <a href="{{ str_starts_with((string) $profile->website, 'http') ? $profile->website : 'https://'.$websiteLabel }}" rel="noopener noreferrer" target="_blank">
                        {{ $websiteLabel }}
                    </a>
                </p>
            @endif
            @foreach ($profileLinks as $link)
                <p>
                    <a href="{{ $link['url'] }}" rel="noopener noreferrer" target="_blank">{{ $link['label'] }}</a>
                </p>
            @endforeach
        </div>

        @if ($hasDownloads)
            <div class="dc-resume-download" data-dc-resume-download>
                <button type="button" class="dc-button dc-resume-download-trigger" data-dc-resume-download-trigger aria-expanded="false" aria-haspopup="true">
                    Download resume
                    <span class="dc-resume-download-chevron" aria-hidden="true">▾</span>
                </button>
                <div class="dc-resume-download-menu" data-dc-resume-download-menu hidden>
                    @if ($hasPdf)
                        <a class="dc-resume-download-option" href="{{ route('resume.download', ['slug' => $variant->slug, 'format' => 'pdf']) }}">PDF</a>
                    @endif
                    @if ($hasDocx)
                        <a class="dc-resume-download-option" href="{{ route('resume.download', ['slug' => $variant->slug, 'format' => 'docx']) }}">DOCX</a>
                    @endif
                </div>
            </div>
        @else
            <p class="dc-resume-download-hint">Attach a PDF or DOCX under Resumes → Public variants to enable downloads.</p>
        @endif
    </aside>

    <div class="dc-resume-main">
        @php $summary = $variant->summary_override ?: $profile->summary; @endphp
        @if ($summary)
            <section class="dc-resume-group dc-resume-summary-block">
                <h2>Summary</h2>
                <p class="dc-resume-summary-text">{{ $summary }}</p>
            </section>
        @endif

        @foreach ($grouped as $type => $items)
            <section class="dc-resume-group dc-resume-group--{{ $type }}">
                <h2>{{ ResumeManager::sectionTypeLabel($type) }}</h2>
                <div class="dc-resume-entries @if ($type === 'experience') dc-resume-experience @endif">
                    @foreach ($items as $section)
                        @php
                            $bullets = json_decode($section->bullets ?? '[]', true) ?: [];
                            $meta = json_decode($section->metadata ?? '{}', true) ?: [];
                            $dateLabel = $meta['date'] ?? null;
                            if (! $dateLabel && ($section->starts_on || $section->ends_on || $section->is_current)) {
                                $dateLabel = trim(implode(' – ', array_filter([
                                    $section->starts_on,
                                    $section->is_current ? 'Present' : $section->ends_on,
                                ])));
                            }
                            $isAwardLike = in_array($type, ['award', 'certification'], true);
                        @endphp
                        <article class="dc-resume-item dc-resume-item--{{ $type }}">
                            @if ($type === 'skills')
                                @if ($section->title)
                                    <h3>{{ $section->title }}</h3>
                                @endif
                                @php
                                    $skillChips = [];
                                    foreach ($bullets as $bullet) {
                                        foreach (preg_split('/\s*[,;|]\s*/', (string) $bullet) ?: [] as $chip) {
                                            $chip = trim($chip);
                                            if ($chip !== '') {
                                                $skillChips[] = $chip;
                                            }
                                        }
                                    }
                                @endphp
                                @if ($skillChips !== [])
                                    <ul class="dc-resume-skills">
                                        @foreach ($skillChips as $chip)
                                            <li>{{ $chip }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            @elseif ($isAwardLike)
                                {{-- Issuer + date first, then award/cert title --}}
                                @if ($section->organization || $dateLabel)
                                    <p class="dc-resume-award-source">
                                        @if ($section->organization)<span>{{ $section->organization }}</span>@endif
                                        @if ($section->organization && $dateLabel)<span aria-hidden="true"> </span>@endif
                                        @if ($dateLabel)<span class="dc-resume-award-year">{{ $dateLabel }}</span>@endif
                                    </p>
                                @endif
                                @if ($section->title)
                                    <h3>{{ $section->title }}</h3>
                                @endif
                                @if ($bullets !== [])
                                    <ul>
                                        @foreach ($bullets as $bullet)
                                            <li>{{ $bullet }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            @else
                                @if ($section->title)
                                    <h3>{{ $section->title }}</h3>
                                @endif
                                @if ($section->organization || $dateLabel)
                                    <p class="dc-resume-meta">
                                        @if ($section->organization)<span>{{ $section->organization }}</span>@endif
                                        @if ($section->organization && $dateLabel)<span aria-hidden="true"> · </span>@endif
                                        @if ($dateLabel)<span>{{ $dateLabel }}</span>@endif
                                    </p>
                                @endif
                                @if ($section->location)
                                    <p class="dc-resume-meta">{{ $section->location }}</p>
                                @endif
                                @if ($bullets !== [])
                                    <ul>
                                        @foreach ($bullets as $bullet)
                                            <li>{{ $bullet }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</article>
@endsection
