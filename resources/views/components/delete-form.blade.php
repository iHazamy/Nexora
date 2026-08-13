{{--
    A destructive action as a real form.

    ! A POST form with method spoofing, never a link. A GET link that deletes is
      followed by crawlers, browser prefetch and "open all in tabs" — and it cannot
      carry a CSRF token.

    ! The confirm() text should name what is being deleted. "Are you sure?" on its own
      is the dialog everyone clicks through without reading.

    ? The API is the one that decides whether a delete is even allowed — a customer with
      invoices, a product on an invoice and a bank account named on one are all refused
      with a 409 that explains the remedy. So this button appears whenever the user has
      the grant, and the refusal (if any) arrives as a message rather than being
      predicted here.
--}}
@props([
    'action',
    'confirm' => 'This cannot be undone. Continue?',
    'label' => 'Delete',
    'iconOnly' => false,
])

<form method="POST" action="{{ $action }}" onsubmit="return confirm(@js($confirm))" class="inline">
    @csrf
    @method('DELETE')

    <button type="submit" {{ $attributes->merge(['class' => 'btn-danger btn-sm']) }}>
        <x-icon name="trash" class="h-3.5 w-3.5" />
        @if ($iconOnly)
            <span class="sr-only">{{ $label }}</span>
        @else
            {{ $label }}
        @endif
    </button>
</form>
