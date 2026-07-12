@php
    $document = json_decode($page->builder_json ?? '{}', true) ?: [];
    $shell = $document['meta']['shell'] ?? 'default';
@endphp
@extends('public.layout', [
    'title' => $page->meta_title ?: $page->title,
    'description' => $page->meta_description ?? $page->excerpt,
    'shell' => $shell,
])

@section('content')
<article class="dc-page" data-dc-shell="{{ $shell }}">
    {!! $content !!}
</article>
@auth
    @if (auth()->user()?->canAccessAdmin())
        <a class="dc-live-edit-fab" href="{{ route('admin.live', $page->id) }}">Edit live</a>
    @endif
@endauth
@endsection
