<?php

use App\Models\Country;
use App\Services\PortService;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component
{
    public string $search = '';

    public string $selectedCountry = '';

    public array $countries = [];

    public array $geoJson = [
        'type' => 'FeatureCollection',
        'features' => [],
    ];

    public array $statistics = [
        'total_ports' => 0,
        'active_ports' => 0,
        'high_risk_ports' => 0,
        'countries' => 0,
    ];

    public function mount(): void
    {
        $this->loadCountries();

        $this->loadPorts();
    }

    #[On('global-sync-completed')]
    public function handleGlobalSyncCompleted(): void
    {
        $this->loadCountries();

        $this->loadPorts();
    }

    public function updatedSearch(): void
    {
        $this->loadPorts();
    }

    public function updatedSelectedCountry(): void
    {
        $this->loadPorts();

        if ($this->selectedCountry !== '' && auth()->check()) {
            $country = Country::find($this->selectedCountry);
            if ($country) {
                \App\Models\UserActivity::create([
                    'user_id' => auth()->id(),
                    'activity_type' => 'select_country',
                    'description' => "Filtered map by country: {$country->name}.",
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            }
        }
    }

    public function refreshMap(): void
    {
        $this->loadPorts();
    }

    public function resetFilters(): void
    {
        $this->search = '';

        $this->selectedCountry = '';

        $this->loadPorts();
    }

    private function loadCountries(): void
    {
        $this->countries = Country::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'iso2',
                'iso3',
            ])
            ->map(
                fn (Country $country): array => [
                    'id' => $country->id,
                    'name' => $country->name,
                    'iso2' => $country->iso2,
                    'iso3' => $country->iso3,
                ]
            )
            ->values()
            ->all();
    }

    private function loadPorts(): void
    {
        $portService = app(
            PortService::class
        );

        $search = trim(
            $this->search
        );

        $countryId = $this->selectedCountry !== ''
            ? (int) $this->selectedCountry
            : null;

        $this->geoJson = $portService->getGeoJson(
            search: $search !== ''
                ? $search
                : null,

            countryId: $countryId
        );

        $features = $this->geoJson['features']
            ?? [];

        $featuresCollection = collect(
            $features
        );

        $activePorts = $featuresCollection
            ->filter(
                fn (array $feature): bool =>
                    strtolower(
                        (string) data_get(
                            $feature,
                            'properties.status',
                            ''
                        )
                    ) === 'active'
            )
            ->count();

        $highRiskPorts = $featuresCollection
            ->filter(
                fn (array $feature): bool =>
                    (float) data_get(
                        $feature,
                        'properties.risk_score',
                        0
                    ) >= 70
            )
            ->count();

        $countries = $featuresCollection
            ->pluck('properties.country.id')
            ->filter(
                fn ($countryId): bool =>
                    $countryId !== null
            )
            ->unique()
            ->count();

        $this->statistics = [
            'total_ports' => count($features),

            'active_ports' => $activePorts,

            'high_risk_ports' => $highRiskPorts,

            'countries' => $countries,
        ];

        $this->dispatch(
            'ports-updated',
            geoJson: $this->geoJson
        );
    }
};

?>

<div wire:poll.300s="refreshMap">

    <div
        class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/80"
    >

        {{-- HEADER --}}

        <div
            class="flex flex-col justify-between gap-4 border-b border-slate-800 p-5 lg:flex-row lg:items-center"
        >
            <div>
                <div
                    class="mb-2 inline-flex items-center gap-2 rounded-full border border-cyan-500/20 bg-cyan-500/10 px-3 py-1"
                >
                    <span
                        class="h-2 w-2 animate-pulse rounded-full bg-cyan-400"
                    ></span>

                    <span
                        class="text-xs font-semibold uppercase tracking-wider text-cyan-400"
                    >
                        Live Port Monitoring
                    </span>
                </div>

                <h2 class="text-xl font-bold text-white">
                    Global Port Intelligence Map
                </h2>

                <p class="mt-1 text-sm text-slate-400">
                    Interactive monitoring of global port infrastructure,
                    operational status and supply chain risk.
                </p>
            </div>

            {{-- FILTER --}}

            <div class="flex flex-col gap-3 sm:flex-row">

                <input
                    type="text"
                    wire:model.live.debounce.500ms="search"
                    placeholder="Search port..."
                    class="rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm text-white outline-none transition placeholder:text-slate-600 focus:border-emerald-400"
                >

                <select
                    wire:model.live="selectedCountry"
                    class="rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-sm text-white outline-none transition focus:border-cyan-400"
                >
                    <option value="">
                        All Countries
                    </option>

                    @foreach ($countries as $country)
                        <option value="{{ $country['id'] }}">
                            {{ $country['name'] }}
                        </option>
                    @endforeach
                </select>

                @if (
                    $search !== ''
                    || $selectedCountry !== ''
                )
                    <button
                        type="button"
                        wire:click="resetFilters"
                        class="rounded-xl border border-slate-700 bg-slate-800 px-4 py-2.5 text-sm font-medium text-slate-300 transition hover:border-rose-500/40 hover:bg-rose-500/10 hover:text-rose-400"
                    >
                        Reset
                    </button>
                @endif

            </div>
        </div>

        {{-- STATISTICS --}}

        <div
            class="grid gap-3 border-b border-slate-800 p-4 sm:grid-cols-2 lg:grid-cols-4"
        >

            <div
                class="rounded-xl border border-slate-800 bg-slate-950/70 p-4"
            >
                <p
                    class="text-xs uppercase tracking-wide text-slate-500"
                >
                    Total Ports
                </p>

                <p
                    class="mt-2 text-2xl font-bold text-cyan-400"
                >
                    {{
                        number_format(
                            $statistics['total_ports'] ?? 0
                        )
                    }}
                </p>
            </div>

            <div
                class="rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-4"
            >
                <p
                    class="text-xs uppercase tracking-wide text-slate-500"
                >
                    Active Ports
                </p>

                <p
                    class="mt-2 text-2xl font-bold text-emerald-400"
                >
                    {{
                        number_format(
                            $statistics['active_ports'] ?? 0
                        )
                    }}
                </p>
            </div>

            <div
                class="rounded-xl border border-rose-500/20 bg-rose-500/5 p-4"
            >
                <p
                    class="text-xs uppercase tracking-wide text-slate-500"
                >
                    High Risk Ports
                </p>

                <p
                    class="mt-2 text-2xl font-bold text-rose-400"
                >
                    {{
                        number_format(
                            $statistics['high_risk_ports'] ?? 0
                        )
                    }}
                </p>
            </div>

            <div
                class="rounded-xl border border-violet-500/20 bg-violet-500/5 p-4"
            >
                <p
                    class="text-xs uppercase tracking-wide text-slate-500"
                >
                    Countries
                </p>

                <p
                    class="mt-2 text-2xl font-bold text-violet-400"
                >
                    {{
                        number_format(
                            $statistics['countries'] ?? 0
                        )
                    }}
                </p>
            </div>

        </div>

        {{-- LOADING --}}

        <div
            wire:loading.flex
            wire:target="search,selectedCountry,refreshMap,resetFilters"
            class="items-center justify-center border-b border-slate-800 bg-slate-950/80 px-4 py-3"
        >
            <svg
                class="mr-2 h-4 w-4 animate-spin text-cyan-400"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
            >
                <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                ></circle>

                <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                ></path>
            </svg>

            <span class="text-sm text-slate-400">
                Updating port intelligence map...
            </span>
        </div>

        {{-- EMPTY DATA --}}

        @if (($statistics['total_ports'] ?? 0) === 0)
            <div
                class="border-b border-amber-500/20 bg-amber-500/5 px-5 py-4"
            >
                <p class="text-sm text-amber-300">
                    No port data available for the selected filter.
                </p>
            </div>
        @endif

        {{-- MAP --}}

        <div
            wire:ignore
            id="global-port-map"
            class="h-[600px] w-full bg-slate-950"
        ></div>

        {{-- FOOTER --}}

        <div
            class="flex flex-col justify-between gap-3 border-t border-slate-800 bg-slate-950/40 px-5 py-3 sm:flex-row sm:items-center"
        >
            <p class="text-xs text-slate-500">
                Port data automatically refreshes every 5 minutes.
            </p>

            <div class="flex flex-wrap items-center gap-4">

                <div class="flex items-center gap-2">
                    <span
                        class="h-2.5 w-2.5 rounded-full bg-emerald-400"
                    ></span>

                    <span class="text-xs text-slate-500">
                        Low Risk
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <span
                        class="h-2.5 w-2.5 rounded-full bg-orange-400"
                    ></span>

                    <span class="text-xs text-slate-500">
                        Medium Risk
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <span
                        class="h-2.5 w-2.5 rounded-full bg-rose-400"
                    ></span>

                    <span class="text-xs text-slate-500">
                        High Risk
                    </span>
                </div>

            </div>
        </div>

    </div>

    @script
        <script>
            let portGeoJson = @js($geoJson);

            let globalPortMap = null;

            let portMarkerCluster = null;

            function escapePortHtml(value) {
                if (
                    value === null
                    || value === undefined
                    || value === ''
                ) {
                    return '-';
                }

                return String(value)
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function getPortRiskInformation(riskScore) {
                const score = Number(
                    riskScore ?? 0
                );

                if (score >= 70) {
                    return {
                        label: 'High Risk',
                        color: '#fb7185',
                        background: 'rgba(244, 63, 94, 0.15)',
                    };
                }

                if (score >= 40) {
                    return {
                        label: 'Medium Risk',
                        color: '#fb923c',
                        background: 'rgba(249, 115, 22, 0.15)',
                    };
                }

                return {
                    label: 'Low Risk',
                    color: '#34d399',
                    background: 'rgba(16, 185, 129, 0.15)',
                };
            }

            function createPortMarker(feature) {
                const properties =
                    feature?.properties ?? {};

                const coordinates =
                    feature?.geometry?.coordinates ?? [];

                const longitude =
                    Number(coordinates[0]);

                const latitude =
                    Number(coordinates[1]);

                if (
                    !Number.isFinite(latitude)
                    || !Number.isFinite(longitude)
                ) {
                    return null;
                }

                const risk =
                    getPortRiskInformation(
                        properties.risk_score
                    );

                const marker = L.circleMarker(
                    [
                        latitude,
                        longitude,
                    ],
                    {
                        radius: 8,
                        color: risk.color,
                        weight: 2,
                        fillColor: risk.color,
                        fillOpacity: 0.75,
                    }
                );

                const country =
                    properties.country ?? {};

                const riskScore = Number(
                    properties.risk_score ?? 0
                );

                const congestionLevel = Number(
                    properties.congestion_level ?? 0
                );

                const popupContent = `
                    <div
                        style="
                            min-width: 230px;
                            font-family: Inter, ui-sans-serif, system-ui, sans-serif;
                        "
                    >
                        <div style="margin-bottom: 10px;">
                            <div
                                style="
                                    font-size: 16px;
                                    font-weight: 700;
                                    color: #0f172a;
                                "
                            >
                                ${escapePortHtml(properties.name)}
                            </div>

                            <div
                                style="
                                    margin-top: 3px;
                                    font-size: 12px;
                                    color: #64748b;
                                "
                            >
                                ${escapePortHtml(country.name)}
                            </div>
                        </div>

                        <div
                            style="
                                border-top: 1px solid #e2e8f0;
                                padding-top: 10px;
                            "
                        >
                            <div
                                style="
                                    display: flex;
                                    justify-content: space-between;
                                    gap: 20px;
                                    margin-bottom: 6px;
                                    font-size: 12px;
                                "
                            >
                                <span style="color: #64748b;">
                                    UN/LOCODE
                                </span>

                                <strong>
                                    ${escapePortHtml(properties.unlocode)}
                                </strong>
                            </div>

                            <div
                                style="
                                    display: flex;
                                    justify-content: space-between;
                                    gap: 20px;
                                    margin-bottom: 6px;
                                    font-size: 12px;
                                "
                            >
                                <span style="color: #64748b;">
                                    City
                                </span>

                                <strong>
                                    ${escapePortHtml(properties.city)}
                                </strong>
                            </div>

                            <div
                                style="
                                    display: flex;
                                    justify-content: space-between;
                                    gap: 20px;
                                    margin-bottom: 6px;
                                    font-size: 12px;
                                "
                            >
                                <span style="color: #64748b;">
                                    Port Type
                                </span>

                                <strong>
                                    ${escapePortHtml(
                                        properties.port_type
                                    )}
                                </strong>
                            </div>

                            <div
                                style="
                                    display: flex;
                                    justify-content: space-between;
                                    gap: 20px;
                                    margin-bottom: 6px;
                                    font-size: 12px;
                                "
                            >
                                <span style="color: #64748b;">
                                    Status
                                </span>

                                <strong>
                                    ${escapePortHtml(properties.status)}
                                </strong>
                            </div>

                            <div
                                style="
                                    display: flex;
                                    justify-content: space-between;
                                    gap: 20px;
                                    margin-bottom: 6px;
                                    font-size: 12px;
                                "
                            >
                                <span style="color: #64748b;">
                                    Congestion
                                </span>

                                <strong>
                                    ${congestionLevel.toFixed(0)}%
                                </strong>
                            </div>

                            <div
                                style="
                                    display: flex;
                                    justify-content: space-between;
                                    gap: 20px;
                                    margin-bottom: 10px;
                                    font-size: 12px;
                                "
                            >
                                <span style="color: #64748b;">
                                    Risk Score
                                </span>

                                <strong>
                                    ${riskScore.toFixed(2)}
                                </strong>
                            </div>

                            <div
                                style="
                                    display: inline-flex;
                                    align-items: center;
                                    padding: 5px 9px;
                                    border-radius: 9999px;
                                    color: ${risk.color};
                                    background: ${risk.background};
                                    font-size: 11px;
                                    font-weight: 700;
                                "
                            >
                                ${risk.label}
                            </div>

                            ${country.id ? `
                            <div style="margin-top: 12px; border-top: 1px solid #e2e8f0; padding-top: 10px;">
                                <a href="/countries/${country.id}" style="display: block; text-align: center; font-size: 11px; font-weight: 600; color: #ffffff; background-color: #6366f1; border-radius: 6px; padding: 6px 10px; text-decoration: none; transition: background-color 0.15s; font-family: sans-serif;">
                                    View Country Intelligence →
                                </a>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                `;

                marker.bindPopup(
                    popupContent,
                    {
                        maxWidth: 320,
                    }
                );

                return marker;
            }

            function renderPortMarkers(
                geoJson,
                fitBounds = true
            ) {
                if (
                    !globalPortMap
                    || !portMarkerCluster
                ) {
                    return;
                }

                portMarkerCluster.clearLayers();

                const features =
                    geoJson?.features ?? [];

                const bounds = [];

                features.forEach(
                    function (feature) {
                        const marker =
                            createPortMarker(feature);

                        if (!marker) {
                            return;
                        }

                        portMarkerCluster.addLayer(
                            marker
                        );

                        const coordinates =
                            feature?.geometry?.coordinates
                            ?? [];

                        const longitude =
                            Number(coordinates[0]);

                        const latitude =
                            Number(coordinates[1]);

                        if (
                            Number.isFinite(latitude)
                            && Number.isFinite(longitude)
                        ) {
                            bounds.push([
                                latitude,
                                longitude,
                            ]);
                        }
                    }
                );

                if (
                    fitBounds
                    && bounds.length > 0
                ) {
                    globalPortMap.fitBounds(
                        bounds,
                        {
                            padding: [
                                40,
                                40,
                            ],
                            maxZoom: 6,
                        }
                    );
                }

                if (
                    fitBounds
                    && bounds.length === 0
                ) {
                    globalPortMap.setView(
                        [
                            15,
                            100,
                        ],
                        2
                    );
                }

                setTimeout(
                    function () {
                        globalPortMap.invalidateSize();
                    },
                    100
                );
            }

            function initializeGlobalPortMap() {
                const mapElement =
                    document.getElementById(
                        'global-port-map'
                    );

                if (!mapElement) {
                    return;
                }

                if (
                    typeof L === 'undefined'
                ) {
                    console.error(
                        'Leaflet library is not loaded.'
                    );

                    return;
                }

                if (
                    typeof L.markerClusterGroup
                    !== 'function'
                ) {
                    console.error(
                        'Leaflet MarkerCluster library is not loaded.'
                    );

                    return;
                }

                if (globalPortMap) {
                    globalPortMap.invalidateSize();

                    renderPortMarkers(
                        portGeoJson,
                        false
                    );

                    return;
                }

                globalPortMap = L.map(
                    mapElement,
                    {
                        center: [
                            15,
                            100,
                        ],
                        zoom: 2,
                        minZoom: 2,
                        maxZoom: 18,
                        worldCopyJump: true,
                        zoomControl: true,
                    }
                );

                L.tileLayer(
                    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                    {
                        maxZoom: 19,
                        attribution:
                            '&copy; OpenStreetMap contributors',
                    }
                ).addTo(
                    globalPortMap
                );

                portMarkerCluster =
                    L.markerClusterGroup(
                        {
                            chunkedLoading: true,
                            chunkInterval: 100,
                            chunkDelay: 50,
                            maxClusterRadius: 50,
                            showCoverageOnHover: false,
                            spiderfyOnMaxZoom: true,
                            removeOutsideVisibleBounds: true,
                        }
                    );

                globalPortMap.addLayer(
                    portMarkerCluster
                );

                renderPortMarkers(
                    portGeoJson,
                    true
                );

                setTimeout(
                    function () {
                        globalPortMap.invalidateSize();
                    },
                    300
                );
            }

            initializeGlobalPortMap();

            $wire.on(
                'ports-updated',
                function (event) {
                    const payload =
                        Array.isArray(event)
                            ? event[0]
                            : event;

                    portGeoJson =
                        payload?.geoJson
                        ?? {
                            type: 'FeatureCollection',
                            features: [],
                        };

                    if (!globalPortMap) {
                        initializeGlobalPortMap();
                    }

                    renderPortMarkers(
                        portGeoJson,
                        true
                    );
                }
            );
        </script>
    @endscript

</div>