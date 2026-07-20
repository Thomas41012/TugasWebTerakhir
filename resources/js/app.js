import './bootstrap';

/*
|--------------------------------------------------------------------------
| Leaflet
|--------------------------------------------------------------------------
*/

import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

import 'leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';

/*
|--------------------------------------------------------------------------
| ApexCharts
|--------------------------------------------------------------------------
*/

import ApexCharts from 'apexcharts';

/*
|--------------------------------------------------------------------------
| Day.js
|--------------------------------------------------------------------------
*/

import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';

dayjs.extend(utc);
dayjs.extend(timezone);

/*
|--------------------------------------------------------------------------
| Global Libraries
|--------------------------------------------------------------------------
*/

window.L = L;
window.ApexCharts = ApexCharts;
window.dayjs = dayjs;

/*
|--------------------------------------------------------------------------
| Global Variables
|--------------------------------------------------------------------------
*/

let globalMap = null;
let markerCluster = null;

let riskChart = null;
let comparisonChart = null;

let clockInterval = null;

/*
|--------------------------------------------------------------------------
| Helper: Livewire Event Payload
|--------------------------------------------------------------------------
*/

function getEventPayload(event, key = null) {
    let payload = event;

    if (Array.isArray(payload)) {
        payload = payload[0];
    }

    if (
        key !== null &&
        payload &&
        typeof payload === 'object' &&
        payload[key] !== undefined
    ) {
        return payload[key];
    }

    return payload;
}

/*
|--------------------------------------------------------------------------
| Helper: Escape HTML
|--------------------------------------------------------------------------
*/

function escapeHtml(value) {
    if (value === null || value === undefined) {
        return '';
    }

    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

/*
|--------------------------------------------------------------------------
| Leaflet Marker Icon Fix
|--------------------------------------------------------------------------
*/

delete L.Icon.Default.prototype._getIconUrl;

L.Icon.Default.mergeOptions({
    iconRetinaUrl: new URL(
        'leaflet/dist/images/marker-icon-2x.png',
        import.meta.url
    ).href,

    iconUrl: new URL(
        'leaflet/dist/images/marker-icon.png',
        import.meta.url
    ).href,

    shadowUrl: new URL(
        'leaflet/dist/images/marker-shadow.png',
        import.meta.url
    ).href,
});

/*
|--------------------------------------------------------------------------
| Initialize Global Map
|--------------------------------------------------------------------------
*/

function initializeGlobalMap() {
    const mapElement = document.getElementById(
        'global-port-map'
    );

    if (!mapElement) {
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Reset Map Jika Element Berubah
    |--------------------------------------------------------------------------
    */

    if (
        globalMap &&
        globalMap.getContainer() !== mapElement
    ) {
        globalMap.remove();

        globalMap = null;
        markerCluster = null;
    }

    /*
    |--------------------------------------------------------------------------
    | Jangan Initialize Dua Kali
    |--------------------------------------------------------------------------
    */

    if (globalMap) {
        setTimeout(() => {
            globalMap.invalidateSize();
        }, 200);

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Create Map
    |--------------------------------------------------------------------------
    */

    globalMap = L.map(mapElement, {
        center: [20, 10],

        zoom: 2,

        minZoom: 2,

        maxZoom: 18,

        worldCopyJump: true,

        zoomControl: true,

        preferCanvas: true,
    });

    /*
    |--------------------------------------------------------------------------
    | Tile Layer
    |--------------------------------------------------------------------------
    */

    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            maxZoom: 19,

            attribution:
                '&copy; OpenStreetMap contributors',
        }
    ).addTo(globalMap);

    /*
    |--------------------------------------------------------------------------
    | Marker Cluster
    |--------------------------------------------------------------------------
    */

    markerCluster = L.markerClusterGroup({
        chunkedLoading: true,

        chunkInterval: 200,

        chunkDelay: 50,

        maxClusterRadius: 60,

        showCoverageOnHover: false,

        spiderfyOnMaxZoom: true,

        removeOutsideVisibleBounds: true,

        animate: true,

        animateAddingMarkers: false,
    });

    globalMap.addLayer(markerCluster);

    /*
    |--------------------------------------------------------------------------
    | Fix Map Size
    |--------------------------------------------------------------------------
    */

    setTimeout(() => {
        globalMap.invalidateSize();
    }, 300);
}

/*
|--------------------------------------------------------------------------
| Update Port Markers
|--------------------------------------------------------------------------
*/

function updatePortMarkers(geoJson) {
    initializeGlobalMap();

    if (!globalMap || !markerCluster) {
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Clear Old Markers
    |--------------------------------------------------------------------------
    */

    markerCluster.clearLayers();

    /*
    |--------------------------------------------------------------------------
    | Validate GeoJSON
    |--------------------------------------------------------------------------
    */

    if (
        !geoJson ||
        !Array.isArray(geoJson.features)
    ) {
        console.warn(
            'Invalid GeoJSON:',
            geoJson
        );

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Markers
    |--------------------------------------------------------------------------
    */

    geoJson.features.forEach((feature) => {
        const geometry = feature.geometry;

        if (
            !geometry ||
            geometry.type !== 'Point' ||
            !Array.isArray(geometry.coordinates)
        ) {
            return;
        }

        const longitude = Number(
            geometry.coordinates[0]
        );

        const latitude = Number(
            geometry.coordinates[1]
        );

        if (
            !Number.isFinite(latitude) ||
            !Number.isFinite(longitude)
        ) {
            return;
        }

        const properties =
            feature.properties ?? {};

        /*
        |--------------------------------------------------------------------------
        | Country Data
        |--------------------------------------------------------------------------
        */

        const countryName =
            typeof properties.country === 'string'
                ? properties.country
                : properties.country?.name ??
                  properties.country_name ??
                  'Unknown';

        const countryDetailUrl =
            properties.country_detail_url ?? null;

        /*
        |--------------------------------------------------------------------------
        | Port Data
        |--------------------------------------------------------------------------
        */

        const portName =
            properties.name ?? 'Unknown Port';

        const unlocode =
            properties.unlocode ?? '-';

        const status =
            properties.status ?? 'unknown';

        const riskScore = Number(
            properties.risk_score ?? 0
        );

        /*
        |--------------------------------------------------------------------------
        | Marker
        |--------------------------------------------------------------------------
        */

        const marker = L.marker([
            latitude,
            longitude,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Popup
        |--------------------------------------------------------------------------
        */

        marker.bindPopup(`
            <div
                class="port-popup"
                style="
                    min-width: 230px;
                    padding: 2px;
                "
            >

                <strong
                    style="
                        display: block;
                        margin-bottom: 8px;
                        font-size: 15px;
                    "
                >
                    ${escapeHtml(portName)}
                </strong>

                <hr
                    style="
                        margin: 8px 0;
                        border: 0;
                        border-top: 1px solid #cbd5e1;
                    "
                >

                <div style="margin-bottom: 5px;">
                    <b>Country:</b>

                    ${escapeHtml(countryName)}
                </div>

                <div style="margin-bottom: 5px;">
                    <b>UN/LOCODE:</b>

                    ${escapeHtml(unlocode)}
                </div>

                <div style="margin-bottom: 5px;">
                    <b>Status:</b>

                    ${escapeHtml(status)}
                </div>

                <div style="margin-bottom: 5px;">
                    <b>Risk Score:</b>

                    ${escapeHtml(
                        riskScore.toFixed(2)
                    )}
                </div>

                ${
                    countryDetailUrl
                        ? `
                            <div
                                style="
                                    margin-top: 12px;
                                    padding-top: 10px;
                                    border-top: 1px solid #cbd5e1;
                                "
                            >
                                <a
                                    href="${escapeHtml(
                                        countryDetailUrl
                                    )}"
                                    style="
                                        display: block;
                                        width: 100%;
                                        box-sizing: border-box;
                                        padding: 9px 12px;
                                        border-radius: 6px;
                                        background: #059669;
                                        color: white;
                                        text-align: center;
                                        text-decoration: none;
                                        font-weight: 600;
                                    "
                                >
                                    View Intelligence
                                </a>
                            </div>
                        `
                        : ''
                }

            </div>
        `);

        /*
        |--------------------------------------------------------------------------
        | Add Marker
        |--------------------------------------------------------------------------
        */

        markerCluster.addLayer(marker);
    });

    /*
    |--------------------------------------------------------------------------
    | Resize Map
    |--------------------------------------------------------------------------
    */

    setTimeout(() => {
        globalMap.invalidateSize();
    }, 200);
}

/*
|--------------------------------------------------------------------------
| Initialize Risk Chart
|--------------------------------------------------------------------------
*/

function initializeRiskChart(riskData = []) {
    const chartElement = document.getElementById(
        'global-risk-chart'
    );

    if (!chartElement) {
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy Old Chart
    |--------------------------------------------------------------------------
    */

    if (riskChart) {
        riskChart.destroy();

        riskChart = null;
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Data
    |--------------------------------------------------------------------------
    */

    if (!Array.isArray(riskData)) {
        riskData = [];
    }

    /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    */

    const countries = riskData.map(
        (item) => item.country ?? 'Unknown'
    );

    /*
    |--------------------------------------------------------------------------
    | Series
    |--------------------------------------------------------------------------
    */

    const totalScores = riskData.map(
        (item) => Number(
            item.total_score ?? 0
        )
    );

    const weatherScores = riskData.map(
        (item) => Number(
            item.weather_score ?? 0
        )
    );

    const inflationScores = riskData.map(
        (item) => Number(
            item.inflation_score ?? 0
        )
    );

    const currencyScores = riskData.map(
        (item) => Number(
            item.currency_score ?? 0
        )
    );

    const politicalScores = riskData.map(
        (item) => Number(
            item.political_score ?? 0
        )
    );

    const portScores = riskData.map(
        (item) => Number(
            item.port_score ?? 0
        )
    );

    /*
    |--------------------------------------------------------------------------
    | Chart Options
    |--------------------------------------------------------------------------
    */

    const options = {
        chart: {
            type: 'bar',

            height: 420,

            background: 'transparent',

            toolbar: {
                show: false,
            },

            animations: {
                enabled: true,

                speed: 500,
            },
        },

        series: [
            {
                name: 'Total Risk',
                data: totalScores,
            },

            {
                name: 'Weather',
                data: weatherScores,
            },

            {
                name: 'Inflation',
                data: inflationScores,
            },

            {
                name: 'Currency',
                data: currencyScores,
            },

            {
                name: 'Political',
                data: politicalScores,
            },

            {
                name: 'Port',
                data: portScores,
            },
        ],

        plotOptions: {
            bar: {
                borderRadius: 4,

                columnWidth: '60%',
            },
        },

        dataLabels: {
            enabled: false,
        },

        xaxis: {
            categories: countries,

            labels: {
                rotate: -45,

                style: {
                    colors: '#94a3b8',
                },
            },
        },

        yaxis: {
            min: 0,

            max: 100,

            tickAmount: 5,

            labels: {
                style: {
                    colors: '#94a3b8',
                },
            },
        },

        legend: {
            position: 'top',

            labels: {
                colors: '#cbd5e1',
            },
        },

        grid: {
            borderColor: '#1e293b',
        },

        tooltip: {
            theme: 'dark',
        },

        theme: {
            mode: 'dark',
        },
    };

    /*
    |--------------------------------------------------------------------------
    | Render Chart
    |--------------------------------------------------------------------------
    */

    riskChart = new ApexCharts(
        chartElement,
        options
    );

    riskChart.render();
}

/*
|--------------------------------------------------------------------------
| Initialize Comparison Chart
|--------------------------------------------------------------------------
*/

function initializeComparisonChart(
    comparisonData = []
) {
    const chartElement = document.getElementById(
        'country-comparison-chart'
    );

    if (!chartElement) {
        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy Old Chart
    |--------------------------------------------------------------------------
    */

    if (comparisonChart) {
        comparisonChart.destroy();

        comparisonChart = null;
    }

    /*
    |--------------------------------------------------------------------------
    | Empty Data
    |--------------------------------------------------------------------------
    */

    if (
        !Array.isArray(comparisonData) ||
        comparisonData.length === 0
    ) {
        chartElement.innerHTML = '';

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Chart Options
    |--------------------------------------------------------------------------
    */

    const options = {
        chart: {
            type: 'radar',

            height: 450,

            background: 'transparent',

            toolbar: {
                show: false,
            },

            animations: {
                enabled: true,

                speed: 500,
            },
        },

        series: comparisonData.map(
            (country) => ({
                name:
                    country.name ?? 'Unknown',

                data: [
                    Number(
                        country.risk_score ?? 0
                    ),

                    Number(
                        country.weather_score ?? 0
                    ),

                    Number(
                        country.inflation_score ?? 0
                    ),

                    Number(
                        country.currency_score ?? 0
                    ),

                    Number(
                        country.political_score ?? 0
                    ),

                    Number(
                        country.port_score ?? 0
                    ),
                ],
            })
        ),

        xaxis: {
            categories: [
                'Total Risk',
                'Weather',
                'Inflation',
                'Currency',
                'Political',
                'Port',
            ],

            labels: {
                style: {
                    colors: [
                        '#94a3b8',
                        '#94a3b8',
                        '#94a3b8',
                        '#94a3b8',
                        '#94a3b8',
                        '#94a3b8',
                    ],
                },
            },
        },

        yaxis: {
            min: 0,

            max: 100,

            tickAmount: 5,

            labels: {
                style: {
                    colors: '#64748b',
                },
            },
        },

        stroke: {
            width: 2,
        },

        fill: {
            opacity: 0.15,
        },

        markers: {
            size: 4,
        },

        legend: {
            position: 'top',

            labels: {
                colors: '#cbd5e1',
            },
        },

        tooltip: {
            theme: 'dark',
        },

        theme: {
            mode: 'dark',
        },
    };

    /*
    |--------------------------------------------------------------------------
    | Render Chart
    |--------------------------------------------------------------------------
    */

    comparisonChart = new ApexCharts(
        chartElement,
        options
    );

    comparisonChart.render();
}

/*
|--------------------------------------------------------------------------
| Load Initial Ports
|--------------------------------------------------------------------------
*/

async function loadInitialPorts() {
    try {
        const response = await fetch(
            '/api/v1/ports/geojson',
            {
                headers: {
                    Accept: 'application/json',
                },
            }
        );

        if (!response.ok) {
            throw new Error(
                `Port API Error: ${response.status}`
            );
        }

        const responseData =
            await response.json();

        const geoJson =
            responseData.data ??
            responseData;

        updatePortMarkers(geoJson);
    } catch (error) {
        console.error(
            'Failed loading initial ports:',
            error
        );
    }
}

/*
|--------------------------------------------------------------------------
| Load Initial Risk
|--------------------------------------------------------------------------
*/

async function loadInitialRisk() {
    try {
        const response = await fetch(
            '/api/v1/risk/latest',
            {
                headers: {
                    Accept: 'application/json',
                },
            }
        );

        if (!response.ok) {
            throw new Error(
                `Risk API Error: ${response.status}`
            );
        }

        const responseData =
            await response.json();

        const apiData =
            responseData.data ?? [];

        /*
        |--------------------------------------------------------------------------
        | Transform API Data
        |--------------------------------------------------------------------------
        */

        const riskData = apiData.map(
            (item) => ({
                country:
                    item.country?.name ??
                    'Unknown',

                iso2:
                    item.country?.iso2 ??
                    '',

                iso3:
                    item.country?.iso3 ??
                    '',

                total_score:
                    Number(
                        item.risk?.total_score ?? 0
                    ),

                weather_score:
                    Number(
                        item.risk?.weather_score ?? 0
                    ),

                inflation_score:
                    Number(
                        item.risk?.inflation_score ?? 0
                    ),

                currency_score:
                    Number(
                        item.risk?.currency_score ?? 0
                    ),

                political_score:
                    Number(
                        item.risk?.political_score ?? 0
                    ),

                port_score:
                    Number(
                        item.risk?.port_score ?? 0
                    ),

                risk_level:
                    item.risk?.risk_level ??
                    'low',
            })
        );

        initializeRiskChart(riskData);
    } catch (error) {
        console.error(
            'Failed loading initial risk:',
            error
        );
    }
}

/*
|--------------------------------------------------------------------------
| Local Country Clock
|--------------------------------------------------------------------------
*/

function updateCountryClocks() {
    const clockElements =
        document.querySelectorAll(
            '[data-country-timezone]'
        );

    clockElements.forEach((element) => {
        const timezoneName =
            element.dataset.countryTimezone;

        if (!timezoneName) {
            return;
        }

        try {
            element.textContent = dayjs()
                .tz(timezoneName)
                .format(
                    'DD MMM YYYY HH:mm:ss'
                );
        } catch (error) {
            console.error(
                'Invalid timezone:',
                timezoneName,
                error
            );

            element.textContent =
                '--:--:--';
        }
    });
}

/*
|--------------------------------------------------------------------------
| Initialize Country Clocks
|--------------------------------------------------------------------------
*/

function initializeCountryClocks() {
    if (clockInterval) {
        clearInterval(clockInterval);

        clockInterval = null;
    }

    updateCountryClocks();

    clockInterval = setInterval(
        updateCountryClocks,
        1000
    );
}

/*
|--------------------------------------------------------------------------
| Initialize Application
|--------------------------------------------------------------------------
*/

function initializeApplication() {
    initializeGlobalMap();

    loadInitialPorts();

    loadInitialRisk();

    initializeCountryClocks();
}

/*
|--------------------------------------------------------------------------
| Livewire Events
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'livewire:init',
    () => {
        /*
        |--------------------------------------------------------------------------
        | Ports Updated
        |--------------------------------------------------------------------------
        */

        Livewire.on(
            'ports-updated',
            (event) => {
                const geoJson =
                    getEventPayload(
                        event,
                        'geoJson'
                    );

                updatePortMarkers(
                    geoJson
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Risk Chart Updated
        |--------------------------------------------------------------------------
        */

        Livewire.on(
            'risk-chart-updated',
            (event) => {
                const riskData =
                    getEventPayload(
                        event,
                        'riskData'
                    );

                initializeRiskChart(
                    Array.isArray(riskData)
                        ? riskData
                        : []
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Comparison Updated
        |--------------------------------------------------------------------------
        */

        Livewire.on(
            'comparison-updated',
            (event) => {
                const comparisonData =
                    getEventPayload(
                        event,
                        'comparisonData'
                    );

                initializeComparisonChart(
                    Array.isArray(
                        comparisonData
                    )
                        ? comparisonData
                        : []
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Dashboard Updated
        |--------------------------------------------------------------------------
        */

        Livewire.on(
            'dashboard-updated',
            () => {
                updateCountryClocks();
            }
        );
    }
);

/*
|--------------------------------------------------------------------------
| DOM Loaded
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    () => {
        initializeApplication();
    }
);

/*
|--------------------------------------------------------------------------
| Livewire Navigate
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'livewire:navigated',
    () => {
        setTimeout(() => {
            initializeApplication();
        }, 200);
    }
);

/*
|--------------------------------------------------------------------------
| Window Resize
|--------------------------------------------------------------------------
*/

window.addEventListener(
    'resize',
    () => {
        if (globalMap) {
            globalMap.invalidateSize();
        }
    }
);

/*
|--------------------------------------------------------------------------
| Global Debug Access
|--------------------------------------------------------------------------
*/

window.GlobalSupplyChain = {
    initializeApplication,

    initializeGlobalMap,

    updatePortMarkers,

    initializeRiskChart,

    initializeComparisonChart,

    loadInitialPorts,

    loadInitialRisk,

    initializeCountryClocks,

    updateCountryClocks,
};