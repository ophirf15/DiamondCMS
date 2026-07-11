@php
    $brandFavicon = asset('brand/favicon/favicon.svg');
    $brandIcon32 = asset('brand/favicon/favicon-32x32.png');
    $brandApple = asset('brand/favicon/apple-touch-icon.png');
@endphp
<link rel="icon" href="{{ $brandFavicon }}" type="image/svg+xml">
<link rel="icon" href="{{ $brandIcon32 }}" sizes="32x32" type="image/png">
<link rel="apple-touch-icon" href="{{ $brandApple }}">
<meta name="theme-color" content="#333333">
