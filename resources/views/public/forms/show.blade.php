@extends('public.layout', ['title' => $form->name])

@section('content')
    <section class="dc-section">
        <h1>{{ $form->name }}</h1>
        <form method="post" action="{{ route('forms.submit', $form->slug) }}" enctype="multipart/form-data">
            @csrf
            <input type="text" name="{{ $form->spam_config['honeypot'] ?? 'website' }}" tabindex="-1" autocomplete="off" class="honeypot" aria-hidden="true">
            @foreach ($form->schema['fields'] ?? [] as $field)
                <label>
                    {{ $field['label'] }}
                    <input
                        name="{{ $field['name'] }}"
                        type="{{ in_array($field['type'], ['email', 'url', 'number', 'file'], true) ? $field['type'] : 'text' }}"
                        @required($field['required'] ?? false)
                    >
                </label>
                @error($field['name'])<p role="alert">{{ $message }}</p>@enderror
            @endforeach
            <button class="dc-button" type="submit">Submit</button>
        </form>
    </section>
@endsection
