@php($headerItems = diamondcms_menu('header'))
@forelse ($headerItems as $item)
    <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
@empty
    <a href="{{ route('projects.index') }}">Projects</a>
@endforelse
@auth
    <a href="{{ url('/admin/dashboard') }}">Admin</a>
@endauth
