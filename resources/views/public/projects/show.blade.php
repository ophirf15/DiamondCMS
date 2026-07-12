@extends('public.layout', ['title' => $project->meta_title ?: $project->title, 'description' => $project->meta_description ?? $project->summary])

@section('content')
    <article class="dc-section">
        <p><a href="{{ route('projects.index') }}">All projects</a></p>
        @if (! empty($project->cover_image))
            <img class="dc-project-hero" src="{{ $project->cover_image }}" alt="" loading="eager">
        @endif
        <h1>{{ $project->title }}</h1>
        @if ($project->summary)<p class="lead">{{ $project->summary }}</p>@endif
        @if (! empty($project->skills))
            <p><strong>Skills:</strong> {{ implode(', ', $project->skills) }}</p>
        @endif
        @if ($project->url)
            <p><a class="dc-button" href="{{ $project->url }}" rel="noopener noreferrer" target="_blank">Visit project</a></p>
        @endif
        @if ($project->case_study)
            <div class="dc-text">{!! nl2br(e($project->case_study)) !!}</div>
        @endif
    </article>
@endsection
