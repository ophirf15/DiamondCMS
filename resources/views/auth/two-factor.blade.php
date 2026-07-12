@extends('layouts.public', ['title' => 'Two-factor challenge'])

@section('content')
<section class="dc-card dc-narrow">
    <h1>Two-factor authentication</h1>
    <form method="post" action="{{ route('two-factor.challenge') }}" class="dc-form">
        @csrf
        <label>Authenticator or recovery code
            <input name="code" autocomplete="one-time-code" required autofocus>
        </label>
        <p class="dc-muted">Use a 6-digit app code, or one of your single-use recovery codes.</p>
        @error('code')
            <p class="dc-error">{{ $message }}</p>
        @enderror
        <button class="dc-button" type="submit">Verify</button>
    </form>
</section>
@endsection
