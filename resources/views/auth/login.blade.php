@extends('layouts.guest')

@section('title', 'Sign in')
@section('tagline', 'Sign in to your account')

@section('content')
    <form method="POST" action="{{ route('login.attempt') }}" class="space-y-5">
        @csrf

        <x-form.input
            name="email"
            label="Email address"
            type="email"
            autocomplete="username"
            autofocus
            required
        />

        <x-form.input
            name="password"
            label="Password"
            type="password"
            autocomplete="current-password"
            required
        />

        <button type="submit" class="btn-primary w-full">Sign in</button>
    </form>
@endsection

@section('footer')
    New to {{ config('app.name') }}?
    <a href="{{ route('register') }}" class="font-semibold text-brand-600 hover:text-brand-700">
        Create an account
    </a>
@endsection
