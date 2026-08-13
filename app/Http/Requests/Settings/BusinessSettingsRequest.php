<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * The company details printed on every invoice.
 *
 * ! `status` AND THE feat_* FLAGS ARE ABSENT, and no rule here will ever accept them.
 *   The API keeps both out of its own request class and out of the model's fillable
 *   list — two independent layers — because status is auth gate 10 and the feature
 *   flags are permission layer 3. A tenant must not be able to lift its own
 *   suspension or switch on a feature it has not paid for by adding a field to a
 *   form. The API does not even return the feature flags, so there is nothing here to
 *   render read-only either.
 *
 * ! Only `name` is required, matching the API. It goes on every document; the rest are
 *   details a business can fill in later, and refusing to save a corrected phone
 *   number because the registration number is blank would be absurd.
 *
 * ? Limits mirror the API's (name 200, address 500, registration_number 100,
 *   invoice_terms 4000) so a value it would refuse is caught before the round trip.
 *   Where the two could drift the API wins — it re-validates everything and its
 *   message is what the user would see anyway.
 */
class BusinessSettingsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name'                => ['required', 'string', 'min:2', 'max:200'],
            'email'               => ['nullable', 'string', 'email:filter', 'max:255'],
            'phone'               => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9][0-9\s\-()]{5,29}$/'],
            'address'             => ['nullable', 'string', 'max:500'],
            'registration_number' => ['nullable', 'string', 'max:100'],

            /*
             * ! No max on the number of LINES, and newlines are legitimate. This is a
             *   list of payment terms that prints as a block on the invoice, so the
             *   line breaks the owner typed are content, not formatting to be
             *   stripped.
             */
            'invoice_terms'       => ['nullable', 'string', 'max:4000'],

            /*
             * ! The UPLOAD is validated as an image; `logo_path` is never accepted
             *   from the browser. Letting a client post the path directly would hand
             *   it the choice of what the invoice renderer fetches, which is the whole
             *   reason the API refuses `../`, a leading slash, a drive letter and any
             *   scheme on that column. Here the file is stored server-side and only
             *   the path THIS app just created is sent on — see logoPayload().
             *
             * ! 2MB. A venue's logo is a few tens of kilobytes; a 20MB photograph of a
             *   signboard is a mistake, and it would be printed on every invoice.
             */
            'logo'                => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'remove_logo'         => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter your business name — it prints on every invoice.',
            'phone.regex'   => 'Enter a valid phone number, for example 03-12345678.',
            'logo.image'    => 'The logo must be an image file (JPG, PNG, WebP or SVG).',
            'logo.max'      => 'The logo must be 2MB or smaller.',
        ];
    }

    /**
     * The payload for the API.
     *
     * ! Sends every text field, including the empty ones, so clearing a field actually
     *   clears it. The API's PUT leaves an ABSENT key alone and treats an explicit
     *   null as "blank this column", so filtering empties out here would make it
     *   impossible to ever remove a registration number once entered.
     *
     * ! `logo_path` is the exception and is sent only when it should change — see
     *   logoPayload().
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'name'                => $this->validated('name'),
            'email'               => $this->validated('email'),
            'phone'               => $this->validated('phone'),
            'address'             => $this->validated('address'),
            'registration_number' => $this->validated('registration_number'),
            'invoice_terms'       => $this->validated('invoice_terms'),
        ] + $this->logoPayload();
    }

    /**
     * The `logo_path` key, present only when the logo is changing.
     *
     * ! STORES THE UPLOAD — this method has a side effect, and is called once, from
     *   payload(). The file is written to the `public` disk and only the RELATIVE PATH
     *   the disk hands back ("logos/abc123.png") is sent to the API. The API never
     *   sees the file and this app never sends it a URL: `logo_path` is read by a
     *   renderer that will fetch whatever it is handed, which is why the value must be
     *   one this app just produced rather than one a browser chose.
     *
     * ! An absent key leaves the existing logo alone, which is what makes it safe to
     *   save the rest of the form without re-uploading. An explicit null is how the
     *   logo is REMOVED, because that is what the API's partial update treats as
     *   "blank this column".
     *
     * ? The stored file is not deleted when the path changes. Deleting it would break
     *   every invoice already rendered against it, and this app cannot know which
     *   those are — the API holds the invoices, not the disk.
     *
     * @return array<string, mixed>
     */
    private function logoPayload(): array
    {
        if ($this->boolean('remove_logo')) {
            return ['logo_path' => null];
        }

        $file = $this->file('logo');

        if (!$file instanceof UploadedFile) {
            return [];
        }

        // # store() names the file itself, from a hash. A user-supplied filename is
        // # attacker-controlled text on a path, and this column is read by a fetcher.
        return ['logo_path' => $file->store('logos', 'public')];
    }
}
