@extends('layouts.auth', ['title' => 'Admin login'])

@section('content')
    @php
        $boot = [
            'csrf' => csrf_token(),
            'status' => session('status'),
            'error' => $errors->first('email'),
            'email' => old('email', ''),
            'loginAction' => route('login'),
            'forgotPasswordUrl' => route('password.request'),
        ];
    @endphp
    <div id="auth-login-app" data-boot="{{ json_encode($boot, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">
        <noscript>
            <p class="p-8 text-center">JavaScript is required for the login UI.</p>
        </noscript>
    </div>
@endsection
