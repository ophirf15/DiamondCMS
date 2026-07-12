<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>DiamondCMS admin</title>
    @include('partials.brand-head')
    @unless (app()->environment('testing'))
        @vite(['resources/css/app.css', 'resources/js/admin.ts'])
    @endunless
    {!! \App\Domains\Design\Support\DesignManager::cssVariables() !!}
</head>
<body class="min-h-screen bg-background text-foreground antialiased" style="color-scheme: light">
    <div id="admin-app" data-boot='@json($boot ?? [])'>
        <div class="flex min-h-screen items-center justify-center p-8 text-center text-sm text-muted-foreground">
            <div class="space-y-3">
                <img src="{{ asset('brand/logo-primary-gold.svg') }}" alt="DiamondCMS" class="mx-auto h-12 w-12">
                <h1 class="text-lg font-semibold text-foreground">DiamondCMS admin</h1>
                <p>Loading admin app… If this stays blank, run <code class="rounded bg-muted px-1">npm run dev</code> or <code class="rounded bg-muted px-1">npm run build</code>.</p>
            </div>
        </div>
    </div>
</body>
</html>
