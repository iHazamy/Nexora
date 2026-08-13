{{--
    ! "Your plan does not include this", NOT "access denied".

      The API distinguishes these with two different codes (614 vs 401) because the
      remedies are different: a feature flag is commercial entitlement and the fix is a
      conversation with sales, while a permission is the account owner's to grant.
      Telling a paying customer they are "not allowed" to use a feature they simply have
      not bought is both wrong and the kind of message that generates an angry ticket.
--}}
<x-error-page
    title="Not included in your plan"
    icon="packages"
    tone="brand"
    :message="$message"
>
    <a href="{{ route('dashboard') }}" class="btn-primary">Back to dashboard</a>
</x-error-page>
