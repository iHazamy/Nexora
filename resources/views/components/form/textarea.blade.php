@props([
    'name',
    'label' => null,
    'value' => null,
    'hint' => null,
    'required' => false,
    'rows' => 4,
])

@php
    $dotted = str_replace(['[', ']'], ['.', ''], $name);
    $hasError = $errors->has($dotted);
    $describedBy = trim(($hint ? "$dotted-hint " : '') . ($hasError ? "$dotted-error" : ''));
@endphp

<div class="field">
    @if ($label)
        <label for="{{ $dotted }}" class="field-label">
            {{ $label }}
            @if ($required)
                <span class="text-rose-600" aria-hidden="true">*</span>
                <span class="sr-only">(required)</span>
            @endif
        </label>
    @endif

    <textarea
        id="{{ $dotted }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if ($required) required @endif
        @if ($hasError) aria-invalid="true" @endif
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->merge(['class' => 'input']) }}
    >{{ old($dotted, $value) }}</textarea>

    @if ($hint)
        <p id="{{ $dotted }}-hint" class="field-hint">{{ $hint }}</p>
    @endif

    @if ($hasError)
        <p id="{{ $dotted }}-error" class="field-error">{{ $errors->first($dotted) }}</p>
    @endif
</div>
