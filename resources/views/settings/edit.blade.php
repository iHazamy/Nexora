@extends('layouts.app')

@section('title', 'Settings')

@php
    /*
     * ! A DISPLAY DECISION ONLY. The API refuses the PUT for anyone but an owner
     *   regardless of what this renders; showing a form to someone who cannot save it
     *   would just be a slower refusal.
     */
    $canEdit = $apiSession->canWrite('business', 'U');
    $logoUrl = ! empty($business['logo_path']) ? Storage::disk('public')->url($business['logo_path']) : null;
@endphp

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <h1 class="page-title">Settings</h1>
            <p class="page-subtitle">
                What Runovia prints on your invoices, and where you ask to be paid.
            </p>
        </div>

        @unless ($canEdit)
            <div class="mb-5 alert-info">
                <x-icon name="warning" class="mt-0.5 h-4 w-4 shrink-0" />
                <span>
                    You can see these details because they appear on every invoice you raise.
                    Only the account owner can change them.
                </span>
            </div>
        @endunless

        {{-- ------------------------------------------------------- Company details --}}
        <div class="card mb-6">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Company details</h2>
                    <p class="card-subtitle">These form the letterhead on your invoices.</p>
                </div>
                @if (! empty($business['status']) && $business['status'] !== 'ACTIVE')
                    {{-- Read-only. Status is the API's auth gate and is not writable here. --}}
                    <span class="badge-cancelled">{{ Str::headline(Str::lower($business['status'])) }}</span>
                @endif
            </div>

            @if ($canEdit)
                {{-- ! enctype is required for the logo upload; without it the file silently
                     ! does not arrive and the form appears to ignore it. --}}
                <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="card-body space-y-5">
                        <x-form.input name="name" label="Business name" :value="$business['name'] ?? null" required />

                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-form.input name="email" label="Email" type="email" :value="$business['email'] ?? null" />
                            <x-form.input name="phone" label="Phone" type="tel" :value="$business['phone'] ?? null" />
                        </div>

                        <x-form.textarea name="address" label="Address" rows="3" :value="$business['address'] ?? null" />

                        <x-form.input
                            name="registration_number"
                            label="Registration number"
                            :value="$business['registration_number'] ?? null"
                            hint="Your SSM number, if you want it on the invoice."
                        />

                        <x-form.textarea
                            name="invoice_terms"
                            label="Payment terms"
                            rows="4"
                            :value="$business['invoice_terms'] ?? null"
                            hint="Printed at the foot of every invoice. One term per line."
                            placeholder="1. Deposit of 50% due on booking.&#10;2. Balance due 14 days before the event."
                        />

                        <div class="field">
                            <span class="field-label">Logo</span>

                            @if ($logoUrl)
                                <div class="flex items-center gap-4">
                                    <img src="{{ $logoUrl }}" alt="Current logo"
                                         class="h-14 w-auto rounded border border-slate-200 bg-white p-1">
                                    <label class="flex items-center gap-2 text-sm text-slate-600">
                                        <input type="checkbox" name="remove_logo" value="1"
                                               class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                        Remove logo
                                    </label>
                                </div>
                            @endif

                            <input type="file" name="logo" accept="image/jpeg,image/png,image/webp,image/svg+xml"
                                   class="input @if ($errors->has('logo')) border-rose-400 @endif">
                            <p class="field-hint">JPG, PNG, WebP or SVG. 2MB maximum.</p>
                            @error('logo')
                                <p class="field-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex justify-end border-t border-slate-200/80 px-5 py-4 sm:px-6">
                        <button type="submit" class="btn-primary">Save settings</button>
                    </div>
                </form>
            @else
                {{-- Read-only rendering for staff. --}}
                <div class="card-body">
                    <dl class="grid gap-x-8 gap-y-4 sm:grid-cols-2">
                        @foreach ([
                            'Business name' => $business['name'] ?? null,
                            'Email' => $business['email'] ?? null,
                            'Phone' => $business['phone'] ?? null,
                            'Registration number' => $business['registration_number'] ?? null,
                        ] as $label => $value)
                            <div>
                                <dt class="metric-label">{{ $label }}</dt>
                                <dd class="mt-1 text-sm text-slate-800">
                                    {{ $value ?: '—' }}
                                </dd>
                            </div>
                        @endforeach

                        <div class="sm:col-span-2">
                            <dt class="metric-label">Address</dt>
                            <dd class="mt-1 whitespace-pre-line text-sm text-slate-800">
                                {{ $business['address'] ?: '—' }}
                            </dd>
                        </div>

                        <div class="sm:col-span-2">
                            <dt class="metric-label">Payment terms</dt>
                            <dd class="mt-1 whitespace-pre-line text-sm text-slate-800">
                                {{ $business['invoice_terms'] ?: '—' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            @endif
        </div>

        {{-- ---------------------------------------------------------- Bank accounts --}}
        <div class="card" x-data="{ editing: null, adding: false }">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Bank accounts</h2>
                    <p class="card-subtitle">
                        An invoice names one of these so the customer knows where to pay.
                    </p>
                </div>

                @if ($canEdit)
                    <button type="button" class="btn-secondary btn-sm"
                            x-on:click="adding = ! adding; editing = null">
                        <x-icon name="plus" class="h-3.5 w-3.5" />
                        Add account
                    </button>
                @endif
            </div>

            {{-- Add form --}}
            @if ($canEdit)
                <div x-show="adding" x-cloak class="border-b border-slate-200 bg-slate-50/60 px-5 py-4 sm:px-6">
                    <form method="POST" action="{{ route('bank-accounts.store') }}" class="space-y-4">
                        @csrf
                        <div class="grid gap-4 sm:grid-cols-3">
                            <x-form.input name="bank_name" label="Bank" placeholder="Maybank" />
                            {{-- ! type="text", never number. Malaysian account numbers carry
                                 ! leading zeros that a numeric input would strip. --}}
                            <x-form.input name="account_number" label="Account number" type="text"
                                          inputmode="numeric" placeholder="512345678901" />
                            <x-form.input name="account_holder" label="Account holder"
                                          placeholder="Runovia Sdn Bhd" />
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" class="btn-secondary btn-sm" x-on:click="adding = false">
                                Cancel
                            </button>
                            <button type="submit" class="btn-primary btn-sm">Add account</button>
                        </div>
                    </form>
                </div>
            @endif

            @if ($bankAccounts === [])
                <x-empty-state
                    icon="bank"
                    title="No bank account yet"
                    text="Without one, your invoices will not show payment details."
                />
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($bankAccounts as $account)
                        <li class="px-5 py-4 sm:px-6">
                            <div x-show="editing !== {{ $account['id'] }}">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="flex items-center gap-2 font-medium text-slate-900">
                                            {{ $account['bank_name'] }}
                                            @unless ($account['active'])
                                                <span class="badge-neutral">Inactive</span>
                                            @endunless
                                        </p>
                                        {{-- The string, exactly as stored. Never cast. --}}
                                        <p class="text-sm text-slate-700 tabular">{{ $account['account_number'] }}</p>
                                        @if (! empty($account['account_holder']))
                                            <p class="text-xs text-slate-500">{{ $account['account_holder'] }}</p>
                                        @endif
                                    </div>

                                    @if ($canEdit)
                                        <div class="flex items-center gap-1">
                                            <button type="button" class="btn-ghost btn-sm"
                                                    x-on:click="editing = {{ $account['id'] }}; adding = false">
                                                <x-icon name="pencil" class="h-3.5 w-3.5" />
                                                <span class="sr-only">Edit</span>
                                            </button>

                                            @if ($account['active'])
                                                {{-- ! The bank-account request marks this path with
                                                     ! `intent`, not `deactivate`. --}}
                                                <x-deactivate-form
                                                    :action="route('bank-accounts.update', $account['id'])"
                                                    marker="intent"
                                                    marker-value="deactivate"
                                                    :confirm="'Deactivate ' . $account['bank_name'] . '? Invoices that already name it are unchanged.'"
                                                />
                                            @endif

                                            <x-delete-form
                                                :action="route('bank-accounts.destroy', $account['id'])"
                                                :confirm="'Delete ' . $account['bank_name'] . ' ' . $account['account_number'] . '? If an invoice names it, the API will refuse and suggest deactivating instead.'"
                                                :label="'Delete ' . $account['bank_name']"
                                                icon-only
                                            />
                                        </div>
                                    @endif
                                </div>
                            </div>

                            @if ($canEdit)
                                <div x-show="editing === {{ $account['id'] }}" x-cloak>
                                    <form method="POST" action="{{ route('bank-accounts.update', $account['id']) }}"
                                          class="space-y-4">
                                        @csrf
                                        @method('PUT')
                                        <div class="grid gap-4 sm:grid-cols-3">
                                            <x-form.input name="bank_name" label="Bank"
                                                          :value="$account['bank_name']" />
                                            <x-form.input name="account_number" label="Account number" type="text"
                                                          inputmode="numeric" :value="$account['account_number']" />
                                            <x-form.input name="account_holder" label="Account holder"
                                                          :value="$account['account_holder']" />
                                        </div>
                                        <x-form.checkbox name="active" label="Available for new invoices"
                                                         :checked="$account['active']" />
                                        <div class="flex justify-end gap-2">
                                            <button type="button" class="btn-secondary btn-sm"
                                                    x-on:click="editing = null">Cancel</button>
                                            <button type="submit" class="btn-primary btn-sm">Save</button>
                                        </div>
                                    </form>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endsection
