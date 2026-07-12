@extends('layouts.installer', ['title' => 'Install DiamondCMS'])

@section('content')
    @php
        $boot = [
            'csrf' => csrf_token(),
            'status' => session('status'),
            'errors' => $errors->all(),
            'requirements' => $requirements,
            'databaseAction' => route('install.database'),
            'finishAction' => route('install.finish'),
            'recoveryAction' => route('install.recovery.clear-lock'),
            'defaults' => [
                'db_host' => old('db_host', session('installer.db.db_host', 'localhost')),
                'db_port' => (string) old('db_port', session('installer.db.db_port', '3306')),
                'db_database' => old('db_database', session('installer.db.db_database', '')),
                'db_username' => old('db_username', session('installer.db.db_username', '')),
                'db_password' => old('db_password', session('installer.db.db_password', '')),
                'site_name' => old('site_name', 'DiamondCMS'),
                'base_url' => old('base_url', url('/')),
                'admin_name' => old('admin_name', ''),
                'admin_email' => old('admin_email', ''),
            ],
        ];
    @endphp
    <div id="installer-app" data-boot="{{ json_encode($boot, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">
        <noscript>
            <p class="p-8 text-center">JavaScript is required for the DiamondCMS installer UI. Enable JS or use <code>php artisan diamondcms:install</code>.</p>
        </noscript>
    </div>
@endsection
