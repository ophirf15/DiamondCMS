@extends('public.layout', ['title' => $project->meta_title ?: $project->title, 'description' => $project->meta_description ?? $project->summary])

@php
    use App\Domains\Design\Support\DesignManager;

    $gallery = is_array($project->gallery ?? null) ? $project->gallery : [];
    $logos = is_array($project->logos ?? null) ? $project->logos : [];
    $portfolio = DesignManager::portfolio();
    $pageLayout = DesignManager::portfolioAttr('pageLayout', DesignManager::portfolioPageLayouts(), 'classic');
    $logoStyle = DesignManager::portfolioAttr('logoStyle', DesignManager::portfolioLogoStyles(), 'chips');
    $logoSize = DesignManager::portfolioAttr('logoSize', DesignManager::portfolioSizePresets(), 'md');
    $logoPlacement = DesignManager::portfolioAttr('logoPlacement', DesignManager::portfolioLogoPlacements(), 'beside-title');
    $ctaSize = DesignManager::portfolioAttr('ctaSize', DesignManager::portfolioSizePresets(), 'md');
    $skillsStyle = DesignManager::portfolioAttr('skillsStyle', DesignManager::portfolioSkillsStyles(), 'chips');
    $galleryPosition = in_array(($portfolio['galleryPosition'] ?? 'after'), ['before', 'after', 'with-media'], true)
        ? $portfolio['galleryPosition']
        : 'after';
@endphp

@section('content')
    <article
        class="dc-section dc-project-page dc-project-page--{{ $pageLayout }} dc-project-logosize--{{ $logoSize }} dc-project-logoplacement--{{ $logoPlacement }} dc-project-cta--{{ $ctaSize }}"
        data-dc-portfolio-layout="{{ $pageLayout }}"
    >
        <p class="dc-project-back"><a href="{{ route('projects.index') }}">← All projects</a></p>

        @if ($pageLayout === 'split')
            <div class="dc-project-split">
                <div class="dc-project-media-col">
                    @if (! empty($project->cover_image))
                        <img class="dc-project-hero" src="{{ $project->cover_image }}" alt="" loading="eager">
                    @endif
                    @if ($galleryPosition === 'with-media' || $galleryPosition === 'before')
                        @include('public.projects.partials.gallery', ['project' => $project, 'gallery' => $gallery])
                    @endif
                </div>
                <div class="dc-project-copy-col">
                    @include('public.projects.partials.heading', [
                        'title' => $project->title,
                        'logos' => $logos,
                        'logoStyle' => $logoStyle,
                        'logoPlacement' => $logoPlacement,
                    ])
                    @if ($project->summary)<p class="lead">{{ $project->summary }}</p>@endif
                    @include('public.projects.partials.skills', ['project' => $project, 'skillsStyle' => $skillsStyle])
                    @if ($project->url)
                        <p class="dc-project-actions">
                            <a class="dc-button dc-project-cta" href="{{ $project->url }}" rel="noopener noreferrer" target="_blank">Visit project</a>
                        </p>
                    @endif
                    @if ($project->case_study)
                        <div class="dc-text">{!! nl2br(e($project->case_study)) !!}</div>
                    @endif
                    @if ($galleryPosition === 'after')
                        @include('public.projects.partials.gallery', ['project' => $project, 'gallery' => $gallery])
                    @endif
                </div>
            </div>
        @elseif ($pageLayout === 'magazine')
            @if (! empty($project->cover_image))
                <div class="dc-project-magazine-hero">
                    <img class="dc-project-hero" src="{{ $project->cover_image }}" alt="" loading="eager">
                </div>
            @endif
            <header class="dc-project-magazine-header">
                @include('public.projects.partials.heading', [
                    'title' => $project->title,
                    'logos' => $logos,
                    'logoStyle' => $logoStyle,
                    'logoPlacement' => $logoPlacement,
                ])
                @if ($project->summary)<p class="lead">{{ $project->summary }}</p>@endif
                <div class="dc-project-magazine-meta">
                    @include('public.projects.partials.skills', ['project' => $project, 'skillsStyle' => $skillsStyle])
                    @if ($project->url)
                        <a class="dc-button dc-project-cta" href="{{ $project->url }}" rel="noopener noreferrer" target="_blank">Visit project</a>
                    @endif
                </div>
            </header>
            @if ($galleryPosition === 'before')
                @include('public.projects.partials.gallery', ['project' => $project, 'gallery' => $gallery])
            @endif
            @if ($project->case_study)
                <div class="dc-text dc-project-story">{!! nl2br(e($project->case_study)) !!}</div>
            @endif
            @if ($galleryPosition !== 'before')
                @include('public.projects.partials.gallery', ['project' => $project, 'gallery' => $gallery])
            @endif
        @elseif ($pageLayout === 'compact')
            <header class="dc-project-compact-band">
                @if (! empty($project->cover_image))
                    <img class="dc-project-compact-cover" src="{{ $project->cover_image }}" alt="" loading="eager">
                @endif
                <div class="dc-project-compact-copy">
                    @include('public.projects.partials.heading', [
                        'title' => $project->title,
                        'logos' => $logos,
                        'logoStyle' => $logoStyle,
                        'logoPlacement' => $logoPlacement,
                    ])
                    @if ($project->summary)<p class="lead">{{ $project->summary }}</p>@endif
                    @include('public.projects.partials.skills', ['project' => $project, 'skillsStyle' => $skillsStyle])
                    @if ($project->url)
                        <p class="dc-project-actions">
                            <a class="dc-button dc-project-cta" href="{{ $project->url }}" rel="noopener noreferrer" target="_blank">Visit project</a>
                        </p>
                    @endif
                </div>
            </header>
            @if ($galleryPosition === 'before')
                @include('public.projects.partials.gallery', ['project' => $project, 'gallery' => $gallery])
            @endif
            @if ($project->case_study)
                <div class="dc-text">{!! nl2br(e($project->case_study)) !!}</div>
            @endif
            @if ($galleryPosition !== 'before')
                @include('public.projects.partials.gallery', ['project' => $project, 'gallery' => $gallery])
            @endif
        @else
            @if (! empty($project->cover_image))
                <img class="dc-project-hero" src="{{ $project->cover_image }}" alt="" loading="eager">
            @endif
            @include('public.projects.partials.heading', [
                'title' => $project->title,
                'logos' => $logos,
                'logoStyle' => $logoStyle,
                'logoPlacement' => $logoPlacement,
            ])
            @if ($project->summary)<p class="lead">{{ $project->summary }}</p>@endif
            @include('public.projects.partials.skills', ['project' => $project, 'skillsStyle' => $skillsStyle])
            @if ($project->url)
                <p class="dc-project-actions">
                    <a class="dc-button dc-project-cta" href="{{ $project->url }}" rel="noopener noreferrer" target="_blank">Visit project</a>
                </p>
            @endif
            @if ($galleryPosition === 'before')
                @include('public.projects.partials.gallery', ['project' => $project, 'gallery' => $gallery])
            @endif
            @if ($project->case_study)
                <div class="dc-text">{!! nl2br(e($project->case_study)) !!}</div>
            @endif
            @if ($galleryPosition !== 'before')
                @include('public.projects.partials.gallery', ['project' => $project, 'gallery' => $gallery])
            @endif
        @endif
    </article>
@endsection
