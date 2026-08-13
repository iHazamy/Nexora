{{--
    The "Deactivate instead" action, as a real form.

    ! A PUT form, never a link. A GET that changes a record is followed by crawlers,
      browser prefetch and "open all in tabs", and it cannot carry a CSRF token.

    ! It posts to the resource's normal update route with nothing but a marker, because the
      route table has no separate deactivate endpoint. The matching FormRequest recognises
      the marker, skips the field rules (none of them are submitted) and returns a payload of
      just `active => false` — sending the other fields on this path would blank NOT NULL
      columns, since they validate to null when unvalidated.

    ? Why this exists: the API refuses to delete a product, package or bank account that
      something else references (409) and its message says to deactivate it instead. The
      refusal bounces back to the page the user was on, so the remedy has to be ON that page
      — otherwise they read advice they cannot act on.
--}}
@props([
    'action',
    'confirm' => 'Deactivate this? It stays on records that already use it.',
    'marker' => 'deactivate',
    'markerValue' => '1',
])

<form method="POST" action="{{ $action }}" onsubmit="return confirm(@js($confirm))" class="inline">
    @csrf
    @method('PUT')

    <input type="hidden" name="{{ $marker }}" value="{{ $markerValue }}">

    {{-- Text, not an icon. A power symbol next to a bin icon reads as a second delete, and
         this action is the opposite of one. --}}
    <button type="submit" {{ $attributes->merge(['class' => 'btn-secondary btn-sm']) }}>
        Deactivate
    </button>
</form>
