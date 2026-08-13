{{--
    ! `options` is an ordered map of value => label. `placeholder` adds an empty
      option, which is how an OPTIONAL foreign key is expressed — bank_account_id and
      customer filters both need "no choice" to be selectable, and a select with no
      empty option silently submits its first entry instead.
--}}
@props([
    'name',
    'label' => null,
    'options' => [],
    'value' => null,
    'hint' => null,
    'required' => false,
    'placeholder' => null,
])

@php
    $dotted = str_replace(['[', ']'], ['.', ''], $name);
    $hasError = $errors->has($dotted);
    $describedBy = trim(($hint ? "$dotted-hint " : '') . ($hasError ? "$dotted-error" : ''));
    $selected = (string) old($dotted, $value);
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

    <select
        id="{{ $dotted }}"
        name="{{ $name }}"
        @if ($required) required @endif
        @if ($hasError) aria-invalid="true" @endif
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->merge(['class' => 'input']) }}
    >
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>

    @if ($hint)
        <p id="{{ $dotted }}-hint" class="field-hint">{{ $hint }}</p>
    @endif

    @if ($hasError)
        <p id="{{ $dotted }}-error" class="field-error">{{ $errors->first($dotted) }}</p>
    @endif
</div>
