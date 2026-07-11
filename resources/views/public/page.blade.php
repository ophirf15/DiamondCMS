@extends('public.layout', ['title' => $page->meta_title ?: $page->title, 'description' => $page->meta_description ?? $page->excerpt])

@section('content')
<article class="dc-page">
    {!! $content !!}
</article>
@endsection
