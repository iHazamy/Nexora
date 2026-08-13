{{--
    The fallback: the API refused in a way this app has no specific handling for.

    ! Shows the API's own message, which is written for users. The response_code that
      would identify the cause precisely is in the API's error_logs, visible to an
      operator — deliberately not to the caller.
--}}
<x-error-page
    title="Something went wrong"
    icon="warning"
    tone="rose"
    :detail="$detail ?? null"
    :message="$message"
>
    <a href="{{ url()->previous() }}" class="btn-primary">Go back</a>
    <a href="{{ route('dashboard') }}" class="btn-secondary">Dashboard</a>
</x-error-page>
