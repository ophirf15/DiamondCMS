@extends('layouts.public', ['title' => $page->title])

@section('content')
<section class="dc-card dc-narrow">
    <h1>{{ $page->title }}</h1>
    <p>This page is password protected.</p>
    <form method="post" action="{{ route('page.password', $page->slug) }}" class="dc-form">
        @csrf
        <label>Password
            <input type="password" name="password" required>
        </label>
        @error('password')
            <p class="dc-error">{{ $message }}</p>
        @enderror
        <button class="dc-button" type="submit">View page</button>
    </form>
</section>
@endsection
