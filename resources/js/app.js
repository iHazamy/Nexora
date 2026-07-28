import './bootstrap';

import Alpine from 'alpinejs';

window.invoiceForm = (initialItems, deposit, packages, services) => ({
    items: (initialItems.length ? initialItems : [{ description: '', quantity: 1, unit_price: 0, discount: 0 }])
        .map(item => ({ discount: 0, ...item })),
    packages: packages || [],
    services: services || [],
    catalogSearch: '',
    catalogOpen: false,
    deposit: Number(deposit || 0),
    get catalogResults() {
        const entries = [
            ...this.packages.map(p => ({ type: 'Package', id: p.id, name: p.name, price: p.price })),
            ...this.services.map(s => ({ type: 'Service', id: s.id, name: s.name, price: s.price })),
        ];
        const q = this.catalogSearch.trim().toLowerCase();
        return q ? entries.filter(e => e.name.toLowerCase().includes(q)) : entries;
    },
    lineTotal(item) { return Math.max(0, (Number(item.quantity) || 0) * (Number(item.unit_price) || 0) - (Number(item.discount) || 0)); },
    get subtotal() { return this.items.reduce((sum, item) => sum + (Number(item.quantity) || 0) * (Number(item.unit_price) || 0), 0); },
    get discount() { return this.items.reduce((sum, item) => sum + (Number(item.discount) || 0), 0); },
    get grandTotal() { return Math.max(0, this.subtotal - this.discount); },
    get balance() { return Math.max(0, this.grandTotal - (Number(this.deposit) || 0)); },
    isBlank(item) { return !item.description && Number(item.unit_price) === 0 && Number(item.discount) === 0; },
    addItem() { this.items.push({ description: '', quantity: 1, unit_price: 0, discount: 0 }); },
    addLine(description, price) {
        const line = { description, quantity: 1, unit_price: Number(price), discount: 0 };
        if (this.items.length === 1 && this.isBlank(this.items[0])) this.items.splice(0, 1, line);
        else this.items.push(line);
    },
    addFromCatalog(entry) { this.addLine(entry.name, entry.price); this.catalogSearch = ''; this.catalogOpen = false; },
    removeItem(index) { this.items.splice(index, 1); },
    money(value) { return `RM ${Number(value || 0).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`; },
});

window.catalogManager = (initialItems, basePath) => ({
    items: initialItems || [],
    editingId: null,
    search: '',
    form: { name: '', description: '', price: '' },
    get filtered() {
        const q = this.search.trim().toLowerCase();
        if (!q) return this.items;
        return this.items.filter(item => item.name.toLowerCase().includes(q) || (item.description || '').toLowerCase().includes(q));
    },
    get action() { return this.editingId ? `/${basePath}/${this.editingId}` : `/${basePath}`; },
    edit(item) { this.editingId = item.id; this.form = { name: item.name, description: item.description || '', price: item.price }; window.scrollTo({ top: 0, behavior: 'smooth' }); },
    cancel() { this.editingId = null; this.form = { name: '', description: '', price: '' }; },
    money(value) { return `RM ${Number(value || 0).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`; },
});

window.bankAccountManager = (initialItems) => ({
    items: initialItems || [],
    editingId: null,
    form: { bank_name: '', account_number: '', account_holder: '' },
    get action() { return this.editingId ? `/bank-accounts/${this.editingId}` : '/bank-accounts'; },
    edit(item) { this.editingId = item.id; this.form = { bank_name: item.bank_name, account_number: item.account_number, account_holder: item.account_holder || '' }; },
    cancel() { this.editingId = null; this.form = { bank_name: '', account_number: '', account_holder: '' }; },
});

window.invoiceList = (initialInvoices) => ({
    invoices: initialInvoices || [],
    search: '',
    get filtered() {
        const q = this.search.trim().toLowerCase();
        if (!q) return this.invoices;
        return this.invoices.filter(inv => `${inv.number} ${inv.customer_name}`.toLowerCase().includes(q));
    },
});

window.Alpine = Alpine;
Alpine.start();
