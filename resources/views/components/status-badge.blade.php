{{--
    An invoice status, as a badge.

    ! All five statuses get their own colour. The old app had two, because its model
      had two — it derived "Paid" or "Outstanding" from a balance column. This API
      distinguishes DRAFT, SENT, PARTIALLY_PAID, PAID and CANCELLED, and the
      distinction users care about most on a list is exactly the one collapsing them
      loses: "not sent yet" versus "sent and waiting".

    ! PAID and PARTIALLY_PAID are DERIVED by the API from recorded payments and cannot
      be set by a client. Nothing in this app should offer them as a choice — see
      InvoiceApi::settableStatuses().
--}}
@props(['status', 'overdue' => false])

@php
    $status = strtoupper((string) $status);

    $class = match ($status) {
        'DRAFT'          => 'badge-draft',
        'SENT'           => 'badge-sent',
        'PARTIALLY_PAID' => 'badge-partial',
        'PAID'           => 'badge-paid',
        'CANCELLED'      => 'badge-cancelled',
        default          => 'badge-neutral',
    };

    $label = match ($status) {
        'DRAFT'          => 'Draft',
        'SENT'           => 'Sent',
        'PARTIALLY_PAID' => 'Part paid',
        'PAID'           => 'Paid',
        'CANCELLED'      => 'Cancelled',
        default          => ucfirst(strtolower($status)),
    };
@endphp

<span class="inline-flex flex-wrap items-center gap-1.5">
    <span class="{{ $class }}">{{ $label }}</span>

    {{--
        ! Overdue is shown ALONGSIDE the status, never instead of it. It is not a
          status — the API has no OVERDUE state — it is a fact about the due date, and
          replacing "Part paid" with "Overdue" would hide that money has been received.
    --}}
    @if ($overdue && ! in_array($status, ['PAID', 'CANCELLED', 'DRAFT'], true))
        <span class="badge-overdue">Overdue</span>
    @endif
</span>
