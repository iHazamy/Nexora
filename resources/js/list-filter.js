/**
 * Submits a list's filter form when a control changes, with a debounce on typing.
 *
 * ? Server-side filtering, not client-side. The API paginates, so a client-side
 *   filter would only ever search the 25 rows currently on screen while appearing to
 *   search everything — which is the kind of bug a user reports as "search is
 *   broken" months later. Every filter here becomes a query string the API applies.
 */
export default () => ({
    /** @type {number|undefined} */
    debounceHandle: undefined,

    /**
     * ! 400ms. Short enough to feel immediate, long enough that typing "Sarah" is
     *   one request rather than five.
     */
    submitDebounced() {
        window.clearTimeout(this.debounceHandle);

        this.debounceHandle = window.setTimeout(() => this.submitNow(), 400);
    },

    submitNow() {
        window.clearTimeout(this.debounceHandle);

        // ! Resets to page 1. Filtering while on page 3 of the unfiltered list would
        // ! otherwise ask for page 3 of a result set that may only have one page, and
        // ! the user sees an empty table for a search that did match.
        const page = this.$el.querySelector('input[name="page"]');

        if (page) {
            page.value = '1';
        }

        this.$el.requestSubmit();
    },
});
