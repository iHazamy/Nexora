@extends('layouts.guest')

@section('title', 'Create an account')
@section('tagline', 'Create your account')

@section('content')
    <form method="POST" action="{{ route('register.attempt') }}" class="space-y-5">
        @csrf

        <x-form.input name="name" label="Your name" autocomplete="name" autofocus required />

        <x-form.input name="email" label="Email address" type="email" autocomplete="username" required />

        <x-form.input
            name="phone"
            label="Phone"
            type="tel"
            autocomplete="tel"
            placeholder="012-3456789"
            hint="Optional."
        />

        {{--
            ! maxlength 72 is enforced in the browser as well as in RegisterRequest,
              because bcrypt truncates silently past 72 bytes — a user who pastes a
              90-character passphrase would find only the first 72 ever mattered.
        --}}
        <x-form.input
            name="password"
            label="Password"
            type="password"
            autocomplete="new-password"
            minlength="8"
            maxlength="72"
            hint="At least 8 characters, up to 72."
            required
        />

        <x-form.input
            name="password_confirmation"
            label="Confirm password"
            type="password"
            autocomplete="new-password"
            maxlength="72"
            required
        />

        <button type="submit" class="btn-primary w-full">Create account</button>

        <p class="text-center text-xs text-slate-500">
            You will set up your business details on the next step.
        </p>
    </form>
@endsection

@section('footer')
    Already have an account?
    <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-700">Sign in</a>
@endsection
