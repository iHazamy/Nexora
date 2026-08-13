<?php

declare(strict_types=1);

namespace App\Runovia;

/**
 * The API's `response_code`, and what this app should DO about each one.
 *
 * ! This enum exists because the HTTP status is not enough to decide. The API
 *   answers HTTP 401 for four different session states and HTTP 403 for six
 *   different refusals, and the right response differs sharply between them:
 *
 *     604 token expired          -> send to login quietly, it is not an error
 *     602 account deactivated    -> send to login, and say why
 *     611 business suspended     -> do NOT send to login; logging in again changes
 *                                   nothing. Show a contact-support page.
 *     614 feature not enabled    -> not a permission problem. Say "your plan does
 *                                   not include this", not "access denied".
 *     401 permission denied      -> "ask your owner for access"
 *
 *   Collapsing those into "401 means log in again" produces a login loop for a
 *   suspended business, and collapsing them into "403 means access denied" tells a
 *   paying customer they are not allowed to use a feature they simply have not
 *   bought.
 *
 * ! UNKNOWN is a real case, not a bug. The API may add codes; this app must not
 *   fail closed on one it has not been taught.
 */
enum ResponseCode: int
{
    // # 1xx — authentication and authorisation. All answer "Access denied." to the
    // # user; which gate refused is deliberately not disclosed.
    case TOKEN_INVALID       = 100;
    case CLIENT_HEADER_MISSING = 101;
    case CLIENT_UNKNOWN      = 102;
    case ROLE_NOT_SERVED     = 103;
    case APP_NOT_SERVED      = 104;
    case CLIENT_NOT_SERVED   = 105;
    case ACCOUNT_SUSPENDED   = 107;
    case CLIENT_MISMATCH     = 110;

    // # 2xx — success
    case OK         = 200;
    case CREATED    = 201;
    case ACCEPTED   = 202;
    case NO_CONTENT = 204;

    // # 4xx — client error
    case VALIDATION_FAILED   = 400;
    case PERMISSION_DENIED   = 401;
    case NOT_FOUND           = 404;
    case METHOD_NOT_ALLOWED  = 405;
    case CONFLICT            = 409;
    case PAYLOAD_TOO_LARGE   = 413;
    case UNSUPPORTED_MEDIA   = 415;
    case UNPROCESSABLE       = 422;
    case RATE_LIMITED        = 429;

    // # 5xx — server error
    case SERVER_ERROR        = 500;
    case NOT_IMPLEMENTED     = 501;
    case BAD_GATEWAY         = 502;
    case SERVICE_UNAVAILABLE = 503;
    case UNKNOWN_CODE        = 506;
    case CONFIG_MISSING      = 507;

    // # 6xx — session and tenant state
    case ACCOUNT_DEACTIVATED  = 602;
    case TOKEN_EXPIRED        = 604;
    case TOKEN_EXPIRING       = 605;
    case BUSINESS_UNAVAILABLE = 610;
    case BUSINESS_SUSPENDED   = 611;
    case BUSINESS_CLOSED      = 612;
    case MAINTENANCE          = 613;
    case FEATURE_DISABLED     = 614;

    case UNKNOWN = 0;

    /**
     * ! NOT named from(). A native PHP enum already declares from() and tryFrom()
     *   implicitly, and redeclaring either is a fatal "Cannot redeclare" at class
     *   load — not a warning, and not something the IDE flags before you run it.
     *
     * ? Unlike the built-in from(), this never throws. An unrecognised code is an
     *   expected condition: the API may add codes, and this app must degrade to a
     *   generic message rather than 500 on one it has not been taught.
     */
    public static function resolve(int $value): self
    {
        return self::tryFrom($value) ?? self::UNKNOWN;
    }

    /**
     * Is the user's session finished, such that logging in again would fix it?
     *
     * ! Deliberately EXCLUDES the 610-613 business states. A suspended business is
     *   not a session problem: the credentials are fine, and bouncing the user to
     *   the login screen would let them authenticate successfully and then be
     *   refused again on the next request — a loop with no exit and no explanation.
     */
    public function requiresReauthentication(): bool
    {
        return match ($this) {
            self::TOKEN_INVALID,
            self::TOKEN_EXPIRED,
            self::ACCOUNT_DEACTIVATED,
            self::ACCOUNT_SUSPENDED => true,
            default                 => false,
        };
    }

    /**
     * Is the tenant itself unavailable, whoever is asking?
     */
    public function isTenantUnavailable(): bool
    {
        return match ($this) {
            self::BUSINESS_UNAVAILABLE,
            self::BUSINESS_SUSPENDED,
            self::BUSINESS_CLOSED,
            self::MAINTENANCE => true,
            default           => false,
        };
    }

    /**
     * Did the request fail because of what the CALLER SENT, such that the message
     * belongs on a form field rather than on an error page?
     */
    public function isInputProblem(): bool
    {
        return match ($this) {
            self::VALIDATION_FAILED,
            self::CONFLICT,
            self::UNPROCESSABLE => true,
            default             => false,
        };
    }

    /**
     * A message this app can show when the API's own is empty or unhelpful.
     *
     * ! The 1xx codes deliberately all read the same. The API refuses to say which
     *   gate stopped you, because telling a caller their AppCode was fine but their
     *   role was not hands them an oracle for probing. This app must not undo that
     *   by being more specific than the API was.
     */
    public function userMessage(): string
    {
        return match ($this) {
            self::TOKEN_EXPIRED       => 'Your session has expired. Please sign in again.',
            self::ACCOUNT_DEACTIVATED => 'This account has been deactivated. Please contact support.',
            self::ACCOUNT_SUSPENDED   => 'This account is suspended. Please contact support.',

            self::BUSINESS_SUSPENDED   => 'Your business account is suspended. Please contact support.',
            self::BUSINESS_CLOSED      => 'This business account has been closed.',
            self::MAINTENANCE          => 'Runovia is undergoing maintenance. Please try again shortly.',
            self::BUSINESS_UNAVAILABLE => 'Your business account is unavailable. Please contact support.',

            self::FEATURE_DISABLED   => 'Your plan does not include this feature yet.',
            self::PERMISSION_DENIED  => 'You do not have permission to do that. Ask the account owner for access.',
            self::NOT_FOUND          => 'We could not find that record.',
            self::CONFLICT           => 'That change conflicts with the current state of the record.',
            self::RATE_LIMITED       => 'Too many requests. Please wait a moment and try again.',
            self::PAYLOAD_TOO_LARGE  => 'That upload is too large.',

            self::SERVICE_UNAVAILABLE,
            self::BAD_GATEWAY,
            self::CONFIG_MISSING,
            self::SERVER_ERROR       => 'Runovia is having trouble right now. Please try again shortly.',

            self::TOKEN_INVALID,
            self::CLIENT_HEADER_MISSING,
            self::CLIENT_UNKNOWN,
            self::ROLE_NOT_SERVED,
            self::APP_NOT_SERVED,
            self::CLIENT_NOT_SERVED,
            self::CLIENT_MISMATCH    => 'Access denied.',

            default                  => 'Something went wrong. Please try again.',
        };
    }
}
