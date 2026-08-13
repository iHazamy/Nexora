{{--
    ! One view for create and edit. `$product` is null on create — that is the only
      difference, and keeping them in one file is what stops the two forms drifting apart
      field by field.

    ! On edit this IS the detail view. The resource has no show route on purpose, so
      everything a user might want to do with a product — read it, change it, deactivate
      it, delete it — has to be reachable from here.
--}}
@extends('layouts.app')

@php
    $isEdit = $product !== null;
    $isActive = $isEdit && (bool) ($product['active'] ?? false);
@endphp

@section('title', $isEdit ? 'Edit ' . $product['name'] : 'New product or service')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="mb-6">
            <a href="{{ route('products.index') }}"
               class="mb-3 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700">
                <x-icon name="chevron-left" class="h-3.5 w-3.5" />
                Back to products &amp; services
            </a>

            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="page-title">
                        {{ $isEdit ? $product['name'] : 'New product or service' }}
                        @if ($isEdit && ! $isActive)
                            <span class="badge-neutral align-middle">Inactive</span>
                        @endif
                    </h1>
                    @if ($isEdit)
                        <p class="page-subtitle">
                            Added {{ \App\Support\DisplayDate::instant($product['created_at'] ?? null, 'j M Y') }}
                            &middot;
                            last changed
                            {{ \App\Support\DisplayDate::instant($product['updated_at'] ?? null, 'j M Y, g:ia') }}
                        </p>
                    @endif
                </div>

                {{--
                    ! Deactivate and Delete sit OUTSIDE the edit form below. Each is its own
                      form, and a form cannot be nested inside another — the browser would
                      drop the inner one and the button would submit the wrong thing.
                --}}
                @if ($isEdit)
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($isActive && $apiSession->canUpdate('products'))
                            @include('products.partials.deactivate', [
                                'action' => route('products.update', $product['id']),
                                'class' => 'btn-secondary',
                                'confirm' => 'Deactivate ' . $product['name']
                                    . '? It stays on invoices that already use it, but will not be offered'
                                    . ' on new ones.',
                            ])
                        @endif

                        @if ($apiSession->canDelete('products'))
                            <x-delete-form
                                :action="route('products.destroy', $product['id'])"
                                :confirm="'Delete ' . $product['name'] . '? This cannot be undone.'"
                                class="btn-danger"
                            />
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <form method="POST"
              action="{{ $isEdit ? route('products.update', $product['id']) : route('products.store') }}">
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
                        :value="$product['name'] ?? null"
                        placeholder="Hall hire — full day"
                        autofocus
                        required
                    />

                    <div class="grid gap-5 sm:grid-cols-2">
                        {{--
                            ! One resource discriminated by `type`, not two resources. The
                              only thing that differs between a product and a service in
                              this system is this field.
                        --}}
                        <x-form.select
                            name="type"
                            label="Type"
                            :value="$product['type'] ?? 'SERVICE'"
                            :options="['PRODUCT' => 'Product — a physical item', 'SERVICE' => 'Service — work or time']"
                            required
                        />

                        {{--
                            ! Money::input(), never a raw echo. The API sends a fixed-2
                              DECIMAL STRING and this repopulates the field with exactly
                              that string — no float cast on the way in or out.

                            ? step="0.01" because the column holds two decimals. A browser
                              that enforces the step saves the user a round trip to the
                              API's refusal.
                        --}}
                        <x-form.input
                            name="price"
                            label="Price"
                            type="number"
                            step="0.01"
                            min="0"
                            inputmode="decimal"
                            prefix="{{ \App\Support\Money::CURRENCY }}"
                            :value="$isEdit ? \App\Support\Money::input($product['price']) : null"
                            placeholder="0.00"
                            hint="Use 0.00 if this item is free."
                            required
                        />
                    </div>

                    <x-form.textarea
                        name="description"
                        label="Description"
                        rows="3"
                        :value="$product['description'] ?? null"
                        hint="Shown as the default line description when this item goes on an invoice."
                        placeholder="Includes tables, chairs and PA system. 8am–6pm."
                    />

                    <div class="border-t border-slate-200/80 pt-5">
                        {{--
                            ! Deactivating is how something leaves the catalogue. Deleting is
                              refused by the API once a package or an invoice references the
                              item, so this checkbox — not the bin — is the normal way to
                              retire one.
                        --}}
                        <x-form.checkbox
                            name="active"
                            label="Active"
                            :checked="$product['active'] ?? true"
                            hint="Inactive items stay on invoices that already use them, but are not offered on new ones."
                        />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-slate-200/80 px-5 py-4 sm:px-6">
                    <a href="{{ route('products.index') }}" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        {{ $isEdit ? 'Save changes' : 'Add item' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
