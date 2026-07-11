@extends('public.resume', ['profile' => $profile, 'variant' => $variant, 'sections' => $sections])
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $variant->name }}</title>
    <style>
        body{font-family:Arial,sans-serif;color:#111;margin:32px;line-height:1.45}
        h1,h2{margin:0 0 8px}
        section{margin:20px 0}
        @media print{body{margin:0}.no-print{display:none}}
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Print or save as PDF</button>
    <h1>{{ $profile->name }}</h1>
    <p>{{ $profile->headline }} {{ $profile->email ? ' | '.$profile->email : '' }}</p>
    <section>
        <h2>Summary</h2>
        <p>{{ $variant->summary_override ?: $profile->summary }}</p>
    </section>
    @foreach($sections as $section)
        <section>
            <h2>{{ $section->title }}</h2>
            <p>{{ $section->organization }}</p>
            <ul>
                @foreach(json_decode($section->bullets ?? '[]', true) ?: [] as $bullet)
                    <li>{{ $bullet }}</li>
                @endforeach
            </ul>
        </section>
    @endforeach
</body>
</html>
