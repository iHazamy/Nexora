<?php

declare(strict_types=1);

namespace App\Http\Requests\Packages;

/**
 * PUT /packages/{package}
 *
 * ! THE MOST DANGEROUS THING IN THIS RESOURCE, and the reason this class is separate from
 *   CreatePackageRequest:
 *
 *       sending `items`    REPLACES the package's contents wholesale
 *       omitting `items`   leaves them exactly as they were
 *       sending `items:[]` empties the package
 *
 *   So the key must appear in the payload ONLY when the item editor was genuinely part of
 *   the submission. If it always appeared, a request that edits nothing but the name
 *   would arrive carrying whatever the form happened to serialise — and on a form that
 *   does not render the editor, that is an empty array. The package silently loses every
 *   line, the API reports success, and nobody finds out until someone quotes from it.
 *
 * ! Presence is decided by the hidden `items_submitted` marker, NOT by whether any
 *   `items[]` inputs arrived — see PackageRequest::managesItems(). Removing every row
 *   submits no inputs at all, and that must still mean "empty it", because that is what
 *   the user did.
 */
class UpdatePackageRequest extends PackageRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        if ($this->isDeactivation()) {
            // # Nothing else is submitted, so nothing else can be validated. Requiring
            // # `name` here would refuse the very remedy the API's 409 recommends.
            return ['deactivate' => ['accepted']];
        }

        return parent::rules();
    }

    /**
     * Is this the one-click Deactivate button rather than the edit form?
     *
     * ! The route table has no packages.deactivate endpoint, so that button posts here
     *   with nothing but a marker. It matters that the marker-only payload carries no
     *   `items` key at all: leaving the contents alone is exactly right for an action
     *   that is only meant to change one flag.
     */
    public function isDeactivation(): bool
    {
        return $this->isMethod('PUT') && $this->boolean('deactivate');
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        /*
         * ! THE FLAG AND NOTHING ELSE on the deactivate path. fields() cannot be used here:
         *   rules() validates only `deactivate` on this path, so validated('name'),
         *   validated('price') and validated('description') all resolve to NULL — and the
         *   API reads a key that is PRESENT AND NULL as "blank this column". `name` and
         *   `price` are NOT NULL, so that is a constraint violation surfacing as a 500, and
         *   `description` would be silently wiped on every deactivation.
         *
         * ! No `items` key either, which is correct twice over: the marker is absent on this
         *   submission, and an action meant to flip one flag must not touch the contents.
         */
        if ($this->isDeactivation()) {
            return ['active' => false];
        }

        $payload = $this->fields();

        if ($this->managesItems()) {
            $payload['items'] = $this->submittedItems();
        }

        return $payload;
    }
}
