<?php

declare(strict_types=1);

namespace App\Runovia;

/**
 * One response from the Runovia API, in the envelope it guarantees.
 *
 *   {
 *     "status":        "Success" | "Failed" | "Error",
 *     "status_code":   1 | 0 | -1,
 *     "response_code": 200,
 *     "message":       "",
 *     "data":          [] | {} | [...],
 *     "pagination":    { ... }        // list endpoints only
 *   }
 *
 * ! SUCCESS IS `status_code === 1`, NOT the HTTP status and NOT a range of
 *   response codes. The API returns HTTP 200 for an empty list with
 *   response_code 204, HTTP 403 for both "permission denied" (401) and "feature
 *   not enabled" (614), and HTTP 401 for four different session states. Reading
 *   the outcome off the HTTP status conflates all of those; `status_code` is the
 *   one field that answers "did this work" on its own.
 *
 * ! `response_code` says WHY, and the distinctions matter to this app: 604 means
 *   re-login silently, 614 means tell the user their plan does not include this,
 *   400 means put the message on the form. See ResponseCode.
 */
final readonly class ApiResponse
{
    /**
     * @param array<string, mixed>|array<int, mixed> $data
     * @param array<string, mixed>                   $pagination
     * @param array<string, mixed>                   $envelope The whole decoded body.
     */
    public function __construct(
        public int $httpStatus,
        public int $statusCode,
        public int $responseCode,
        public string $message,
        public array $data,
        public array $pagination = [],
        public array $envelope = [],
    ) {
    }

    /**
     * @param array<string, mixed> $envelope
     */
    public static function fromEnvelope(int $httpStatus, array $envelope): self
    {
        /** @var array<string, mixed>|array<int, mixed> $data */
        $data = is_array($envelope['data'] ?? null) ? $envelope['data'] : [];

        /** @var array<string, mixed> $pagination */
        $pagination = is_array($envelope['pagination'] ?? null) ? $envelope['pagination'] : [];

        return new self(
            httpStatus: $httpStatus,
            statusCode: (int) ($envelope['status_code'] ?? -1),
            responseCode: (int) ($envelope['response_code'] ?? 0),
            message: (string) ($envelope['message'] ?? ''),
            data: $data,
            pagination: $pagination,
            envelope: $envelope,
        );
    }

    public function successful(): bool
    {
        return $this->statusCode === 1;
    }

    public function failed(): bool
    {
        return !$this->successful();
    }

    /**
     * `Failed` (0) means the API knew exactly why and said so — validation, not
     * found, permission denied. Nothing is broken and retrying will not help.
     */
    public function isRefusal(): bool
    {
        return $this->statusCode === 0;
    }

    /**
     * `Error` (-1) means something unanticipated. Retrying may help.
     */
    public function isServerError(): bool
    {
        return $this->statusCode === -1;
    }

    public function code(): ResponseCode
    {
        return ResponseCode::resolve($this->responseCode);
    }

    /**
     * A dot-path read into `data`.
     *
     * ? Not Arr::get on the whole envelope: callers want the payload, and having to
     *   remember to prefix every path with 'data.' is the kind of detail that is
     *   right in 19 call sites and wrong in the 20th.
     */
    public function get(string $path, mixed $default = null): mixed
    {
        return data_get($this->data, $path, $default);
    }

    /**
     * `data` as a keyed record — a single resource.
     *
     * @return array<string, mixed>
     */
    public function record(): array
    {
        return array_is_list($this->data) ? [] : $this->data;
    }

    /**
     * `data` as a list of records — a collection endpoint.
     *
     * ! An empty list is a legitimate success (response_code 204), so this returns
     *   [] rather than treating it as a missing payload.
     *
     * @return array<int, array<string, mixed>>
     */
    public function records(): array
    {
        if (!array_is_list($this->data)) {
            return [];
        }

        /** @var array<int, array<string, mixed>> $rows */
        $rows = array_values(array_filter($this->data, 'is_array'));

        return $rows;
    }

    public function total(): int
    {
        return (int) ($this->pagination['total'] ?? count($this->records()));
    }

    public function page(): int
    {
        return (int) ($this->pagination['page'] ?? 1);
    }

    public function perPage(): int
    {
        return (int) ($this->pagination['per_page'] ?? max(1, count($this->records())));
    }

    public function totalPages(): int
    {
        return (int) ($this->pagination['total_pages'] ?? 1);
    }

    /**
     * A message safe to show a user, with a fallback for the plain-200 empty case.
     */
    public function userMessage(string $fallback = 'Something went wrong. Please try again.'): string
    {
        return $this->message !== '' ? $this->message : $fallback;
    }
}
