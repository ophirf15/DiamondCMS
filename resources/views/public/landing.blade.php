@extends('layouts.public', ['title' => 'DiamondCMS'])

@section('content')
<section class="dc-hero">
    <p class="dc-eyebrow">DiamondCMS {{ diamondcms_version() }}</p>
    <h1>Shared-host friendly CMS for personal sites, portfolios, and resumes.</h1>
    <p>Build pages visually, manage media, publish resumes, and ship a production ZIP that runs with PHP and MySQL only.</p>
    <div class="dc-actions">
        <a class="dc-button" href="{{ route('install.wizard') }}">Install DiamondCMS</a>
        <a class="dc-button dc-button-secondary" href="{{ route('login') }}">Admin login</a>
    </div>
</section>
@endsection
