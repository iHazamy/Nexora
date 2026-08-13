<?php

declare(strict_types=1);

namespace App\Runovia\Resources;

use App\Runovia\ApiResponse;

/**
 * Register, sign in, sign out, and who the API says you are.
 *
 * ! register() and attemptLogin() are the only calls in this app made with the APP
 *   token instead of a user's own. They have to be: there is no user token yet at
 *   that point, and the API still requires the caller to prove it is a registered
 *   client. Every other method here runs as the signed-in user.
 */
final class AuthApi extends ResourceApi
{
    /**
     * ! Returns the ApiResponse rather than throwing, because a wrong password is
     *   an ordinary outcome of a login form, not an exception. The controller puts
     *   the API's message straight on the form.
     *
     * ! The API answers an unknown email and a wrong password with the IDENTICAL
     *   code and message, so this app cannot — and must not try to — tell the user
     *   which one it was. Doing so would turn the login form into a way to discover
     *   which addresses are registered.
     */
    public function attemptLogin(string $email, string $password): ApiResponse
    {
        return $this->client->asApp('POST', '/api/v1/auth/login', [
            'email'    => $email,
            'password' => $password,
        ]);
    }

    /**
     * ! Also returns the ApiResponse: "that email is already registered" is a form
     *   error, and the API answers it with a 409 that says so.
     *
     * ! Creates a user and NOTHING ELSE — no business. That is the API's flow, and
     *   the caller must send the new user on to create one. account_type is not
     *   writable through any endpoint, so no request body here can mint a platform
     *   admin.
     *
     * @param array<string, mixed> $attributes name, email, phone, password
     */
    public function register(array $attributes): ApiResponse
    {
        return $this->client->asApp('POST', '/api/v1/auth/register', $attributes);
    }

    /**
     * The authenticated user, their business (or null) and their extra grants.
     *
     * ? Works without a business, which is exactly why it is called right after
     *   registering — it is how this app decides whether to show the app shell or
     *   the "create your business" step.
     *
     * @return array<string, mixed>
     */
    public function me(): array
    {
        return $this->httpGet('/api/v1/auth/me')->record();
    }

    /**
     * Revoke the calling token only, leaving other devices signed in.
     *
     * ! Failure is swallowed on purpose — see the controller. If the token is
     *   already gone the user's intent is satisfied either way, and an error page on
     *   the way out of the building is the least useful moment for one.
     */
    public function logout(): ApiResponse
    {
        return $this->call('POST', '/api/v1/auth/logout');
    }

    /**
     * Revoke every token this user holds, on every device.
     */
    public function logoutEverywhere(): ApiResponse
    {
        return $this->call('POST', '/api/v1/auth/logout', ['all' => true]);
    }
}
