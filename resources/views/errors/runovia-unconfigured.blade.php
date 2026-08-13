{{--
    ! A SETUP mistake, not an outage, and the wording says so. "Please try again
      shortly" would send a developer looking at the API's health for an hour when the
      answer is two blank lines in .env.
--}}
<x-error-page
    title="Runovia is not configured"
    icon="settings"
    tone="amber"
    :detail="$detail ?? null"
    message="This application has no API credentials, so it cannot reach Runovia. An administrator needs to finish setting it up."
>
    @if (config('app.debug'))
        <div class="w-full rounded-lg bg-slate-50 p-4 text-left">
            <p class="text-xs font-semibold text-slate-700">To fix this, on the API host run:</p>
            <pre class="mt-2 overflow-x-auto text-xs text-slate-600">php database/create-app-client.php \
    --name="Runovia Web" --app=RV --client=web</pre>
            <p class="mt-3 text-xs text-slate-600">
                Then put the printed <code class="font-mono">client_id</code> and
                <code class="font-mono">secret</code> into this app's
                <code class="font-mono">.env</code> as
                <code class="font-mono">RUNOVIA_API_CLIENT_ID</code> and
                <code class="font-mono">RUNOVIA_API_CLIENT_SECRET</code>.
                The secret is printed once and is not recoverable.
            </p>
        </div>
    @endif
</x-error-page>
