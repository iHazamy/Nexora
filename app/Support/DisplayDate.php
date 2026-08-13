<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Formats the two kinds of temporal value the API sends. They are not the same kind
 * of thing and must not be handled the same way.
 *
 * ! A DATE-ONLY FIELD HAS NO TIME AND NO TIMEZONE. `invoice_date`, `due_date`,
 *   `event_date` and `payment_date` arrive as 'YYYY-MM-DD' and must be rendered as
 *   that same calendar day, always, everywhere. Converting one between zones is
 *   always a bug — and a specific one that already bit this project once: the API
 *   used to parse these in Asia/Kuala_Lumpur and print them with gmdate(), so every
 *   date came back a day early and an invoice dated 12 December was served as 11
 *   December. Runovia-API's tests/Feature/DateRoundTripTest.php guards that end;
 *   date() below is the other end.
 *
 * ! A TIMESTAMP IS A REAL INSTANT. `created_at` and `updated_at` arrive as ISO-8601
 *   UTC and SHOULD be converted to the viewer's timezone — that is what makes "2
 *   hours ago" mean anything. So instant() converts and date() does not, and the
 *   split between them is the whole point of this class.
 */
final class DisplayDate
{
    /**
     * A date-only value, for reading: '2026-12-12' -> '12 Dec 2026'.
     *
     * ! Parsed with a leading '!' so every unspecified field resets to zero rather
     *   than defaulting to NOW, and formatted straight back out. No DateTimeZone is
     *   involved at any point, which is exactly the property that makes this correct.
     */
    public static function date(?string $value, string $format = 'j M Y'): string
    {
        $parsed = self::parseDateOnly($value);

        return $parsed === null ? '—' : $parsed->format($format);
    }

    /**
     * A date-only value for an <input type="date">, which requires 'Y-m-d'.
     */
    public static function input(?string $value): string
    {
        $parsed = self::parseDateOnly($value);

        return $parsed === null ? '' : $parsed->format('Y-m-d');
    }

    /**
     * '2026-12-12' -> 'Sat, 12 Dec 2026', for a document header.
     */
    public static function long(?string $value): string
    {
        return self::date($value, 'D, j M Y');
    }

    /**
     * An ISO-8601 UTC instant, converted to the app's timezone for display.
     */
    public static function instant(?string $value, string $format = 'j M Y, g:ia'): string
    {
        if ($value === null || trim($value) === '') {
            return '—';
        }

        try {
            $parsed = new DateTimeImmutable($value);
        } catch (\Exception) {
            return '—';
        }

        return $parsed
            ->setTimezone(new DateTimeZone((string) config('app.timezone', 'UTC')))
            ->format($format);
    }

    /**
     * Days until a date-only value; negative when it has passed.
     *
     * ! Compared at midnight in the APP's timezone, not UTC. "Is this invoice
     *   overdue" is a question about the user's calendar day: at 08:00 in Kuala
     *   Lumpur it is still the previous day in UTC, and comparing against UTC would
     *   call a same-day invoice overdue for the first eight hours of every morning.
     */
    public static function daysUntil(?string $value): ?int
    {
        $parsed = self::parseDateOnly($value);

        if ($parsed === null) {
            return null;
        }

        $zone  = new DateTimeZone((string) config('app.timezone', 'UTC'));
        $today = new DateTimeImmutable('today', $zone);

        // # Re-anchor the parsed date into the same zone so the diff is whole days
        // # between two midnights rather than an offset-shifted partial day.
        $target = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $parsed->format('Y-m-d'),
            $zone
        );

        if ($target === false) {
            return null;
        }

        return (int) $today->diff($target)->format('%r%a');
    }

    public static function isOverdue(?string $dueDate): bool
    {
        $days = self::daysUntil($dueDate);

        return $days !== null && $days < 0;
    }

    /**
     * ! Returns null rather than a fallback date on bad input. A caller that gets
     *   null renders an em dash; a caller handed "today" would silently display a
     *   date the record does not contain.
     */
    private static function parseDateOnly(?string $value): ?DateTimeImmutable
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        // # Tolerates a full timestamp by taking its date part, since a driver may
        // # return 'Y-m-d H:i:s' for a DATE column. Taking the leading 10 characters
        // # is deliberate: parsing the whole thing would reintroduce a timezone.
        $text = substr(trim($value), 0, 10);

        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $text);

        return $parsed === false ? null : $parsed;
    }
}
