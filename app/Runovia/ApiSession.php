<?php

declare(strict_types=1);

namespace App\Runovia;

use Illuminate\Contracts\Session\Session;

/**
 * The signed-in user's API session: their token, and who the API says they are.
 *
 * ! THE TOKEN LIVES HERE AND ONLY HERE. Server-side session storage, never a
 *   JavaScript-readable cookie, never localStorage, never interpolated into a
 *   rendered page. The browser holds an encrypted session cookie and nothing else,
 *   so XSS in this app cannot walk away with an API credential that is valid for
 *   fourteen days.
 *
 * ! The API remains the authority on everything cached here. Role, business and
 *   grants are copied out of the login response so a sidebar can be rendered
 *   without a round trip — but they are re-read from /auth/me on demand, and every
 *   permission decision that MATTERS is made by the API's gate pipeline on the
 *   actual request. See canWrite() for why the copy is a hint and not a rule.
 */
final class ApiSession
{
    private const TOKEN       = 'runovia.token';
    private const EXPIRES_AT  = 'runovia.expires_at';
    private const USER        = 'runovia.user';
    private const BUSINESS    = 'runovia.business';
    private const ROLE        = 'runovia.role';
    private const PERMISSIONS = 'runovia.permissions';

    public function __construct(private readonly Session $session)
    {
    }

    // # ------------------------------------------------------------------
    // # Lifecycle
    // # ------------------------------------------------------------------

    /**
     * Store a fresh login.
     *
     * ! Regenerates the session id FIRST. Without it, an attacker who can set a
     *   victim's session cookie before they sign in still holds a valid id
     *   afterwards — session fixation. This is the one moment in the app where the
     *   privilege level of a session changes, so it is the moment the id must
     *   change.
     *
     * @param array<string, mixed> $payload The `data` block from /auth/login or
     *                                      /auth/register.
     */
    public function start(array $payload): void
    {
        $this->session->regenerate();

        $this->session->put(self::TOKEN, (string) ($payload['token'] ?? ''));
        $this->session->put(self::EXPIRES_AT, $payload['expires_at'] ?? null);

        $this->putIdentity($payload);
    }

    /**
     * Refresh the cached identity from a /auth/me response, leaving the token
     * alone.
     *
     * ? Called after editing company settings, which changes what the shell renders
     *   without minting a new token.
     *
     * @param array<string, mixed> $payload
     */
    public function refreshIdentity(array $payload): void
    {
        $this->putIdentity($payload);
    }

    /**
     * Swap in a token the API reissued because the user's membership changed.
     *
     * ! REQUIRED after creating a business, and /auth/me is NOT a substitute. A user
     *   token seals its business id, UserType and grants into the ciphertext at issue
     *   time, and /auth/me reads the business out of that token rather than out of
     *   business_users — so with the registration-time token it keeps answering
     *   `business: null` however many times it is asked. The API's
     *   POST /businesses hands back a replacement token for exactly this reason;
     *   this is where it is stored.
     *
     * ! Regenerates the session id, like start() does, because this IS a privilege
     *   change: the caller went from belonging to nothing to owning a business. Any
     *   moment a session's privilege level rises is a moment its id should change.
     *
     * ! Keeps the existing cached `user`. The business-create response carries the
     *   business, the role and the token but no user — it is the same person — and
     *   passing the response straight to putIdentity() would blank the name and email
     *   the shell renders.
     *
     * @param array<string, mixed> $payload The `data` block from POST /businesses.
     */
    public function replaceToken(array $payload): void
    {
        $user = $this->user();

        $this->session->regenerate();

        $this->session->put(self::TOKEN, (string) ($payload['token'] ?? ''));
        $this->session->put(self::EXPIRES_AT, $payload['expires_at'] ?? null);
        $this->session->put(self::USER, $user);
        $this->session->put(self::BUSINESS, is_array($payload['business'] ?? null) ? $payload['business'] : null);
        $this->session->put(self::ROLE, $payload['role'] ?? null);

        /*
         * ! Cleared rather than carried over. Grants are re-sampled by the API when it
         *   mints the new token, and the per-user extras from the previous membership
         *   have no meaning against a membership that did not exist a moment ago.
         *   Keeping them would let a stale grant widen the button hints.
         */
        $this->session->put(self::PERMISSIONS, []);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function putIdentity(array $payload): void
    {
        $this->session->put(self::USER, is_array($payload['user'] ?? null) ? $payload['user'] : []);
        $this->session->put(self::BUSINESS, is_array($payload['business'] ?? null) ? $payload['business'] : null);
        $this->session->put(self::ROLE, $payload['role'] ?? null);
        $this->session->put(
            self::PERMISSIONS,
            is_array($payload['permissions'] ?? null) ? array_values($payload['permissions']) : []
        );
    }

    /**
     * Forget everything about the signed-in user.
     *
     * ! Invalidates the whole session rather than removing the keys, so nothing
     *   from the previous user's visit — flash messages, old form input, the
     *   intended-url — survives into the next one. Also regenerates the CSRF token.
     */
    public function forget(): void
    {
        $this->session->invalidate();
        $this->session->regenerateToken();
    }

    // # ------------------------------------------------------------------
    // # Reading
    // # ------------------------------------------------------------------

    public function token(): ?string
    {
        $token = $this->session->get(self::TOKEN);

        return is_string($token) && $token !== '' ? $token : null;
    }

    public function check(): bool
    {
        return $this->token() !== null;
    }

    /**
     * Has the token passed the expiry the API told us about?
     *
     * ! An optimisation, not a security control — the API checks expiry itself at
     *   gate 6 and is the only opinion that counts. Checking here just means an
     *   expired session becomes a clean redirect to the login page instead of a
     *   failed API call rendered as an error.
     */
    public function isExpired(): bool
    {
        $expiresAt = $this->session->get(self::EXPIRES_AT);

        if (!is_string($expiresAt) || $expiresAt === '') {
            return false;
        }

        $timestamp = strtotime($expiresAt);

        if ($timestamp === false) {
            return false;
        }

        return $timestamp <= time() + (int) config('runovia.token_leeway_seconds', 60);
    }

    /**
     * @return array<string, mixed>
     */
    public function user(): array
    {
        $user = $this->session->get(self::USER);

        return is_array($user) ? $user : [];
    }

    public function userName(): string
    {
        return (string) ($this->user()['name'] ?? 'there');
    }

    public function userEmail(): string
    {
        return (string) ($this->user()['email'] ?? '');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function business(): ?array
    {
        $business = $this->session->get(self::BUSINESS);

        return is_array($business) ? $business : null;
    }

    public function businessName(): string
    {
        return (string) ($this->business()['name'] ?? 'Your business');
    }

    /**
     * ! A registered user has NO business until they create one, and most of the
     *   app is unreachable until they do. That is the API's design — register and
     *   create-business are separate steps — so this is a first-class state, not an
     *   error. See EnsureBusinessExists.
     */
    public function hasBusiness(): bool
    {
        return ($this->business()['id'] ?? null) !== null;
    }

    public function businessId(): ?int
    {
        $id = $this->business()['id'] ?? null;

        return $id === null ? null : (int) $id;
    }

    /**
     * SA | OW | MG | MB
     */
    public function role(): ?string
    {
        $role = $this->session->get(self::ROLE);

        return is_string($role) ? $role : null;
    }

    public function isOwner(): bool
    {
        return $this->role() === 'OW';
    }

    /**
     * ! A platform admin reaches NONE of the business modules — Module::forRole()
     *   gives SA and the business roles disjoint sets. So an SA signing in here has
     *   no customers, no invoices and no business, and sending them to the normal
     *   dashboard would show them a screen of permission errors.
     */
    public function isPlatformAdmin(): bool
    {
        return $this->role() === 'SA';
    }

    /**
     * The per-user ADDITIONAL grants, as `module.OP` strings.
     *
     * ! These are only the extras stored on business_users.permissions — NOT the
     *   effective set. The role's own defaults are applied by the API and are not
     *   transmitted, which is why canWrite() has to consult both.
     *
     * @return array<int, string>
     */
    public function extraGrants(): array
    {
        $permissions = $this->session->get(self::PERMISSIONS);

        return is_array($permissions) ? array_values(array_filter($permissions, 'is_string')) : [];
    }

    // # ------------------------------------------------------------------
    // # Permission hints
    // # ------------------------------------------------------------------

    /**
     * Would the API probably allow this operation? FOR HIDING BUTTONS ONLY.
     *
     * ! THIS IS NOT ENFORCEMENT AND MUST NEVER BE TREATED AS ANY. The API's gate 11
     *   is the only authority, and it re-checks every request regardless of what
     *   this returned. What this buys is a UI that does not offer a delete button
     *   that is going to fail — which is a courtesy, not a control.
     *
     * ! It mirrors Module::defaultGrants() on the API side, and a copy of a table
     *   can drift from its original. It is safe for it to drift in the direction of
     *   showing a button the API then refuses (the user sees a clear message). It is
     *   NOT safe to ever let it become the reason something is allowed, which is why
     *   no controller calls this and only views do.
     */
    public function canWrite(string $module, string $operation = 'C'): bool
    {
        $operation = strtoupper($operation);
        $role      = $this->role();

        if ($role === null) {
            return false;
        }

        // # An explicit per-user grant settles it — that is what they are for.
        if (in_array("$module." . $operation, $this->extraGrants(), true)) {
            return true;
        }

        return match ($role) {
            // # Owners hold everything except that the activity log is read-only.
            'OW' => $module !== 'logs' || $operation === 'R',

            // # Management runs the operational modules but deletes nothing by
            // # default, and cannot change the business record or its bank accounts.
            'MG' => match ($module) {
                'business', 'logs' => $operation === 'R',
                default            => in_array($operation, ['C', 'R', 'U'], true),
            },

            // # Members read.
            'MB' => $operation === 'R',

            // # SA reaches the platform module and the log, and nothing here.
            'SA' => $module === 'logs' && $operation === 'R',

            default => false,
        };
    }

    public function canDelete(string $module): bool
    {
        return $this->canWrite($module, 'D');
    }

    public function canUpdate(string $module): bool
    {
        return $this->canWrite($module, 'U');
    }

    public function canCreate(string $module): bool
    {
        return $this->canWrite($module, 'C');
    }
}
