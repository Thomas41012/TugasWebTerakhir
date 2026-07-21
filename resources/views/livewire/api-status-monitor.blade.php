<?php

use App\Models\ApiLog;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component
{
    public array $apiStatuses = [];

    public array $statistics = [
        'total' => 0,
        'online' => 0,
        'offline' => 0,
        'unknown' => 0,
        'average_response_time' => 0,
    ];

    public function mount(): void
    {
        $this->loadApiStatuses();
    }

    #[On('global-sync-completed')]
    public function handleGlobalSyncCompleted(): void
    {
        $this->loadApiStatuses();
    }

    public function refreshApiStatuses(): void
    {
        $this->loadApiStatuses();
    }

    private function loadApiStatuses(): void
    {
        /*
        |--------------------------------------------------------------------------
        | External API Services
        |--------------------------------------------------------------------------
        */

        $services = [
            'Open-Meteo',
            'Exchange Rate',
            'World Bank',
            'GNews',
            'REST Countries',
        ];

        /*
        |--------------------------------------------------------------------------
        | Latest API Status
        |--------------------------------------------------------------------------
        */

        $this->apiStatuses = collect($services)
            ->map(function (string $service): array {
                $latestLog = ApiLog::query()
                    ->where('service', $service)
                    ->latest('requested_at')
                    ->first();

                /*
                |--------------------------------------------------------------------------
                | API Never Called
                |--------------------------------------------------------------------------
                */

                if (! $latestLog) {
                    return [
                        'service' => $service,

                        'status' => 'unknown',

                        'status_code' => null,

                        'response_time' => 0,

                        'endpoint' => null,

                        'error_message' => null,

                        'last_updated' => null,

                        'last_updated_human' => 'Never',
                    ];
                }

                /*
                |--------------------------------------------------------------------------
                | API Status
                |--------------------------------------------------------------------------
                */

                $status = (bool) $latestLog->success
                    ? 'success'
                    : 'failed';

                /*
                |--------------------------------------------------------------------------
                | API Data
                |--------------------------------------------------------------------------
                */

                return [
                    'service' => $service,

                    'status' => $status,

                    'status_code' =>
                        $latestLog->status_code !== null
                            ? (int) $latestLog->status_code
                            : null,

                    'response_time' =>
                        (int) (
                            $latestLog->response_time_ms
                            ?? 0
                        ),

                    'endpoint' =>
                        $latestLog->endpoint,

                    'error_message' =>
                        $latestLog->error_message,

                    'last_updated' =>
                        $latestLog->requested_at
                            ?->format('d M Y H:i:s'),

                    'last_updated_human' =>
                        $latestLog->requested_at
                            ?->diffForHumans()
                        ?? 'Never',
                ];
            })
            ->values()
            ->all();

        /*
        |--------------------------------------------------------------------------
        | API Statistics
        |--------------------------------------------------------------------------
        */

        $statuses = collect(
            $this->apiStatuses
        );

        $responseTimes = $statuses
            ->pluck('response_time')
            ->filter(
                fn ($responseTime): bool =>
                    (int) $responseTime > 0
            );

        $this->statistics = [
            'total' =>
                $statuses->count(),

            'online' =>
                $statuses
                    ->where(
                        'status',
                        'success'
                    )
                    ->count(),

            'offline' =>
                $statuses
                    ->where(
                        'status',
                        'failed'
                    )
                    ->count(),

            'unknown' =>
                $statuses
                    ->where(
                        'status',
                        'unknown'
                    )
                    ->count(),

            'average_response_time' =>
                $responseTimes->isNotEmpty()
                    ? round(
                        (float) $responseTimes->avg(),
                        2
                    )
                    : 0,
        ];
    }

    // MEMPERBAIKI ERROR: Meneruskan variabel properti secara eksplisit ke Blade
    public function with(): array
    {
        return [
            'apiStatuses' => $this->apiStatuses,
            'statistics' => $this->statistics,
        ];
    }
};

?>

<div
    wire:poll.300s="refreshApiStatuses"
>
    <div
        class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5"
    >

        {{-- HEADER --}}

        <div
            class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center"
        >
            <div>
                <h2 class="text-xl font-bold text-white">
                    API Status Monitoring
                </h2>

                <p class="mt-1 text-sm text-slate-400">
                    Real-time monitoring of external data services
                    used by the Global Supply Chain Intelligence platform.
                </p>
            </div>

            <div
                class="inline-flex items-center gap-2 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-2"
            >
                <span
                    class="h-2 w-2 animate-pulse rounded-full bg-emerald-400"
                ></span>

                <span
                    class="text-xs font-semibold uppercase tracking-wider text-emerald-400"
                >
                    Live Monitoring
                </span>
            </div>
        </div>

        {{-- STATISTICS --}}

        <div
            class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-5"
        >

            {{-- TOTAL API --}}

            <div
                class="rounded-xl border border-slate-800 bg-slate-950/70 p-4"
            >
                <p
                    class="text-xs uppercase tracking-wide text-slate-500"
                >
                    Total API
                </p>

                <p
                    class="mt-2 text-2xl font-bold text-cyan-400"
                >
                    {{
                        number_format(
                            $statistics['total'] ?? 0
                        )
                    }}
                </p>
            </div>

            {{-- ONLINE --}}

            <div
                class="rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-4"
            >
                <p
                    class="text-xs uppercase tracking-wide text-slate-500"
                >
                    Online
                </p>

                <p
                    class="mt-2 text-2xl font-bold text-emerald-400"
                >
                    {{
                        number_format(
                            $statistics['online'] ?? 0
                        )
                    }}
                </p>
            </div>

            {{-- OFFLINE --}}

            <div
                class="rounded-xl border border-rose-500/20 bg-rose-500/5 p-4"
            >
                <p
                    class="text-xs uppercase tracking-wide text-slate-500"
                >
                    Offline
                </p>

                <p
                    class="mt-2 text-2xl font-bold text-rose-400"
                >
                    {{
                        number_format(
                            $statistics['offline'] ?? 0
                        )
                    }}
                </p>
            </div>

            {{-- UNKNOWN --}}

            <div
                class="rounded-xl border border-orange-500/20 bg-orange-500/5 p-4"
            >
                <p
                    class="text-xs uppercase tracking-wide text-slate-500"
                >
                    Unknown
                </p>

                <p
                    class="mt-2 text-2xl font-bold text-orange-400"
                >
                    {{
                        number_format(
                            $statistics['unknown'] ?? 0
                        )
                    }}
                </p>
            </div>

            {{-- AVERAGE RESPONSE TIME --}}

            <div
                class="rounded-xl border border-violet-500/20 bg-violet-500/5 p-4"
            >
                <p
                    class="text-xs uppercase tracking-wide text-slate-500"
                >
                    Avg Response
                </p>

                <p
                    class="mt-2 text-2xl font-bold text-violet-400"
                >
                    {{
                        number_format(
                            $statistics[
                                'average_response_time'
                            ] ?? 0,
                            2
                        )
                    }}

                    <span class="text-xs">
                        ms
                    </span>
                </p>
            </div>

        </div>

        {{-- API SERVICES --}}

        <div
            class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3"
        >
            @forelse ($apiStatuses as $api)

                <article
                    wire:key="api-status-{{ md5($api['service']) }}"
                    class="rounded-xl border border-slate-800 bg-slate-950/70 p-5 transition hover:border-cyan-500/30"
                >

                    {{-- SERVICE HEADER --}}

                    <div
                        class="flex items-start justify-between gap-4"
                    >
                        <div>
                            <h3
                                class="font-semibold text-white"
                            >
                                {{ $api['service'] }}
                            </h3>

                            <p
                                class="mt-1 text-xs text-slate-500"
                            >
                                External Data Service
                            </p>
                        </div>

                        {{-- STATUS BADGE --}}

                        <span
                            @class([
                                'inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold',

                                'border-emerald-500/30 bg-emerald-500/10 text-emerald-400'
                                    => $api['status'] === 'success',

                                'border-rose-500/30 bg-rose-500/10 text-rose-400'
                                    => $api['status'] === 'failed',

                                'border-orange-500/30 bg-orange-500/10 text-orange-400'
                                    => $api['status'] === 'unknown',
                            ])
                        >
                            <span
                                @class([
                                    'h-2 w-2 rounded-full',

                                    'animate-pulse bg-emerald-400'
                                        => $api['status'] === 'success',

                                    'bg-rose-400'
                                        => $api['status'] === 'failed',

                                    'bg-orange-400'
                                        => $api['status'] === 'unknown',
                                ])
                            ></span>

                            @if ($api['status'] === 'success')
                                ONLINE
                            @elseif ($api['status'] === 'failed')
                                OFFLINE
                            @else
                                UNKNOWN
                            @endif
                        </span>
                    </div>

                    {{-- SERVICE INFORMATION --}}

                    <div
                        class="mt-5 space-y-3 border-t border-slate-800 pt-4"
                    >

                        {{-- HTTP STATUS --}}

                        <div
                            class="flex items-center justify-between gap-4"
                        >
                            <span
                                class="text-xs text-slate-500"
                            >
                                HTTP Status
                            </span>

                            <span
                                @class([
                                    'text-xs font-medium',

                                    'text-emerald-400'
                                        => $api['status_code'] !== null
                                        && $api['status_code'] >= 200
                                        && $api['status_code'] < 400,

                                    'text-rose-400'
                                        => $api['status_code'] !== null
                                        && $api['status_code'] >= 400,

                                    'text-slate-500'
                                        => $api['status_code'] === null,
                                ])
                            >
                                {{
                                    $api['status_code']
                                    ?? '-'
                                }}
                            </span>
                        </div>

                        {{-- RESPONSE TIME --}}

                        <div
                            class="flex items-center justify-between gap-4"
                        >
                            <span
                                class="text-xs text-slate-500"
                            >
                                Response Time
                            </span>

                            <span
                                @class([
                                    'text-xs font-medium',

                                    'text-emerald-400'
                                        => $api['response_time'] > 0
                                        && $api['response_time'] <= 1000,

                                    'text-orange-400'
                                        => $api['response_time'] > 1000
                                        && $api['response_time'] <= 3000,

                                    'text-rose-400'
                                        => $api['response_time'] > 3000,

                                    'text-slate-500'
                                        => $api['response_time'] <= 0,
                                ])
                            >
                                @if ($api['response_time'] > 0)

                                    {{
                                        number_format(
                                            $api['response_time']
                                        )
                                    }}
                                    ms

                                @else

                                    -

                                @endif
                            </span>
                        </div>

                        {{-- LAST UPDATED --}}

                        <div
                            class="flex items-center justify-between gap-4"
                        >
                            <span
                                class="text-xs text-slate-500"
                            >
                                Last Updated
                            </span>

                            <span
                                class="text-right text-xs text-cyan-400"
                                title="{{ $api['last_updated'] ?? 'Never' }}"
                            >
                                {{
                                    $api[
                                        'last_updated_human'
                                    ]
                                }}
                            </span>
                        </div>

                    </div>

                    {{-- ENDPOINT: Hanya admin yang boleh melihat --}}

                    @if (! empty($api['endpoint']) && auth()->check() && auth()->user()->role === 'admin')

                        <div
                            class="mt-4 rounded-lg border border-slate-800 bg-slate-900/70 p-3"
                        >
                            <p
                                class="text-[10px] uppercase tracking-wide text-slate-600"
                            >
                                Endpoint
                            </p>

                            <p
                                class="mt-1 truncate font-mono text-xs text-slate-400"
                                title="{{ $api['endpoint'] }}"
                            >
                                {{ $api['endpoint'] }}
                            </p>
                        </div>

                    @endif

                    {{-- ERROR MESSAGE --}}

                    @if (
                        $api['status'] === 'failed'
                        && ! empty($api['error_message'])
                    )

                        <div
                            class="mt-4 rounded-lg border border-rose-500/20 bg-rose-500/5 p-3"
                        >
                            <p
                                class="text-[10px] uppercase tracking-wide text-rose-500"
                            >
                                Error
                            </p>

                            <p
                                class="mt-1 line-clamp-3 text-xs text-rose-300"
                                title="{{ $api['error_message'] }}"
                            >
                                {{
                                    $api['error_message']
                                }}
                            </p>
                        </div>

                    @endif

                </article>

            @empty

                <div
                    class="col-span-full rounded-xl border border-slate-800 bg-slate-950/70 p-8 text-center"
                >
                    <p class="text-sm text-slate-500">
                        No API monitoring data available.
                    </p>
                </div>

            @endforelse
        </div>
    </div>
</div>