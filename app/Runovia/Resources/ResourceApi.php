<?php

declare(strict_types=1);

namespace App\Runovia\Resources;

use App\Runovia\ApiException;
use App\Runovia\ApiResponse;
use App\Runovia\ApiSession;
use App\Runovia\RunoviaClient;

/**
 * Base for the per-resource API clients.
 *
 * ! THE FAILURE CONVENTION, which is what makes the controllers in this app three
 *   lines each: every method here THROWS ApiException on a refusal rather than
 *   returning a status for the caller to check. One handler
 *   (bootstrap/app.php -> ApiException) then decides what a given response_code
 *   means for the user:
 *
 *       400 / 409  -> back to the form with the API's message on it
 *       404        -> a not-found page
 *       604 / 602  -> the login page, session cleared
 *       611 / 613  -> a "contact support" / "maintenance" page, NOT the login page
 *       614        -> "your plan does not include this"
 *
 *   The alternative — returning ApiResponse everywhere — puts that same branch in
 *   every controller action, which means it is written correctly in most of them.
 *
 * ! Methods whose failure is genuinely part of the flow are the exception, and say
 *   so: AuthApi::attemptLogin() returns an ApiResponse, because "wrong password" is
 *   not an exceptional condition on a login form.
 */
abstract class ResourceApi
{
    public function __construct(
        protected readonly RunoviaClient $client,
        protected readonly ApiSession $session,
    ) {
    }

    /*
     * ! The transport helpers are named httpGet/httpPost/httpPut/httpDelete, not
     *   get/post/put/delete. A subclass wants to expose a natural `delete(int $id)`
     *   for its resource, and in PHP that would be an incompatible OVERRIDE of an
     *   inherited `delete(string $path, array $body)` — a fatal error at class load,
     *   not a warning. Prefixing the transport layer keeps the domain vocabulary
     *   free for the subclasses that actually model a domain.
     */

    /**
     * @param array<string, mixed> $query
     */
    protected function httpGet(string $path, array $query = []): ApiResponse
    {
        return $this->callOrFail('GET', $path, [], $query);
    }

    /**
     * @param array<string, mixed> $body
     */
    protected function httpPost(string $path, array $body = []): ApiResponse
    {
        return $this->callOrFail('POST', $path, $body);
    }

    /**
     * @param array<string, mixed> $body
     */
    protected function httpPut(string $path, array $body = []): ApiResponse
    {
        return $this->callOrFail('PUT', $path, $body);
    }

    /**
     * @param array<string, mixed> $body
     */
    protected function httpDelete(string $path, array $body = []): ApiResponse
    {
        return $this->callOrFail('DELETE', $path, $body);
    }

    /**
     * Call as the signed-in user, and throw unless the API said Success.
     *
     * @param array<string, mixed> $body
     * @param array<string, mixed> $query
     */
    protected function callOrFail(string $method, string $path, array $body = [], array $query = []): ApiResponse
    {
        $response = $this->call($method, $path, $body, $query);

        if ($response->failed()) {
            throw ApiException::fromResponse($response, "$method $path");
        }

        return $response;
    }

    /**
     * Call as the signed-in user and hand back whatever the API said.
     *
     * ! For the handful of places a refusal is an expected branch. Everything else
     *   should use callOrFail() so the failure reaches the one handler that knows
     *   what each response_code means.
     *
     * @param array<string, mixed> $body
     * @param array<string, mixed> $query
     */
    protected function call(string $method, string $path, array $body = [], array $query = []): ApiResponse
    {
        return $this->client->asUser($this->requireToken(), $method, $path, $body, $query);
    }

    /**
     * ! A LogicException, not a redirect. Reaching a resource method with no token
     *   means a route was registered without the api.session middleware — a wiring
     *   bug in this app, not something a user did. Turning it into a polite login
     *   redirect would hide the missing middleware, and the route would stay
     *   unprotected for every future change.
     */
    protected function requireToken(): string
    {
        $token = $this->session->token();

        if ($token === null) {
            throw new \LogicException(
                static::class . ' was called without an API session. '
                . 'The route is missing the `api.session` middleware.'
            );
        }

        return $token;
    }

    /**
     * The list parameters every collection endpoint accepts, filtered to what was
     * actually asked for.
     *
     * ! Empty values are dropped rather than sent as ''. The API treats a present
     *   filter as a filter, so `?status=` would ask for invoices whose status is
     *   the empty string — a query that matches nothing — instead of asking for all
     *   of them.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    protected function listQuery(array $filters): array
    {
        return array_filter(
            $filters,
            static fn(mixed $value): bool => $value !== null && $value !== '' && $value !== [],
        );
    }
}
