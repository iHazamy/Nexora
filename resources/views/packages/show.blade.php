@extends('layouts.app')

@section('title', $package['name'])

@php
    use App\Support\Money;

    $itemsTotal = $package['items_total'] ?? null;
@endphp

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <a href="{{ route('packages.index') }}"
               class="mb-3 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700">
                <x-icon name="chevron-left" class="h-3.5 w-3.5" />
                Back to packages
            </a>

            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="page-title">{{ $package['name'] }}</h1>
                        @unless ($package['active'])
                            <span class="badge-neutral">Inactive</span>
                        @endunless
                    </div>
                    @if (! empty($package['description']))
                        <p class="page-subtitle">{{ $package['description'] }}</p>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if ($apiSession->canUpdate('packages'))
                        <a href="{{ route('packages.edit', $package['id']) }}" class="btn-secondary">
                            <x-icon name="pencil" class="h-4 w-4" />
                            Edit
                        </a>
                    @endif

                    @if ($apiSession->canUpdate('packages') && $package['active'])
                        <x-deactivate-form
                            :action="route('packages.update', $package['id'])"
                            :confirm="'Deactivate ' . $package['name'] . '? It stays on invoices that already use it.'"
                        />
                    @endif

                    @if ($apiSession->canDelete('packages'))
                        <x-delete-form
                            :action="route('packages.destroy', $package['id'])"
                            :confirm="'Delete ' . $package['name'] . '? This cannot be undone.'"
                            class="btn-danger"
                        />
                    @endif
                </div>
            </div>
        </div>

        {{-- ------------------------------------------------------------- Pricing --}}
        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <div class="card"><div class="card-body">
                <p class="metric-label">Package price</p>
                <p class="metric-value">{{ Money::format($package['price']) }}</p>
                <p class="metric-note">What the customer pays</p>
            </div></div>

            @if ($itemsTotal !== null)
                <div class="card"><div class="card-body">
                    <p class="metric-label">Contents at list price</p>
                    <p class="metric-value text-slate-600">{{ Money::format($itemsTotal) }}</p>
                    <p class="metric-note">Sum of the items below</p>
                </div></div>

                <div class="card"><div class="card-body">
                    @php
                        /*
                         * ! Compared, never reconciled. A package price BELOW its contents is the
                         *   normal case and the entire point of selling a bundle; a package priced
                         *   above them is unusual but legitimate (a service premium), so neither is
                         *   treated as an error.
                         *
                         * ? Comparing on the cent integers rather than on floats, so a package
                         *   priced exactly at its contents reads as "no saving" and not as a
                         *   rounding artefact.
                         */
                        $priceCents = (int) round(((float) $package['price']) * 100);
                        $itemsCents = (int) round(((float) $itemsTotal) * 100);
                        $savingCents = $itemsCents - $priceCents;
                    @endphp

                    <p class="metric-label">{{ $savingCents >= 0 ? 'Customer saves' : 'Premium' }}</p>
                    <p @class([
                        'metric-value',
                        'text-emerald-700' => $savingCents > 0,
                        'text-slate-400'   => $savingCents === 0,
                        'text-amber-700'   => $savingCents < 0,
                    ])>
                        {{ Money::format(number_format(abs($savingCents) / 100, 2, '.', '')) }}
                    </p>
                    <p class="metric-note">
                        @if ($savingCents > 0)
                            Bundled below list price
                        @elseif ($savingCents === 0)
                            Priced at list
                        @else
                            Priced above list
                        @endif
                    </p>
                </div></div>
            @endif
        </div>

        {{-- --------------------------------------------------------------- Items --}}
        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">What is included</h2>
                    <p class="card-subtitle">
                        For your reference and pricing. An invoice shows the package as one line.
                    </p>
                </div>
            </div>

            @if (($package['items'] ?? []) === [])
                <x-empty-state
                    icon="products"
                    title="No contents recorded"
                    text="This package has a price but no itemised contents. That is valid — add them if you want the breakdown."
                >
                    @if ($apiSession->canUpdate('packages'))
                        <a href="{{ route('packages.edit', $package['id']) }}" class="btn-secondary btn-sm">
                            Add contents
                        </a>
                    @endif
                </x-empty-state>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Product</th>
                                <th scope="col" class="numeric text-right">Qty</th>
                                <th scope="col" class="numeric text-right">Unit price</th>
                                <th scope="col" class="numeric text-right">Line total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($package['items'] as $item)
                                <tr>
                                    <td class="text-slate-800">
                                        {{ $item['product_name'] ?? ('Product #' . $item['product_id']) }}
                                    </td>
                                    <td class="numeric text-right text-slate-600">{{ $item['quantity'] }}</td>
                                    <td class="numeric text-right text-slate-600">
                                        {{ Money::amount($item['unit_price']) }}
                                    </td>
                                    <td class="numeric text-right font-medium text-slate-900">
                                        {{ Money::amount($item['line_total'] ?? $item['unit_price']) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
