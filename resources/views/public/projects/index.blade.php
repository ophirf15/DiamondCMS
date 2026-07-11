@extends('public.layout', ['title' => 'Projects'])

@section('content')
    <section class="dc-section">
        <h1>Projects</h1>
        <div class="dc-project-grid">
            @forelse ($projects as $project)
                <article class="dc-project-card">
                    <h2><a href="{{ route('projects.show', $project->slug) }}">{{ $project->title }}</a></h2>
                    @if ($project->summary)<p>{{ $project->summary }}</p>@endif
                    @if (! empty($project->skills))
                        <p>{{ implode(', ', $project->skills) }}</p>
                    @endif
                </article>
            @empty
                <p>No public projects match these filters.</p>
            @endforelse
        </div>
    </section>
@endsection
