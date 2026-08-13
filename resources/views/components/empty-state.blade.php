{{--
    ! An empty list is a SUCCESS, not an error. The API says so explicitly — it answers
      HTTP 200 with response_code 204 — and this component is how that shows up:
      an invitation to add the first record, never a warning colour or an error icon.
--}}
@props([
    'icon' => 'products',
    'title',
    'text' => null,
])

<div class="empty">
    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-400">
        <x-icon :name="$icon" class="h-5 w-5" />
    </div>

    <p class="empty-title">{{ $title }}</p>

    @if ($text)
        <p class="empty-text">{{ $text }}</p>
    @endif

    @if (! $slot->isEmpty())
        <div class="mt-1">{{ $slot }}</div>
    @endif
</div>
