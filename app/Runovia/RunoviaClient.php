<?php

declare(strict_types=1);

namespace App\Runovia;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Support\Facades\Log;

/**
 * The one place this application talks to the Runovia API.
 *
 * ! EVERY request carries two headers, and both are mandatory outside /health:
 *
 *       Authorization:    Bearer <token>
 *       X-Runovia-Client: web
 *
 *   The client header is bound THREE WAYS by auth gate 7 — against this header,
 *   against the value sealed inside the token's ciphertext, and against the
 *   endpoint's allowed list. That is what stops a token lifted from the mobile app
 *   being replayed by a script: an attacker controls the header but cannot see or
 *   change what is inside the token.
 *
 * ! TWO KINDS OF TOKEN, and confusing them is the mistake this class exists to
 *   make impossible:
 *
 *       APP token   authenticates THIS APPLICATION. Bought with the client
 *                   credentials. The only credential /auth/login and
 *                   /auth/register accept, because at that point there is no user
 *                   token yet to check. Cached across requests — it is not
 *                   user-specific and buying a new one per page view would be one
 *                   wasted round trip on every single request.
 *       USER token  authenticates a person. Minted by /auth/login. Lives in the
 *                   server-side session and is passed in explicitly by the caller.
 *
 * ! The user's token NEVER leaves the server. It is not in a JavaScript-readable
 *   cookie, not in localStorage, and not in any rendered page. That is the whole
 *   reason this app calls the API server-side rather than from the browser.
 */
final class RunoviaClient
{
    /**
     * ! Long enough to be worth caching, short enough that a revoked client stops
     *   working promptly. The real bound is the API's own AUTH_APP_TOKEN_TTL; this
     *   only ever shortens it, never extends it — see rememberAppToken().
     */
    private const APP_TOKEN_CACHE_KEY = 'runovia.app_token';

    public function __construct(
        private readonly HttpFactory $http,
        private readonly CacheRepository $cache,
    ) {
    }

    // # ------------------------------------------------------------------
    // # Calls made as the application
    // # ------------------------------------------------------------------

    /**
     * A call authenticated by the APP token — /auth/login and /auth/register only.
     *
     * ! Retries ONCE on a token-rejected response, having first forgotten the
     *   cached token. A cached APP token can be invalidated out from under this app
     *   at any time: the credential row is deleted, the API's database is reset in
     *   development, or an operator revokes it. Without the retry every login would
     *   fail until the cache happened to expire, and the user-visible symptom
     *   ("Access denied" on correct credentials) points at entirely the wrong thing.
     *
     * @param array<string, mixed> $body
     * @param array<string, mixed> $query
     */
    public function asApp(string $method, string $path, array $body = [], array $query = []): ApiResponse
    {
        $response = $this->send($method, $path, $body, $query, $this->appToken());

        if ($response->code()->requiresReauthentication()) {
            $this->forgetAppToken();

            $response = $this->send($method, $path, $body, $query, $this->appToken());
        }

        return $response;
    }

    /**
     * A call authenticated as a signed-in person.
     *
     * ! No retry here, deliberately. A rejected USER token means the session is
     *   over, and only the person can fix that by signing in again — retrying with
     *   the same token would fail identically. The caller (see
     *   EnsureApiSession) turns this into a redirect to login.
     *
     * @param array<string, mixed> $body
     * @param array<string, mixed> $query
     */
    public function asUser(string $token, string $method, string $path, array $body = [], array $query = []): ApiResponse
    {
        return $this->send($method, $path, $body, $query, $token);
    }

    // # ------------------------------------------------------------------
    // # The APP token
    // # ------------------------------------------------------------------

    /**
     * The cached APP token, buying a new one when there is none.
     */
    public function appToken(): string
    {
        $cached = $this->cache->get(self::APP_TOKEN_CACHE_KEY);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        return $this->rememberAppToken();
    }

    public function forgetAppToken(): void
    {
        $this->cache->forget(self::APP_TOKEN_CACHE_KEY);
    }

    /**
     * Exchange the client credentials for a fresh APP token and cache it.
     *
     * ! The signature is sha512(client_id . secret) and is sent ALONGSIDE Basic
     *   auth, not instead of it. The API answers an unknown client id, a wrong
     *   secret and a wrong signature with the identical code and message, so the
     *   endpoint cannot be used to discover which half of a credential pair was
     *   right — which also means a failure here tells this app nothing more
     *   specific than "these credentials do not work".
     */
    private function rememberAppToken(): string
    {
        $clientId = (string) config('runovia.client_id');
        $secret   = (string) config('runovia.client_secret');

        if ($clientId === '' || $secret === '') {
            throw ApiException::notConfigured();
        }

        $response = $this->dispatch(
            method: 'POST',
            path: '/api/v1/auth/token/app',
            body: [],
            query: [],
            headers: [
                'Authorization'       => 'Basic ' . base64_encode($clientId . ':' . $secret),
                'X-Runovia-Signature' => hash('sha512', $clientId . $secret),
            ],
        );

        if ($response->failed()) {
            throw ApiException::fromResponse($response, 'the application token exchange');
        }

        $token = (string) $response->get('token', '');

        if ($token === '') {
            throw ApiException::fromResponse($response, 'the application token exchange (no token returned)');
        }

        $this->cache->put(self::APP_TOKEN_CACHE_KEY, $token, $this->appTokenTtl($response));

        return $token;
    }

    /**
     * How long to cache the APP token for.
     *
     * ! Derived from the API's own `expires_at`, minus a leeway, and never longer.
     *   Hard-coding a duration here would silently outlive a shortened
     *   AUTH_APP_TOKEN_TTL on the API and produce expired-token failures that look
     *   like an outage. Falls back to a conservative hour if the field is absent.
     */
    private function appTokenTtl(ApiResponse $response): int
    {
        $expiresAt = $response->get('expires_at');
        $leeway    = (int) config('runovia.token_leeway_seconds', 60);

        if (is_string($expiresAt) && $expiresAt !== '') {
            $timestamp = strtotime($expiresAt);

            if ($timestamp !== false) {
                return max(60, $timestamp - time() - $leeway);
            }
        }

        return 3600;
    }

    // # ------------------------------------------------------------------
    // # Transport
    // # ------------------------------------------------------------------

    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed> $query
     */
    private function send(string $method, string $path, array $body, array $query, string $token): ApiResponse
    {
        return $this->dispatch(
            method: $method,
            path: $path,
            body: $body,
            query: $query,
            headers: ['Authorization' => 'Bearer ' . $token],
        );
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, mixed> $query
     * @param array<string, string> $headers
     */
    private function dispatch(string $method, string $path, array $body, array $query, array $headers): ApiResponse
    {
        $method = strtoupper($method);
        $url    = config('runovia.url') . $path;

        $request = $this->http
            ->asJson()
            ->acceptJson()
            ->timeout((int) config('runovia.timeout', 15))
            ->withHeaders($headers + [
                // ! Gate 1 refuses the request outright without this (code 101).
                'X-Runovia-Client' => (string) config('runovia.client'),
            ]);

        try {
            // ? Body and query are passed separately rather than merged: the API
            // ? reads filters from the query string and payloads from the body, and
            // ? a create whose field happened to share a filter's name would
            // ? otherwise be silently reinterpreted as a filter.
            $httpResponse = $request->send($method, $url, array_filter([
                'query' => $query,
                'json'  => $body,
            ], static fn(array $part): bool => $part !== []));
        } catch (ConnectionException $e) {
            Log::warning('Runovia API unreachable', ['method' => $method, 'path' => $path, 'error' => $e->getMessage()]);

            throw ApiException::transport($e->getMessage(), $e);
        }

        return $this->decode($httpResponse, $method, $path);
    }

    /**
     * Turn an HTTP response into an ApiResponse.
     *
     * ! A non-JSON body is treated as a transport failure, not as a refusal. Every
     *   real answer from this API is the envelope — including its errors, which is
     *   the guarantee Modules\Response makes — so HTML or an empty body means
     *   something in front of the API answered instead of the API: a web server
     *   error page, a proxy, a wrong RUNOVIA_API_URL. Parsing that as a refusal
     *   would surface "Something went wrong" on a misconfiguration that has an
     *   exact cause.
     */
    private function decode(HttpResponse $httpResponse, string $method, string $path): ApiResponse
    {
        $decoded = $httpResponse->json();

        if (!is_array($decoded) || !array_key_exists('status_code', $decoded)) {
            Log::error('Runovia API returned a non-envelope response', [
                'method' => $method,
                'path'   => $path,
                'status' => $httpResponse->status(),
                'body'   => mb_substr($httpResponse->body(), 0, 500),
            ]);

            throw ApiException::transport(sprintf(
                'unexpected response from %s %s (HTTP %d) — check RUNOVIA_API_URL',
                $method,
                $path,
                $httpResponse->status()
            ));
        }

        /** @var array<string, mixed> $decoded */
        $response = ApiResponse::fromEnvelope($httpResponse->status(), $decoded);

        if ($response->isServerError()) {
            // # The API keeps the detail in its own error_logs and tells the caller
            // # nothing useful on purpose; log what we sent so the two can be paired.
            Log::error('Runovia API server error', [
                'method'        => $method,
                'path'          => $path,
                'http'          => $response->httpStatus,
                'response_code' => $response->responseCode,
            ]);
        }

        return $response;
    }
}
