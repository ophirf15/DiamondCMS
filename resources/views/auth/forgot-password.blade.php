@extends('layouts.public', ['title' => 'Reset password'])

@section('content')
<section class="dc-card dc-narrow">
    <h1>Reset password</h1>
    @if (session('status'))
        <p class="dc-status" role="status">{{ session('status') }}</p>
    @endif
    <form method="post" action="{{ route('password.email') }}" class="dc-form">
        @csrf
        <label>Email
            <input type="email" name="email" value="{{ old('email') }}" required autofocus>
        </label>
        <button class="dc-button" type="submit">Send reset link</button>
    </form>
</section>
@endsection
