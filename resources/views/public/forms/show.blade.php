@extends('public.layout', ['title' => $form->name])

@section('content')
    <section class="dc-section">
        <h1>{{ $form->name }}</h1>
        @include('public.forms.embed', ['form' => $form])
    </section>
@endsection
