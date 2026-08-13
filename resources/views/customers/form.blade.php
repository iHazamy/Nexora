{{--
    ! One view for create and edit. `$customer` is null on create — that is the only
      difference, and keeping them in one file is what stops the two forms drifting apart
      field by field over time.
--}}
@extends('layouts.app')

@php
    $isEdit = $customer !== null;
@endphp

@section('title', $isEdit ? 'Edit ' . $customer['name'] : 'New customer')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="mb-6">
            <a href="{{ $isEdit ? route('customers.show', $customer['id']) : route('customers.index') }}"
               class="mb-3 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700">
                <x-icon name="chevron-left" class="h-3.5 w-3.5" />
                {{ $isEdit ? 'Back to customer' : 'Back to customers' }}
            </a>
            <h1 class="page-title">{{ $isEdit ? 'Edit customer' : 'New customer' }}</h1>
        </div>

        <form method="POST"
              action="{{ $isEdit ? route('customers.update', $customer['id']) : route('customers.store') }}">
            @csrf
            {{-- ! PUT is spoofed. Browsers can only send GET and POST from a form. --}}
            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="card">
                <div class="card-body space-y-5">
                    <x-form.input
                        name="name"
                        label="Name"
                        :value="$customer['name'] ?? null"
                        placeholder="Sarah &amp; Ali"
                        autofocus
                        required
                    />

                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-form.input
                            name="email"
                            label="Email"
                            type="email"
                            :value="$customer['email'] ?? null"
                            placeholder="sarah.ali@example.com"
                        />

                        <x-form.input
                            name="phone"
                            label="Phone"
                            type="tel"
                            :value="$customer['phone'] ?? null"
                            placeholder="012-3456789"
                        />
                    </div>

                    <x-form.textarea
                        name="address"
                        label="Address"
                        rows="3"
                        :value="$customer['address'] ?? null"
                        placeholder="8 Jalan Mawar, 50000 Kuala Lumpur"
                    />

                    <x-form.textarea
                        name="notes"
                        label="Notes"
                        rows="3"
                        :value="$customer['notes'] ?? null"
                        hint="Internal only — never shown on an invoice."
                        placeholder="Wedding on 12 December. Prefers WhatsApp."
                    />
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-slate-200/80 px-5 py-4 sm:px-6">
                    <a href="{{ $isEdit ? route('customers.show', $customer['id']) : route('customers.index') }}"
                       class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        {{ $isEdit ? 'Save changes' : 'Add customer' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
