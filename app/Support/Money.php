<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Formats the money strings the API emits, for display.
 *
 * ! THE API SENDS MONEY AS A FIXED-2 DECIMAL STRING ("11350.00"), NEVER A NUMBER,
 *   and this class keeps it that way. DECIMAL(15,2) does not survive a round trip
 *   through a JSON double: a client that casts to float and adds will eventually be
 *   a cent out, and on an invoice a cent out is a support ticket.
 *
 * ! Nothing here does arithmetic. There is no add(), no subtract(), no total() — on
 *   purpose. Every figure this app displays is one the API already computed, and the
 *   moment a helper here starts summing line items, this app owns a second copy of
 *   the invoice arithmetic that can disagree with the first. If a screen seems to
 *   need a total that the API does not send, the fix is an API field, not a sum
 *   here.
 *
 * ? The one deliberate exception is the invoice form's live preview, which does add
 *   up in the browser. It is labelled an estimate, and what gets saved is always
 *   the server's figure — see resources/js/invoice-form.js.
 */
final class Money
{
    public const CURRENCY = 'RM';

    /**
     * "11350.00" -> "RM 11,350.00"
     */
    public static function format(mixed $amount): string
    {
        return self::CURRENCY . ' ' . self::amount($amount);
    }

    /**
     * "11350.00" -> "11,350.00", with no currency prefix.
     *
     * ! Grouped by string surgery, not by number_format(). number_format() takes a
     *   float, and casting a DECIMAL(15,2) string to float is the exact conversion
     *   the API's string encoding exists to avoid — for values near the top of the
     *   range it is lossy, and doing it "only for display" still displays the wrong
     *   number.
     */
    public static function amount(mixed $amount): string
    {
        $text = self::normalise($amount);

        $negative = str_starts_with($text, '-');
        $text     = ltrim($text, '-');

        [$whole, $fraction] = self::split($text);

        $grouped = strrev(implode(',', str_split(strrev($whole), 3)));

        return ($negative ? '-' : '') . $grouped . '.' . $fraction;
    }

    /**
     * A value safe to put in a number input's `value`, i.e. ungrouped fixed-2.
     */
    public static function input(mixed $amount): string
    {
        $text = self::normalise($amount);

        [$whole, $fraction] = self::split(ltrim($text, '-'));

        return (str_starts_with($text, '-') ? '-' : '') . $whole . '.' . $fraction;
    }

    /**
     * Is this money value zero? Answered on the digits, not on a float compare.
     */
    public static function isZero(mixed $amount): bool
    {
        return preg_replace('/[^1-9]/', '', self::normalise($amount)) === '';
    }

    public static function isPositive(mixed $amount): bool
    {
        return !self::isZero($amount) && !str_starts_with(self::normalise($amount), '-');
    }

    /**
     * Coerce whatever arrived into a `-?digits.digits` string.
     *
     * ! Tolerant of a float or int on the way in — a fixture, a form repopulation or
     *   a hand-written value in a test will not always be a string — but it never
     *   produces a float internally, and anything unrecognisable becomes "0.00"
     *   rather than a PHP warning rendered into the page.
     */
    private static function normalise(mixed $amount): string
    {
        if (is_string($amount) && preg_match('/^-?\d+(\.\d+)?$/', trim($amount)) === 1) {
            return trim($amount);
        }

        if (is_int($amount)) {
            return (string) $amount;
        }

        if (is_float($amount)) {
            return number_format($amount, 2, '.', '');
        }

        return '0.00';
    }

    /**
     * Split into a whole part and exactly two fraction digits.
     *
     * @return array{0: string, 1: string}
     */
    private static function split(string $text): array
    {
        $parts    = explode('.', $text, 2);
        $whole    = $parts[0] === '' ? '0' : $parts[0];
        $fraction = $parts[1] ?? '';

        // # Pad or truncate to 2. The API always sends 2, so truncation only ever
        // # bites on a locally-constructed value — and silently rounding one here
        // # would hide that it was built wrong.
        $fraction = substr($fraction . '00', 0, 2);

        return [$whole, $fraction];
    }
}
