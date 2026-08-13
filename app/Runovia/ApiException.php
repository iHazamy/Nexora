<?php

declare(strict_types=1);

namespace App\Runovia;

use RuntimeException;
use Throwable;

/**
 * A Runovia API call that could not be completed.
 *
 * ! Three genuinely different failures, kept apart because the right handling
 *   differs:
 *
 *     notConfigured()  the credentials are missing from .env. A setup mistake, and
 *                      the only one where the fix is a developer's, so it says so
 *                      plainly instead of pretending the API is down.
 *     transport()      the API could not be reached at all — refused, DNS, timeout.
 *                      Nothing about the request was wrong; retrying may work.
 *     fromResponse()   the API answered and refused. The envelope is attached, so
 *                      the handler can branch on response_code.
 *
 * ? Refusals a CALLER expects to handle — 404 on a record, 400 on a form — are
 *   returned as an ApiResponse instead of thrown. Throwing there would make every
 *   controller a try/catch. This exception is for the cases a controller has no
 *   useful answer to.
 */
final class ApiException extends RuntimeException
{
    private function __construct(
        string $message,
        public readonly ?ApiResponse $response = null,
        public readonly bool $isTransport = false,
        public readonly bool $isConfiguration = false,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $response?->responseCode ?? 0, $previous);
    }

    public static function fromResponse(ApiResponse $response, string $context = ''): self
    {
        $detail = $response->message !== '' ? $response->message : $response->code()->userMessage();

        return new self(
            message: trim(sprintf(
                'Runovia API refused %s (HTTP %d, response_code %d): %s',
                $context !== '' ? $context : 'the request',
                $response->httpStatus,
                $response->responseCode,
                $detail
            )),
            response: $response,
        );
    }

    public static function transport(string $detail, ?Throwable $previous = null): self
    {
        return new self(
            message: 'Could not reach the Runovia API: ' . $detail,
            isTransport: true,
            previous: $previous,
        );
    }

    public static function notConfigured(): self
    {
        return new self(
            message: 'Runovia API credentials are not configured. Set RUNOVIA_API_CLIENT_ID and '
                . 'RUNOVIA_API_CLIENT_SECRET in .env — mint them on the API with '
                . '`php database/create-app-client.php --name="Runovia Web" --app=RV --client=web`.',
            isConfiguration: true,
        );
    }

    public function code(): ResponseCode
    {
        return $this->response?->code() ?? ResponseCode::UNKNOWN;
    }

    /**
     * The message to show a user — never this exception's own, which names
     * internals.
     */
    public function userMessage(): string
    {
        if ($this->isConfiguration) {
            return 'Runovia is not configured correctly. Please contact your administrator.';
        }

        if ($this->isTransport) {
            return 'Runovia is unreachable right now. Please try again shortly.';
        }

        $response = $this->response;

        if ($response === null) {
            return 'Something went wrong. Please try again.';
        }

        // # Prefer the API's own wording: it is written for users and is already
        // # careful not to disclose which gate refused.
        return $response->message !== '' ? $response->message : $response->code()->userMessage();
    }
}
