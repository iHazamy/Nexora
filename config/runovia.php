<?php

declare(strict_types=1);

/**
 * Runovia API connection.
 *
 * ! The credentials here authenticate THE APPLICATION, not a person. They buy an
 *   APP token, which is the only credential the API accepts on /auth/login and
 *   /auth/register — the two endpoints that have no user token yet to check. A
 *   user's own token comes from logging in and lives in the server-side session.
 */
return [

    'url' => rtrim((string) env('RUNOVIA_API_URL', 'http://localhost:8000'), '/'),

    /*
     * ! Sent as X-Runovia-Client on every request, and bound THREE WAYS by auth
     *   gate 7: against this header, against the value sealed inside the token at
     *   issue time, and against the endpoint's own allowed list. All three must
     *   agree, so this must match the --client the credentials were registered
     *   for. A mismatch is 403 / response_code 110, not a warning.
     */
    'client' => (string) env('RUNOVIA_API_CLIENT', 'web'),

    'client_id'     => (string) env('RUNOVIA_API_CLIENT_ID', ''),
    'client_secret' => (string) env('RUNOVIA_API_CLIENT_SECRET', ''),

    /*
     * ! Seconds. Deliberately finite and fairly short: every page in this app is a
     *   synchronous call to the API, so an unbounded timeout means a stalled API
     *   holds a PHP worker until the web server gives up on it.
     */
    'timeout' => (int) env('RUNOVIA_API_TIMEOUT', 15),

    /*
     * How long before a user token expires to treat it as stale.
     *
     * ? The API warns with response_code 605 ("expiring soon") and refuses with 604
     *   ("expired"). Neither is an error worth showing a user mid-task, so the
     *   session is cleared and they are sent to log in again with an explanation.
     */
    'token_leeway_seconds' => 60,

];
