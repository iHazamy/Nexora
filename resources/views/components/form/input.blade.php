{{--
    A labelled text/number/date/email input that wires its own error state.

    ! The caller passes `name` and nothing else is required. The label, the id, the
      hint association, `aria-invalid` and the error message are all derived from it,
      so the accessible state and the visual state cannot be added independently — the
      failure mode where a field turns red but announces nothing is not reachable from
      here.

    ! `old($name, $value)` on every input. When the ApiException handler bounces a 400
      back to the form it redirects withInput(), and a form that ignores old() throws
      away everything the user typed on the way to showing them the error.
--}}
@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'required' => false,
    'prefix' => null,
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

    <div @class(['relative' => $prefix])>
        @if ($prefix)
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-500">
                {{ $prefix }}
            </span>
        @endif

        <input
            type="{{ $type }}"
            id="{{ $dotted }}"
            name="{{ $name }}"
            value="{{ old($dotted, $value) }}"
            @if ($required) required @endif
            @if ($hasError) aria-invalid="true" @endif
            @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
            {{ $attributes->merge(['class' => 'input' . ($prefix ? ' pl-11' : '')]) }}
        >
    </div>

    @if ($hint)
        <p id="{{ $dotted }}-hint" class="field-hint">{{ $hint }}</p>
    @endif

    @if ($hasError)
        <p id="{{ $dotted }}-error" class="field-error">{{ $errors->first($dotted) }}</p>
    @endif
</div>
