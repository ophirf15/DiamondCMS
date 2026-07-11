@extends('layouts.installer', ['title' => 'Install DiamondCMS'])

@section('content')
<section class="dc-card dc-install-card">
    @if (session('status'))
        <p class="dc-status" role="status">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <ul class="dc-error-list" role="alert">
            @foreach ($errors->all() as $error)
                <li class="dc-error">{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <h2>1. Requirements</h2>
    <dl class="dc-grid">
        <dt>PHP</dt>
        <dd>{{ $requirements['php']['current'] }} — {{ $requirements['php']['ok'] ? 'OK' : 'Upgrade required' }}</dd>
        @foreach ($requirements['extensions'] as $extension => $ok)
            <dt>{{ $extension }}</dt>
            <dd>{{ $ok ? 'OK' : 'Missing' }}</dd>
        @endforeach
        @foreach ($requirements['writable'] ?? [] as $path => $ok)
            <dt>{{ $path }}</dt>
            <dd>{{ $ok ? 'Writable' : 'Fix permissions' }}</dd>
        @endforeach
    </dl>

    <h2>2. Database</h2>
    <p class="lead">Local defaults: database <code>diamondcms</code>, user <code>diamondcms</code>, password <code>diamondcms_local</code>.</p>
    <form method="post" action="{{ route('install.database') }}" class="dc-form">
        @csrf
        <label>Host
            <input name="db_host" value="{{ old('db_host', session('installer.db.db_host', '127.0.0.1')) }}" required>
        </label>
        <label>Port
            <input name="db_port" value="{{ old('db_port', session('installer.db.db_port', '3306')) }}" required>
        </label>
        <label>Database
            <input name="db_database" value="{{ old('db_database', session('installer.db.db_database', 'diamondcms')) }}" required>
        </label>
        <label>Username
            <input name="db_username" value="{{ old('db_username', session('installer.db.db_username', 'diamondcms')) }}" required autocomplete="username">
        </label>
        <label>Password
            <input type="password" name="db_password" value="{{ old('db_password', session('installer.db.db_password', 'diamondcms_local')) }}" autocomplete="new-password">
        </label>
        <button class="dc-button" type="submit">Test database</button>
    </form>

    <h2>3. Site and administrator</h2>
    <form method="post" action="{{ route('install.finish') }}" class="dc-form">
        @csrf
        <label>Site name
            <input name="site_name" value="{{ old('site_name', 'DiamondCMS') }}" required>
        </label>
        <label>Base URL
            <input type="url" name="base_url" value="{{ old('base_url', url('/')) }}" required>
        </label>
        <label>Admin name
            <input name="admin_name" value="{{ old('admin_name') }}" required>
        </label>
        <label>Admin email
            <input type="email" name="admin_email" value="{{ old('admin_email') }}" required autocomplete="username">
        </label>
        <label>Admin password
            <input type="password" name="admin_password" required minlength="12" autocomplete="new-password">
        </label>
        <label>Confirm password
            <input type="password" name="admin_password_confirmation" required minlength="12" autocomplete="new-password">
        </label>
        <button class="dc-button" type="submit">Finish installation</button>
    </form>

    <details class="dc-install-recovery">
        <summary>Install recovery</summary>
        <p>Use only if setup was interrupted and <code>DIAMONDCMS_RECOVERY_KEY</code> is configured.</p>
        <form method="post" action="{{ route('install.recovery.clear-lock') }}" class="dc-form">
            @csrf
            <label>Recovery key
                <input type="password" name="recovery_key" autocomplete="off">
            </label>
            <button class="dc-button dc-button-secondary" type="submit">Clear install lock</button>
        </form>
    </details>
</section>
@endsection
