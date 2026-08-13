{{--
    Navigation, shared by the desktop rail and the mobile drawer.

    ! Items are hidden by ROLE using the display hints, so a member does not see a
      "Settings" link that will refuse them. Read access is what gates visibility
      here, not write access: staff can READ the business and its bank accounts —
      those details go on every invoice they raise — they just cannot change them.
      The Settings screen itself renders read-only for them.
--}}
@php
    /**
     * Marks a nav item active for its whole section, so /customers/4/edit still
     * highlights "Customers".
     */
    $isSection = static fn (string $pattern): bool => request()->routeIs($pattern);
@endphp

<div class="flex h-16 shrink-0 items-center gap-2.5 px-5">
    <x-brand-mark class="h-8 w-8" />
    <span class="text-base font-bold tracking-tight text-white">{{ config('app.name') }}</span>
</div>

<nav class="flex-1 space-y-1 overflow-y-auto px-3 pb-6" aria-label="Main">
    <a href="{{ route('dashboard') }}"
       class="sidebar-link @if ($isSection('dashboard')) sidebar-link-active @endif">
        <x-icon name="dashboard" class="h-5 w-5 shrink-0" />
        Dashboard
    </a>

    <p class="sidebar-heading">Billing</p>

    <a href="{{ route('invoices.index') }}"
       class="sidebar-link @if ($isSection('invoices.*')) sidebar-link-active @endif">
        <x-icon name="invoice" class="h-5 w-5 shrink-0" />
        Invoices
    </a>

    <a href="{{ route('payments.index') }}"
       class="sidebar-link @if ($isSection('payments.*')) sidebar-link-active @endif">
        <x-icon name="payments" class="h-5 w-5 shrink-0" />
        Payments
    </a>

    <p class="sidebar-heading">Catalogue</p>

    <a href="{{ route('customers.index') }}"
       class="sidebar-link @if ($isSection('customers.*')) sidebar-link-active @endif">
        <x-icon name="customers" class="h-5 w-5 shrink-0" />
        Customers
    </a>

    <a href="{{ route('products.index') }}"
       class="sidebar-link @if ($isSection('products.*')) sidebar-link-active @endif">
        <x-icon name="products" class="h-5 w-5 shrink-0" />
        Products &amp; services
    </a>

    <a href="{{ route('packages.index') }}"
       class="sidebar-link @if ($isSection('packages.*')) sidebar-link-active @endif">
        <x-icon name="packages" class="h-5 w-5 shrink-0" />
        Packages
    </a>

    <p class="sidebar-heading">Business</p>

    <a href="{{ route('settings.edit') }}"
       class="sidebar-link @if ($isSection('settings.*') || $isSection('bank-accounts.*')) sidebar-link-active @endif">
        <x-icon name="settings" class="h-5 w-5 shrink-0" />
        Settings
    </a>

    {{--
        ! The activity log is read-only for OW and MG and unreachable for MB —
          Module::forRole() does not give a member the logs module at all, so the API
          would answer 403. Hiding it is the honest thing to do.
    --}}
    @if ($apiSession->canWrite('logs', 'R'))
        <a href="{{ route('activity.index') }}"
           class="sidebar-link @if ($isSection('activity.*')) sidebar-link-active @endif">
            <x-icon name="activity" class="h-5 w-5 shrink-0" />
            Activity log
        </a>
    @endif
</nav>

<div class="border-t border-white/10 px-5 py-4">
    <p class="truncate text-sm font-medium text-white">{{ $apiSession->userName() }}</p>
    <p class="truncate text-xs text-slate-400">{{ $apiSession->userEmail() }}</p>
</div>
