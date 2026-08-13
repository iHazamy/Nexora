{{--
    The "Deactivate" action, as a real form.

    ! A POST/PUT form, never a link — a GET that changes a record is followed by
      crawlers, prefetch and "open all in tabs", and it cannot carry a CSRF token.

    ! It posts to products.update with nothing but the `deactivate` marker, because the
      route table has no products.deactivate endpoint. UpdateProductController reads the
      marker and calls ProductApi::deactivate(); ProductRequest skips the field rules for
      this shape, since none of them are submitted.

    ? Why this exists at all: the API refuses to delete a product that a package or an
      invoice references (409) and its message says to deactivate it instead. The
      refusal is bounced back to the page the user was on, so the remedy has to be
      ON that page — otherwise the user reads advice they cannot act on.

    @include('products.partials.deactivate', ['action' => ..., 'confirm' => ...])
--}}
<form method="POST" action="{{ $action }}"
      onsubmit="return confirm(@js($confirm ?? 'Deactivate this item? It stays on existing invoices.'))"
      class="inline">
    @csrf
    @method('PUT')

    <input type="hidden" name="deactivate" value="1">

    {{-- Text, not an icon. An X or a power symbol next to a bin reads as a second
         delete, and this action is the opposite of one. --}}
    <button type="submit" class="{{ $class ?? 'btn-secondary btn-sm' }}">
        Deactivate
    </button>
</form>
