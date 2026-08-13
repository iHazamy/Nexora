<?php

declare(strict_types=1);

namespace App\Http\Requests\Packages;

/**
 * POST /packages
 */
class CreatePackageRequest extends PackageRequest
{
    /**
     * ! `items` is ALWAYS sent on create, even when it is empty. There is nothing to
     *   leave alone on a record that does not exist yet, so the ambiguity that makes
     *   UpdatePackageRequest careful simply is not present here: an empty list creates an
     *   empty package, which is a legitimate thing to save and fill in later.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->fields() + ['items' => $this->submittedItems()];
    }
}
