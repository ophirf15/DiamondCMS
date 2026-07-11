@extends('layouts.public', ['title' => 'Admin login'])

@section('content')
<section class="dc-card dc-narrow">
    <h1>Admin login</h1>
    @if (session('status'))
        <p class="dc-status" role="status">{{ session('status') }}</p>
    @endif
    <form method="post" action="{{ route('login') }}" class="dc-form">
        @csrf
        <label>Email
            <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
        </label>
        <label>Password
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        <label class="dc-inline">
            <input type="checkbox" name="remember" value="1"> Remember me
        </label>
        @error('email')
            <p class="dc-error">{{ $message }}</p>
        @enderror
        <button class="dc-button" type="submit">Sign in</button>
        <a href="{{ route('password.request') }}">Forgot password?</a>
    </form>
</section>
@endsection
