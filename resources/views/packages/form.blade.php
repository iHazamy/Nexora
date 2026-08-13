{{--
    ! THE `items_submitted` MARKER BELOW IS LOAD-BEARING. To the API, a present `items` key
      replaces the package's contents wholesale and an absent one leaves them alone — and a
      user who removes every row submits no `items[]` inputs at all, which on the wire is
      identical to a form that never managed the contents. The marker is how this form says
      "I am in charge of the item list", so that emptying a package is possible and editing
      the name alone cannot silently gut it. See PackageRequest::managesItems().
--}}
@extends('layouts.app')

@php
    use App\Support\Money;

    $isEdit = $package !== null;

    // Repopulate from old() first, so a refused submission does not lose the user's rows.
    $rows = old('items') ?? array_map(static fn (array $item): array => [
        'product_id' => (string) $item['product_id'],
        'quantity'   => Money::input($item['quantity']),
        'unit_price' => Money::input($item['unit_price']),
    ], $package['items'] ?? []);

    $catalogue = array_map(static fn (array $p): array => [
        'id' => (string) $p['id'], 'name' => $p['name'], 'price' => $p['price'],
    ], $products);
@endphp

@section('title', $isEdit ? 'Edit ' . $package['name'] : 'New package')

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="mb-6">
            <a href="{{ $isEdit ? route('packages.show', $package['id']) : route('packages.index') }}"
               class="mb-3 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700">
                <x-icon name="chevron-left" class="h-3.5 w-3.5" />
                {{ $isEdit ? 'Back to package' : 'Back to packages' }}
            </a>
            <h1 class="page-title">{{ $isEdit ? 'Edit package' : 'New package' }}</h1>
        </div>

        <form method="POST"
              action="{{ $isEdit ? route('packages.update', $package['id']) : route('packages.store') }}"
              x-data="{
                  rows: @js($rows),
                  catalogue: @js($catalogue),
                  nextKey: 0,
                  keys: [],
                  init() { this.rows.forEach(() => this.keys.push(this.nextKey++)); if (this.rows.length === 0) this.addRow(); },
                  addRow() { this.rows.push({ product_id: '', quantity: '1', unit_price: '' }); this.keys.push(this.nextKey++); },
                  removeRow(i) { this.rows.splice(i, 1); this.keys.splice(i, 1); },
                  product(id) { return this.catalogue.find(p => p.id === String(id)); },
                  /* ! The effective price is the product's CURRENT price when the field is left
                       blank — which is what the API does too. Showing 0 there would suggest the
                       line is free. */
                  effectivePrice(row) {
                      if (row.unit_price !== '' && row.unit_price !== null) return Number(row.unit_price) || 0;
                      const p = this.product(row.product_id);
                      return p ? Number(p.price) || 0 : 0;
                  },
                  lineTotal(row) { return this.effectivePrice(row) * (Number(row.quantity) || 0); },
                  get itemsTotal() { return this.rows.reduce((sum, r) => sum + this.lineTotal(r), 0); },
                  money(v) { return (Number(v) || 0).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
              }">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            {{-- See the note at the top of this file. --}}
            <input type="hidden" name="items_submitted" value="1">

            <div class="space-y-6">
                <div class="card">
                    <div class="card-header"><h2 class="card-title">Package details</h2></div>
                    <div class="card-body space-y-5">
                        <x-form.input name="name" label="Package name" :value="$package['name'] ?? null"
                                      placeholder="Wedding Gold Package" autofocus required />

                        <x-form.textarea name="description" label="Description" rows="2"
                                         :value="$package['description'] ?? null"
                                         placeholder="Venue, tables, decoration, catering, PA system." />

                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-form.input
                                name="price"
                                label="Package price"
                                type="number"
                                step="0.01"
                                min="0"
                                :value="Money::input($package['price'] ?? '0.00')"
                                :prefix="Money::CURRENCY"
                                hint="What you sell the bundle for. Independent of the items below."
                                required
                            />

                            <div class="flex items-end">
                                <x-form.checkbox
                                    name="active"
                                    label="Available for new invoices"
                                    :checked="$package['active'] ?? true"
                                    hint="Turn off to retire it without affecting past invoices."
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">What is included</h2>
                            <p class="card-subtitle">Printed nowhere — this is for your own reference and pricing.</p>
                        </div>
                        <button type="button" class="btn-secondary btn-sm" x-on:click="addRow()">
                            <x-icon name="plus" class="h-3.5 w-3.5" />
                            Add product
                        </button>
                    </div>

                    @if ($products === [])
                        <x-empty-state
                            icon="products"
                            title="No active products to add"
                            text="A package is built from your products and services. Add one first, or save the package now and add contents later."
                        >
                            <a href="{{ route('products.create') }}" class="btn-secondary btn-sm">Add a product</a>
                        </x-empty-state>
                    @else
                        <div class="table-wrap">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th scope="col" class="min-w-[14rem]">Product</th>
                                        <th scope="col" class="w-24 numeric">Qty</th>
                                        <th scope="col" class="w-40 numeric">Unit price</th>
                                        <th scope="col" class="w-28 numeric text-right">Line</th>
                                        <th scope="col" class="w-10"><span class="sr-only">Remove</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(row, index) in rows" :key="keys[index]">
                                        <tr>
                                            <td>
                                                <select class="input input-sm" x-model="row.product_id"
                                                        x-bind:name="`items[${index}][product_id]`">
                                                    <option value="">Choose a product…</option>
                                                    <template x-for="p in catalogue" :key="p.id">
                                                        <option x-bind:value="p.id" x-text="p.name"></option>
                                                    </template>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0.01"
                                                       class="input input-sm text-right"
                                                       x-model="row.quantity"
                                                       x-bind:name="`items[${index}][quantity]`">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0"
                                                       class="input input-sm text-right"
                                                       x-model="row.unit_price"
                                                       x-bind:name="`items[${index}][unit_price]`"
                                                       x-bind:placeholder="product(row.product_id) ? product(row.product_id).price : '0.00'">
                                            </td>
                                            <td class="numeric text-right align-middle text-slate-700"
                                                x-text="money(lineTotal(row))"></td>
                                            <td class="align-middle">
                                                <button type="button"
                                                        class="btn-ghost btn-sm text-slate-400 hover:text-rose-600"
                                                        x-on:click="removeRow(index)">
                                                    <x-icon name="close" class="h-3.5 w-3.5" />
                                                    <span class="sr-only">Remove</span>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div class="border-t border-slate-200 px-5 py-4 sm:px-6">
                            <p class="field-hint mb-2">
                                Leave a unit price blank to use the product's current price.
                            </p>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-600">Value of contents (estimate)</span>
                                <span class="font-medium text-slate-900 tabular"
                                      x-text="'{{ Money::CURRENCY }} ' + money(itemsTotal)"></span>
                            </div>
                            {{--
                                ! Presented as a comparison, never as a correction. A bundle is
                                  normally cheaper than its parts — that is the point of selling
                                  one — so a package priced below its contents is right, not a
                                  mistake to flag.
                            --}}
                            <p class="mt-1 text-xs text-slate-500">
                                The package price above is what the customer pays. This figure is the
                                contents at list price, for comparison.
                            </p>
                        </div>
                    @endif

                    @if ($errors->hasAny(['items', 'items.*']))
                        <div class="border-t border-slate-200 px-5 py-4 sm:px-6">
                            <div class="alert-error">
                                <x-icon name="warning" class="mt-0.5 h-4 w-4 shrink-0" />
                                <ul class="space-y-1">
                                    @foreach ($errors->get('items.*') as $messages)
                                        @foreach ($messages as $message)<li>{{ $message }}</li>@endforeach
                                    @endforeach
                                    @foreach ($errors->get('items') as $message)<li>{{ $message }}</li>@endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <div class="flex items-center justify-end gap-3 border-t border-slate-200/80 px-5 py-4 sm:px-6">
                        <a href="{{ $isEdit ? route('packages.show', $package['id']) : route('packages.index') }}"
                           class="btn-secondary">Cancel</a>
                        <button type="submit" class="btn-primary">
                            {{ $isEdit ? 'Save changes' : 'Create package' }}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
