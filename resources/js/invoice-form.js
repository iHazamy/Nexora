/**
 * The invoice line-item editor.
 *
 * ! THE TOTALS THIS COMPUTES ARE A PREVIEW AND NOTHING ELSE. The API derives every
 *   figure on an invoice in integer cents and its answer is the only one ever saved
 *   or shown as final. This file exists so a user typing a quantity sees roughly
 *   what the line will cost without a round trip — not so the browser can decide
 *   what to charge.
 *
 *   The screen says "estimate" next to these numbers on purpose. The previous app
 *   duplicated the same arithmetic in PHP AND JavaScript with neither labelled, and
 *   keeping two independent copies of a money calculation in agreement is a game you
 *   eventually lose quietly.
 *
 * ! Arithmetic here is in INTEGER CENTS, matching the API, rather than in floats on
 *   ringgit values. 3 x 33.33 must preview as 99.99 and not 99.99000000000001, and a
 *   preview that disagrees with the saved figure in the last decimal is worse than no
 *   preview at all.
 */

/** "1234.5" | 1234.5 -> 123450 */
const toCents = (value) => {
    const number = typeof value === 'number' ? value : Number.parseFloat(String(value ?? '').trim());

    return Number.isFinite(number) ? Math.round(number * 100) : 0;
};

/** 123450 -> "1,234.50" */
const fromCents = (cents) => {
    const negative = cents < 0;
    const absolute = Math.abs(cents);
    const whole = Math.trunc(absolute / 100).toString();
    const fraction = (absolute % 100).toString().padStart(2, '0');
    const grouped = whole.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

    return `${negative ? '-' : ''}${grouped}.${fraction}`;
};

export default (config = {}) => ({
    /** @type {Array<{key: string, description: string, quantity: string, unit_price: string, discount: string, product_id: string, package_id: string}>} */
    lines: [],

    catalog: config.catalog ?? { products: [], packages: [] },

    invoiceDiscount: config.invoiceDiscount ?? '0.00',
    invoiceTax: config.invoiceTax ?? '0.00',

    nextKey: 0,

    init() {
        const existing = Array.isArray(config.lines) ? config.lines : [];

        this.lines = existing.map((line) => this.makeLine(line));

        if (this.lines.length === 0) {
            this.addLine();
        }
    },

    makeLine(line = {}) {
        return {
            // ! A stable key per row, used for :key. Using the array index instead
            // ! makes Alpine reuse the wrong DOM node when a middle row is removed,
            // ! and the user watches their typing jump to a different line.
            key: `line-${this.nextKey++}`,
            description: line.description ?? '',
            quantity: line.quantity ?? '1',
            unit_price: line.unit_price ?? '0.00',
            discount: line.discount ?? '0.00',
            product_id: line.product_id ?? '',
            package_id: line.package_id ?? '',
        };
    },

    addLine() {
        this.lines.push(this.makeLine());
    },

    removeLine(index) {
        this.lines.splice(index, 1);

        // ! Never leave zero rows. The API refuses an invoice with no items, so an
        // ! empty editor can only ever produce a failed submit.
        if (this.lines.length === 0) {
            this.addLine();
        }
    },

    /**
     * Fill a line from the catalog.
     *
     * ! Sets product_id XOR package_id and clears the other. A line carrying both is
     *   a 400 from the API — it has no single price basis — and the select's value
     *   encodes which kind it is because the two id spaces overlap.
     */
    applyCatalog(index, value) {
        const line = this.lines[index];

        if (!line) {
            return;
        }

        if (!value) {
            line.product_id = '';
            line.package_id = '';

            return;
        }

        const [kind, id] = value.split(':');
        const source = kind === 'package' ? this.catalog.packages : this.catalog.products;
        const chosen = source.find((entry) => String(entry.id) === id);

        line.product_id = kind === 'product' ? id : '';
        line.package_id = kind === 'package' ? id : '';

        if (chosen) {
            // ? Copies name and price onto the line rather than referencing them. The
            // ? API does the same thing server-side at issue time, so that renaming or
            // ? repricing a product later does not rewrite an invoice already sent.
            line.description = chosen.name ?? line.description;
            line.unit_price = chosen.price ?? line.unit_price;
        }
    },

    catalogValue(line) {
        if (line.product_id) {
            return `product:${line.product_id}`;
        }

        if (line.package_id) {
            return `package:${line.package_id}`;
        }

        return '';
    },

    // # ---------------------------------------------------------------- Preview

    lineGrossCents(line) {
        return Math.round((toCents(line.quantity) * toCents(line.unit_price)) / 100);
    },

    lineTotalCents(line) {
        return Math.max(0, this.lineGrossCents(line) - toCents(line.discount));
    },

    lineTotal(line) {
        return fromCents(this.lineTotalCents(line));
    },

    /** True when a line's discount exceeds the line — the API will refuse this. */
    lineDiscountTooLarge(line) {
        return toCents(line.discount) > this.lineGrossCents(line);
    },

    get subtotalCents() {
        return this.lines.reduce((sum, line) => sum + this.lineTotalCents(line), 0);
    },

    get subtotal() {
        return fromCents(this.subtotalCents);
    },

    get totalCents() {
        return this.subtotalCents - toCents(this.invoiceDiscount) + toCents(this.invoiceTax);
    },

    get total() {
        return fromCents(Math.max(0, this.totalCents));
    },

    /** The API refuses a discount larger than the subtotal. */
    get invoiceDiscountTooLarge() {
        return toCents(this.invoiceDiscount) > this.subtotalCents;
    },

    /**
     * Anything the API is going to reject, surfaced before the user submits.
     *
     * ! A convenience, not validation. The API re-checks all of it and its refusal is
     *   what the user would see anyway; this just saves them a round trip and a lost
     *   scroll position.
     */
    get warnings() {
        const warnings = [];

        this.lines.forEach((line, index) => {
            if (this.lineDiscountTooLarge(line)) {
                warnings.push(`Line ${index + 1}: the discount is more than the line is worth.`);
            }

            if (!line.description && !line.product_id && !line.package_id) {
                warnings.push(`Line ${index + 1}: choose an item or type a description.`);
            }
        });

        if (this.invoiceDiscountTooLarge) {
            warnings.push('The invoice discount is more than the subtotal.');
        }

        return warnings;
    },

    money(value) {
        return fromCents(toCents(value));
    },
});
