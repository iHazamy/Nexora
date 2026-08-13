{{--
    One labelled form control with its hint and error.

    ! Wires `aria-invalid`, `aria-describedby` and the error message together from one
      place. Done per-field by hand, the visual error state and the state announced to
      a screen reader drift apart — the styling gets added and the aria attribute does
      not. Here there is one source for both.

    ! `name` may use dot or bracket notation (items.0.quantity). The error bag is
      keyed with dots and the input needs brackets, so both spellings are derived
      rather than asking the caller to pass two.
--}}
@props([
    'name',
    'label' => null,
    'hint' => null,
    'required' => false,
])

@php
    $dotted = str_replace(['[', ']'], ['.', ''], $name);
    $hasError = $errors->has($dotted);
    $describedBy = collect([
        $hint ? "$dotted-hint" : null,
        $hasError ? "$dotted-error" : null,
    ])->filter()->implode(' ');
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

    {{ $slot }}

    @if ($hint)
        <p id="{{ $dotted }}-hint" class="field-hint">{{ $hint }}</p>
    @endif

    @if ($hasError)
        <p id="{{ $dotted }}-error" class="field-error">{{ $errors->first($dotted) }}</p>
    @endif
</div>
