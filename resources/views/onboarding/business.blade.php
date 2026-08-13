{{--
    ! Uses the GUEST layout, not the app shell. The sidebar links to customers,
      invoices and settings — every one of which the API refuses for a user with no
      business — so rendering the shell here would surround the one thing they CAN do
      with eight things they cannot.
--}}
@extends('layouts.guest')

@section('title', 'Set up your business')
@section('tagline', 'One more step')

@section('content')
    <form method="POST" action="{{ route('onboarding.business.store') }}" class="space-y-5">
        @csrf

        <p class="text-sm text-slate-600">
            These details appear on the invoices you send. You can change them later in Settings.
        </p>

        <x-form.input name="name" label="Business name" autofocus required
                      placeholder="Runovia Wedding Venue" />

        <x-form.input name="email" label="Business email" type="email"
                      placeholder="hello@venue.example" hint="Optional." />

        <x-form.input name="phone" label="Business phone" type="tel"
                      placeholder="03-12345678" hint="Optional." />

        <x-form.textarea name="address" label="Address" rows="3"
                         placeholder="12 Jalan Contoh, 50000 Kuala Lumpur" />

        <button type="submit" class="btn-primary w-full">Create business</button>
    </form>
@endsection

@section('footer')
    <form method="POST" action="{{ route('logout') }}" class="inline">
        @csrf
        <button type="submit" class="text-slate-500 underline hover:text-slate-700">Sign out instead</button>
    </form>
@endsection
