@extends('layouts.public', ['title' => 'Choose a new password'])

@section('content')
<section class="dc-card dc-narrow">
    <h1>Choose a new password</h1>
    <form method="post" action="{{ route('password.update') }}" class="dc-form">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label>Email
            <input type="email" name="email" value="{{ old('email', $email) }}" required>
        </label>
        <label>Password
            <input type="password" name="password" required minlength="12" autocomplete="new-password">
        </label>
        <label>Confirm password
            <input type="password" name="password_confirmation" required minlength="12" autocomplete="new-password">
        </label>
        @error('email')
            <p class="dc-error">{{ $message }}</p>
        @enderror
        <button class="dc-button" type="submit">Reset password</button>
    </form>
</section>
@endsection
