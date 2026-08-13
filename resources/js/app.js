import Alpine from 'alpinejs';

import invoiceForm from './invoice-form';
import listFilter from './list-filter';

/*
 * ! Alpine only, and only for the two screens that genuinely need client state:
 *   the invoice line-item editor and the list filters. Everything else in this app
 *   is a server-rendered page and a form POST.
 *
 * ? That is a deliberate step back from the previous app, which hydrated four Alpine
 *   components from server-serialised JSON and then never re-synced them — so the
 *   page and the component could disagree after any redirect. Fewer moving parts
 *   here means fewer places for the display to drift from what the API actually
 *   holds.
 */
Alpine.data('invoiceForm', invoiceForm);
Alpine.data('listFilter', listFilter);

window.Alpine = Alpine;

Alpine.start();
