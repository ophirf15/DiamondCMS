@extends('public.layout', ['title' => 'Projects'])

@php
    use App\Domains\Design\Support\DesignManager;
    $indexLayout = DesignManager::portfolioAttr('indexLayout', DesignManager::portfolioIndexLayouts(), 'grid');
@endphp

@section('content')
    <section class="dc-section dc-projects-index dc-projects-index--{{ $indexLayout }}">
        <h1>Projects</h1>
        <div class="dc-project-grid dc-project-grid--{{ $indexLayout }}">
            @forelse ($projects as $project)
                {!! app(\App\Domains\Portfolio\Support\PortfolioManager::class)->projectCardHtml($project, 'h2') !!}
            @empty
                <p>No public projects match these filters.</p>
            @endforelse
        </div>
    </section>
@endsection
