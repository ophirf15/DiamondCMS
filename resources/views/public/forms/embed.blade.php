<form class="dc-form" method="post" action="{{ route('forms.submit', $form->slug) }}" enctype="multipart/form-data">
    @csrf
    <input type="text" name="{{ $form->spam_config['honeypot'] ?? 'website' }}" tabindex="-1" autocomplete="off" class="honeypot" aria-hidden="true">
    @foreach ($form->schema['fields'] ?? [] as $field)
        <label class="dc-form-field">
            <span>{{ $field['label'] }}@if (! empty($field['required'])) * @endif</span>
            @if (($field['type'] ?? 'text') === 'textarea')
                <textarea name="{{ $field['name'] }}" rows="4" @required($field['required'] ?? false)></textarea>
            @elseif (($field['type'] ?? 'text') === 'select')
                <select name="{{ $field['name'] }}" @required($field['required'] ?? false)>
                    <option value="">Select…</option>
                    @foreach ($field['options'] ?? [] as $option)
                        <option value="{{ is_array($option) ? ($option['value'] ?? $option['label'] ?? '') : $option }}">
                            {{ is_array($option) ? ($option['label'] ?? $option['value'] ?? '') : $option }}
                        </option>
                    @endforeach
                </select>
            @elseif (($field['type'] ?? 'text') === 'checkbox')
                <input type="checkbox" name="{{ $field['name'] }}" value="1" @required($field['required'] ?? false)>
            @else
                <input
                    name="{{ $field['name'] }}"
                    type="{{ in_array($field['type'], ['email', 'url', 'number', 'file'], true) ? $field['type'] : 'text' }}"
                    @required($field['required'] ?? false)
                >
            @endif
        </label>
        @if (isset($errors) && $errors->has($field['name']))
            <p class="dc-form-error" role="alert">{{ $errors->first($field['name']) }}</p>
        @endif
    @endforeach
    <button class="dc-button" type="submit">Submit</button>
</form>
