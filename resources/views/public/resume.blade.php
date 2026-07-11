@extends('layouts.public', ['title' => $variant->name])

@section('content')
<article class="dc-resume">
    <aside>
        <h1>{{ $profile->name }}</h1>
        @if ($profile->headline)
            <p>{{ $profile->headline }}</p>
        @endif
        @if ($profile->email)
            <p>{{ $profile->email }}</p>
        @endif
        @if ($profile->phone)
            <p>{{ $profile->phone }}</p>
        @endif
        <a class="dc-button" href="{{ route('resume.print', $variant->slug) }}">Download print file</a>
    </aside>
    <section>
        <h2>Summary</h2>
        <p>{{ $variant->summary_override ?: $profile->summary }}</p>
        @foreach ($sections as $section)
            <section class="dc-resume-section">
                <h2>{{ $section->title }}</h2>
                @if ($section->organization)
                    <p>{{ $section->organization }}</p>
                @endif
                <ul>
                    @foreach (json_decode($section->bullets ?? '[]', true) ?: [] as $bullet)
                        <li>{{ $bullet }}</li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </section>
</article>
@endsection
