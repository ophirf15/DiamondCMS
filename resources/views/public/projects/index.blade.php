@extends('public.layout', ['title' => 'Projects'])

@section('content')
    <section class="dc-section">
        <h1>Projects</h1>
        <div class="dc-project-grid">
            @forelse ($projects as $project)
                {!! app(\App\Domains\Portfolio\Support\PortfolioManager::class)->projectCardHtml($project, 'h2') !!}
            @empty
                <p>No public projects match these filters.</p>
            @endforelse
        </div>
    </section>
@endsection
