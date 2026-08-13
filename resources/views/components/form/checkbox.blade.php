{{--
    ! Emits a hidden "0" before the checkbox. An unchecked HTML checkbox submits
      NOTHING, so without this the API cannot tell "the user unticked Active" from
      "this form does not manage Active" — and since the API treats an absent key as
      "leave it alone", unticking would silently do nothing at all.
--}}
@props([
    'name',
    'label',
    'checked' => false,
    'hint' => null,
])

@php
    $dotted = str_replace(['[', ']'], ['.', ''], $name);
    $isChecked = (bool) old($dotted, $checked);
@endphp

<div class="flex items-start gap-3">
    <input type="hidden" name="{{ $name }}" value="0">

    <input
        type="checkbox"
        id="{{ $dotted }}"
        name="{{ $name }}"
        value="1"
        @checked($isChecked)
        {{ $attributes->merge([
            'class' => 'mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500',
        ]) }}
    >

    <div>
        <label for="{{ $dotted }}" class="field-label">{{ $label }}</label>
        @if ($hint)
            <p class="field-hint">{{ $hint }}</p>
        @endif
    </div>
</div>
