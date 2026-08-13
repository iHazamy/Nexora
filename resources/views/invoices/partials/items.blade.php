{{--
    The line-item editor.

    ! When $locked, every row renders as TEXT with no input and no `name` attribute, so no
      `items` key is submitted at all. The API treats a present `items` key as a wholesale
      replacement and refuses it outright once payments exist — so "disabled inputs" would
      not be enough here, because a disabled input submits nothing but a readonly one does.
--}}
@php
    use App\Support\Money;
@endphp

<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">Items</h2>
            <p class="card-subtitle">
                @if ($locked)
                    Locked — this invoice has payments recorded.
                @else
                    Pick from your catalogue, or type a one-off line.
                @endif
            </p>
        </div>

        @unless ($locked)
            <button type="button" class="btn-secondary btn-sm" x-on:click="addLine()">
                <x-icon name="plus" class="h-3.5 w-3.5" />
                Add line
            </button>
        @endunless
    </div>

    @if ($locked)
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">Description</th>
                        <th scope="col" class="numeric text-right">Qty</th>
                        <th scope="col" class="numeric text-right">Unit price</th>
                        <th scope="col" class="numeric text-right">Discount</th>
                        <th scope="col" class="numeric text-right">Line total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice['items'] as $item)
                        <tr>
                            <td class="text-slate-800">{{ $item['description'] }}</td>
                            <td class="numeric text-right text-slate-600">{{ $item['quantity'] }}</td>
                            <td class="numeric text-right text-slate-600">{{ Money::amount($item['unit_price']) }}</td>
                            <td class="numeric text-right text-slate-600">
                                {{ Money::isZero($item['discount'] ?? '0.00') ? '—' : '− ' . Money::amount($item['discount']) }}
                            </td>
                            <td class="numeric text-right font-medium text-slate-900">
                                {{ Money::amount($item['line_total']) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col" class="min-w-[9rem]">From catalogue</th>
                        <th scope="col" class="min-w-[12rem]">Description</th>
                        <th scope="col" class="w-24 numeric">Qty</th>
                        <th scope="col" class="w-32 numeric">Unit price</th>
                        <th scope="col" class="w-32 numeric">Discount</th>
                        <th scope="col" class="w-28 numeric text-right">Line total</th>
                        <th scope="col" class="w-10"><span class="sr-only">Remove</span></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(line, index) in lines" :key="line.key">
                        <tr>
                            {{-- ! The value encodes the KIND as well as the id, because product
                                 ! and package ids overlap and a line must reference one or the
                                 ! other, never both. --}}
                            <td>
                                <select class="input input-sm"
                                        x-bind:value="catalogValue(line)"
                                        x-on:change="applyCatalog(index, $event.target.value)">
                                    <option value="">One-off line</option>
                                    <optgroup label="Packages">
                                        <template x-for="pkg in catalog.packages" :key="'p' + pkg.id">
                                            <option x-bind:value="'package:' + pkg.id" x-text="pkg.name"></option>
                                        </template>
                                    </optgroup>
                                    <optgroup label="Products &amp; services">
                                        <template x-for="product in catalog.products" :key="'r' + product.id">
                                            <option x-bind:value="'product:' + product.id"
                                                    x-text="product.name"></option>
                                        </template>
                                    </optgroup>
                                </select>

                                {{-- Only one of these ever carries a value; see applyCatalog(). --}}
                                <input type="hidden" x-bind:name="`items[${index}][product_id]`"
                                       x-bind:value="line.product_id">
                                <input type="hidden" x-bind:name="`items[${index}][package_id]`"
                                       x-bind:value="line.package_id">
                            </td>

                            <td>
                                <input type="text" class="input input-sm"
                                       x-bind:name="`items[${index}][description]`"
                                       x-model="line.description"
                                       placeholder="What is being charged for">
                            </td>

                            <td>
                                <input type="number" step="0.01" min="0.01" class="input input-sm text-right"
                                       x-bind:name="`items[${index}][quantity]`"
                                       x-model="line.quantity">
                            </td>

                            <td>
                                <input type="number" step="0.01" min="0" class="input input-sm text-right"
                                       x-bind:name="`items[${index}][unit_price]`"
                                       x-model="line.unit_price">
                            </td>

                            <td>
                                <input type="number" step="0.01" min="0" class="input input-sm text-right"
                                       x-bind:name="`items[${index}][discount]`"
                                       x-model="line.discount"
                                       x-bind:aria-invalid="lineDiscountTooLarge(line) ? 'true' : null">
                            </td>

                            <td class="numeric text-right align-middle font-medium text-slate-900"
                                x-text="lineTotal(line)"></td>

                            <td class="align-middle">
                                <button type="button" class="btn-ghost btn-sm text-slate-400 hover:text-rose-600"
                                        x-on:click="removeLine(index)">
                                    <x-icon name="close" class="h-3.5 w-3.5" />
                                    <span class="sr-only">Remove line</span>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Server-side item errors, which are keyed items.N.field. --}}
        @if ($errors->hasAny(['items', 'items.*']))
            <div class="border-t border-slate-200 px-5 py-4 sm:px-6">
                <div class="alert-error">
                    <x-icon name="warning" class="mt-0.5 h-4 w-4 shrink-0" />
                    <ul class="space-y-1">
                        @foreach ($errors->get('items.*') as $messages)
                            @foreach ($messages as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        @endforeach
                        @foreach ($errors->get('items') as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="border-t border-slate-200/80 px-5 py-3 sm:px-6">
            <p class="text-xs text-slate-500">
                A line discount comes off that line before the subtotal. The invoice discount
                below comes off the whole bill.
            </p>
        </div>
    @endif
</div>
