{{--
    ! NO LINK TO THE LOGIN PAGE, deliberately.

      A suspended, closed or under-maintenance business is not a session problem. The
      credentials are perfectly valid — the API's gate 10 reads business status live and
      refuses on that basis — so signing in again succeeds and the very next request is
      refused identically. Offering "sign in again" here builds a loop with no exit and
      no explanation, which is the specific failure this page exists to avoid.

      Sign OUT is offered, because leaving is a real thing the user may want to do.
--}}
<x-error-page
    title="Your business account is unavailable"
    icon="building"
    :tone="($isTemporary ?? false) ? 'amber' : 'rose'"
    :message="$message"
>
    @if ($isTemporary ?? false)
        <a href="{{ url()->current() }}" class="btn-primary">Try again</a>
    @endif

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn-secondary">Sign out</button>
    </form>
</x-error-page>
