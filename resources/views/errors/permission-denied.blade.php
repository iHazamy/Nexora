{{--
    ! Names the remedy — ask the owner — because that is what the API's own message says
      and it is actionable. "Access denied" on its own leaves the user with nothing to do
      but guess whether it is a bug.
--}}
<x-error-page
    title="You do not have permission"
    icon="warning"
    tone="amber"
    :message="$message"
>
    <a href="{{ route('dashboard') }}" class="btn-primary">Back to dashboard</a>
</x-error-page>
