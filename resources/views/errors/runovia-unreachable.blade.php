<x-error-page
    title="Runovia is unreachable"
    icon="warning"
    tone="rose"
    :detail="$detail ?? null"
    message="We could not reach the Runovia service. Nothing you submitted has been lost — try again in a moment."
>
    <a href="{{ url()->current() }}" class="btn-primary">Try again</a>
    <a href="{{ route('dashboard') }}" class="btn-secondary">Go to dashboard</a>
</x-error-page>
