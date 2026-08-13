@extends('layouts.app')

@section('title', 'Activity log')

@php
    use App\Support\DisplayDate;
@endphp

@section('content')
    <div class="mb-6">
        <h1 class="page-title">Activity log</h1>
        <p class="page-subtitle">
            Every request made against this business, newest first.
        </p>
    </div>

    {{--
        ! This explanation is not decoration. The log records SUCCESSFUL READS as well as
          changes — "who looked at the customer list" is exactly the question a trail over
          other people's money should answer — so it is far busier than a change-log and
          every user's first reaction is that it is broken. Saying so, and pointing at the
          filter, is cheaper than fielding the question.
    --}}
    <div class="mb-5 alert-info">
        <x-icon name="activity" class="mt-0.5 h-4 w-4 shrink-0" />
        <div>
            <p class="font-medium">Reads are logged too, not just changes.</p>
            <p class="mt-0.5">
                Entries with no action were someone viewing a page. Filter by action to see
                only things that changed. Sign-ins are recorded against the person rather than
                the business, so they do not appear here.
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <form method="GET" action="{{ url()->current() }}" x-data="listFilter"
                  class="flex flex-wrap items-end gap-3">
                <input type="hidden" name="page" value="1">

                <div class="w-44">
                    <label for="action" class="sr-only">Action</label>
                    <select id="action" name="action" class="input" x-on:change="submitNow()">
                        <option value="">All activity</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}" @selected(request()->query('action') === $action)>
                                {{ Str::headline(str_replace('_', ' ', $action)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-44">
                    <label for="entity_type" class="sr-only">Record type</label>
                    <select id="entity_type" name="entity_type" class="input" x-on:change="submitNow()">
                        <option value="">All record types</option>
                        @foreach ($entityTypes as $type)
                            <option value="{{ $type }}" @selected(request()->query('entity_type') === $type)>
                                {{ Str::headline(str_replace('_', ' ', $type)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn-secondary">Apply</button>

                @if (request()->hasAny(['action', 'entity_type']))
                    <a href="{{ url()->current() }}" class="btn-ghost">Clear</a>
                @endif
            </form>
        </div>

        @if ($entries === [])
            <x-empty-state
                icon="activity"
                title="Nothing recorded yet"
                text="Activity appears here as you and your team use Runovia."
            />
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($entries as $entry)
                    @php
                        $action = $entry['action'] ?? null;
                        $changes = is_array($entry['changes'] ?? null) ? $entry['changes'] : [];
                    @endphp
                    <li class="px-5 py-4 sm:px-6">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="flex flex-wrap items-center gap-2 text-sm">
                                    @if ($action)
                                        <span @class([
                                            'badge-paid'      => $action === 'created',
                                            'badge-sent'      => $action === 'updated',
                                            'badge-cancelled' => in_array($action, ['deleted', 'cancelled'], true),
                                            'badge-neutral'   => ! in_array($action, ['created', 'updated', 'deleted', 'cancelled'], true),
                                        ])>{{ Str::headline(str_replace('_', ' ', $action)) }}</span>
                                    @else
                                        <span class="badge-neutral">Viewed</span>
                                    @endif

                                    @if (! empty($entry['entity_type']))
                                        <span class="font-medium text-slate-900">
                                            {{ Str::headline(str_replace('_', ' ', $entry['entity_type'])) }}
                                            @if (! empty($entry['entity_id']))
                                                #{{ $entry['entity_id'] }}
                                            @endif
                                        </span>
                                    @endif
                                </p>

                                @if ($changes !== [])
                                    {{--
                                        ! "Submitted", not "changed from / to". The API records WHAT WAS
                                          SENT, not a before-and-after diff, and labelling it as a diff
                                          would be a straightforward lie about what the row contains.

                                        ! Sensitive values arrive already replaced with the literal
                                          string `_excluded_` by the API's redaction, before anything
                                          reaches the table. Nothing here has to filter.
                                    --}}
                                    <div class="mt-2 rounded-lg bg-slate-50 px-3 py-2">
                                        <p class="mb-1 text-[0.6875rem] font-semibold uppercase tracking-wide text-slate-500">
                                            Submitted
                                        </p>
                                        <dl class="grid gap-x-4 gap-y-1 text-xs sm:grid-cols-2">
                                            @foreach ($changes as $field => $value)
                                                <div class="flex gap-2">
                                                    <dt class="shrink-0 text-slate-500">
                                                        {{ Str::headline(str_replace('_', ' ', (string) $field)) }}:
                                                    </dt>
                                                    <dd class="min-w-0 break-words text-slate-800">
                                                        @if (is_scalar($value) || $value === null)
                                                            {{ $value === null ? '—' : (string) $value }}
                                                        @else
                                                            {{ json_encode($value) }}
                                                        @endif
                                                    </dd>
                                                </div>
                                            @endforeach
                                        </dl>
                                    </div>
                                @endif
                            </div>

                            <div class="shrink-0 text-right">
                                {{-- An instant, so it IS converted to the viewer's timezone. --}}
                                <p class="text-xs text-slate-500">
                                    {{ DisplayDate::instant($entry['created_at']) }}
                                </p>
                                @if (! empty($entry['user_id']))
                                    <p class="text-xs text-slate-400">User #{{ $entry['user_id'] }}</p>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            <x-pagination :response="$response" />
        @endif
    </div>
@endsection
