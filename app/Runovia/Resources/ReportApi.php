<?php

declare(strict_types=1);

namespace App\Runovia\Resources;

/**
 * The dashboard aggregate.
 *
 * ! THIS ENDPOINT EXISTS SO THIS APP NEVER SUMS MONEY. Before it, a dashboard could only get
 *   "total invoiced" and "outstanding" by paging every invoice and adding the figures up —
 *   wrong twice over: this app only ever holds one page at a time, so the total would
 *   silently mean "of the most recent 25", and adding fixed-2 decimal strings in PHP floats
 *   loses cents. The API aggregates in SQL, in integer cents, over every invoice the business
 *   owns.
 *
 * ! CANCELLED invoices are excluded from every money figure and from the overdue set, but are
 *   still counted in `invoices.cancelled` and `invoices.total`. A cancelled invoice is not
 *   owed. Do not re-derive any of these figures from the counts.
 */
final class ReportApi extends ResourceApi
{
    /**
     * @return array{
     *     invoices: array<string, int>,
     *     money: array{invoiced: string, collected: string, outstanding: string},
     *     overdue: array{count: int, amount: string}
     * }
     */
    public function summary(): array
    {
        $record = $this->httpGet('/api/v1/reports/summary')->record();

        /*
         * ! Defaults merged in so a view can read `money.outstanding` unconditionally. An
         *   empty business is a legitimate success — the API answers 200 with zeros — but the
         *   `invoices` map is keyed by the statuses that actually occur, so a business with no
         *   cancelled invoices has no `cancelled` key at all. A dashboard reading it directly
         *   would emit a PHP warning into the page on day one of every new account.
         */
        return [
            'invoices' => array_merge([
                'total'          => 0,
                'draft'          => 0,
                'sent'           => 0,
                'partially_paid' => 0,
                'paid'           => 0,
                'cancelled'      => 0,
            ], is_array($record['invoices'] ?? null) ? $record['invoices'] : []),

            'money' => array_merge([
                'invoiced'    => '0.00',
                'collected'   => '0.00',
                'outstanding' => '0.00',
            ], is_array($record['money'] ?? null) ? $record['money'] : []),

            'overdue' => array_merge([
                'count'  => 0,
                'amount' => '0.00',
            ], is_array($record['overdue'] ?? null) ? $record['overdue'] : []),
        ];
    }
}
