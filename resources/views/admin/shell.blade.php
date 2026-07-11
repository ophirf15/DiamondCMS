<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>DiamondCMS admin</title>
    @unless (app()->environment('testing'))
        @vite(['resources/css/app.css', 'resources/js/admin.ts'])
    @endunless
</head>
<body class="dc-admin-body">
    <div id="admin-app" data-boot='@json($boot ?? [])'>
        <div class="dc-admin-noscript">
            <h1>DiamondCMS admin</h1>
            <p>The admin app is loading. If it does not load, run <code>npm run build</code> or enable JavaScript.</p>
            <form method="post" action="{{ route('logout') }}">@csrf<button class="dc-button">Logout</button></form>
        </div>
    </div>
</body>
</html>
