<?php

namespace App\Services;

use App\Models\Country;
use App\Models\Port;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class PortService
{
    /*
    |--------------------------------------------------------------------------
    | Search Ports
    |--------------------------------------------------------------------------
    */

    public function search(
        ?string $search = null,
        ?int $countryId = null,
        int $limit = 5000
    ): Collection {
        $search = trim((string) $search);

        $limit = max(
            1,
            min($limit, 10000)
        );

        return Port::query()
            ->with([
                'country:id,name,iso2,iso3',
            ])
            ->when(
                $search !== '',
                function (Builder $query) use ($search): void {
                    $query->where(
                        function (Builder $subQuery) use ($search): void {
                            $subQuery
                                ->where(
                                    'name',
                                    'like',
                                    '%' . $search . '%'
                                )
                                ->orWhere(
                                    'city',
                                    'like',
                                    '%' . $search . '%'
                                )
                                ->orWhere(
                                    'unlocode',
                                    'like',
                                    '%' . $search . '%'
                                )
                                ->orWhere(
                                    'port_type',
                                    'like',
                                    '%' . $search . '%'
                                );
                        }
                    );
                }
            )
            ->when(
                $countryId !== null,
                function (Builder $query) use ($countryId): void {
                    $query->where(
                        'country_id',
                        $countryId
                    );
                }
            )
            ->where(
                'status',
                'active'
            )
            ->whereNotNull(
                'latitude'
            )
            ->whereNotNull(
                'longitude'
            )
            ->orderBy(
                'name'
            )
            ->limit(
                $limit
            )
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Ports As GeoJSON
    |--------------------------------------------------------------------------
    */

    public function getGeoJson(
        ?string $search = null,
        ?int $countryId = null
    ): array {
        $ports = $this->search(
            search: $search,
            countryId: $countryId
        );

        $features = $ports
            ->filter(
                fn (Port $port): bool =>
                    $this->hasValidCoordinates($port)
            )
            ->map(
                function (Port $port): array {
                    return [
                        'type' => 'Feature',

                        'geometry' => [
                            'type' => 'Point',

                            'coordinates' => [
                                (float) $port->longitude,
                                (float) $port->latitude,
                            ],
                        ],

                        'properties' => [
                            'id' =>
                                $port->id,

                            'name' =>
                                $port->name,

                            'unlocode' =>
                                $port->unlocode,

                            'city' =>
                                $port->city,

                            'port_type' =>
                                $port->port_type,

                            'status' =>
                                $port->status,

                            'congestion_level' =>
                                (int) $port->congestion_level,

                            'risk_score' =>
                                (float) $port->risk_score,

                            'last_synced_at' =>
                                $port->last_synced_at
                                    ?->toISOString(),

                            'country' => [
                                'id' =>
                                    $port->country?->id,

                                'name' =>
                                    $port->country?->name,

                                'iso2' =>
                                    $port->country?->iso2,

                                'iso3' =>
                                    $port->country?->iso3,
                            ],
                        ],
                    ];
                }
            )
            ->values()
            ->all();

        return [
            'type' => 'FeatureCollection',

            'features' => $features,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Port Statistics
    |--------------------------------------------------------------------------
    */

    public function getStatistics(): array
    {
        $total = Port::query()
            ->count();

        $active = Port::query()
            ->where(
                'status',
                'active'
            )
            ->count();

        $inactive = Port::query()
            ->where(
                'status',
                '!=',
                'active'
            )
            ->count();

        $highCongestion = Port::query()
            ->where(
                'status',
                'active'
            )
            ->where(
                'congestion_level',
                '>=',
                70
            )
            ->count();

        $highRisk = Port::query()
            ->where(
                'status',
                'active'
            )
            ->where(
                'risk_score',
                '>=',
                70
            )
            ->count();

        $averageRisk = Port::query()
            ->where(
                'status',
                'active'
            )
            ->avg(
                'risk_score'
            );

        $averageCongestion = Port::query()
            ->where(
                'status',
                'active'
            )
            ->avg(
                'congestion_level'
            );

        $countries = Port::query()
            ->where(
                'status',
                'active'
            )
            ->distinct()
            ->count(
                'country_id'
            );

        return [
            'total' =>
                $total,

            'active' =>
                $active,

            'inactive' =>
                $inactive,

            'countries' =>
                $countries,

            'high_congestion' =>
                $highCongestion,

            'high_risk' =>
                $highRisk,

            'average_risk' =>
                round(
                    (float) ($averageRisk ?? 0),
                    2
                ),

            'average_congestion' =>
                round(
                    (float) ($averageCongestion ?? 0),
                    2
                ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Import Ports From JSON
    |--------------------------------------------------------------------------
    */

    public function importFromJson(
        string $disk,
        string $path
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Validate Dataset
        |--------------------------------------------------------------------------
        */

        if (
            ! Storage::disk($disk)
                ->exists($path)
        ) {
            throw new RuntimeException(
                "Port dataset not found: {$path}"
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Read JSON Dataset
        |--------------------------------------------------------------------------
        */

        try {
            $json = Storage::disk($disk)
                ->get($path);

            $rows = json_decode(
                $json,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (Throwable $exception) {
            throw new RuntimeException(
                'Port dataset contains invalid JSON: '
                . $exception->getMessage(),
                previous: $exception
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Dataset Structure
        |--------------------------------------------------------------------------
        */

        if (! is_array($rows)) {
            throw new RuntimeException(
                'Port dataset must contain a JSON array.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Import Statistics
        |--------------------------------------------------------------------------
        */

        $results = [
            'total' => count($rows),

            'created' => 0,

            'updated' => 0,

            'skipped' => 0,

            'failed' => 0,
        ];

        /*
        |--------------------------------------------------------------------------
        | Database Transaction
        |--------------------------------------------------------------------------
        */

        DB::transaction(
            function () use (
                $rows,
                &$results
            ): void {
                foreach ($rows as $row) {
                    /*
                    |--------------------------------------------------------------------------
                    | Validate Row Structure
                    |--------------------------------------------------------------------------
                    */

                    if (! is_array($row)) {
                        $results['skipped']++;

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Normalize Dataset Values
                    |--------------------------------------------------------------------------
                    */

                    $iso3 = strtoupper(
                        trim(
                            (string) (
                                $row['iso3']
                                ?? ''
                            )
                        )
                    );

                    $name = trim(
                        (string) (
                            $row['name']
                            ?? ''
                        )
                    );

                    $unlocode = strtoupper(
                        trim(
                            (string) (
                                $row['unlocode']
                                ?? ''
                            )
                        )
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Validate Required Fields
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $iso3 === ''
                        || $name === ''
                        || ! array_key_exists(
                            'latitude',
                            $row
                        )
                        || ! array_key_exists(
                            'longitude',
                            $row
                        )
                        || ! is_numeric(
                            $row['latitude']
                        )
                        || ! is_numeric(
                            $row['longitude']
                        )
                    ) {
                        $results['skipped']++;

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Find Country
                    |--------------------------------------------------------------------------
                    */

                    $country = Country::query()
                        ->where(
                            'iso3',
                            $iso3
                        )
                        ->first();

                    if (! $country) {
                        $results['skipped']++;

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Normalize Coordinates
                    |--------------------------------------------------------------------------
                    */

                    $latitude =
                        (float) $row['latitude'];

                    $longitude =
                        (float) $row['longitude'];

                    /*
                    |--------------------------------------------------------------------------
                    | Validate Coordinates
                    |--------------------------------------------------------------------------
                    */

                    if (
                        ! $this->coordinatesAreValid(
                            $latitude,
                            $longitude
                        )
                    ) {
                        $results['skipped']++;

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Find Existing Port By UN/LOCODE
                    |--------------------------------------------------------------------------
                    */

                    $port = null;

                    if ($unlocode !== '') {
                        $port = Port::query()
                            ->where(
                                'unlocode',
                                $unlocode
                            )
                            ->first();
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Find Existing Port Without UN/LOCODE
                    |--------------------------------------------------------------------------
                    */

                    if (! $port) {
                        $port = Port::query()
                            ->where(
                                'country_id',
                                $country->id
                            )
                            ->where(
                                'name',
                                $name
                            )
                            ->where(
                                'latitude',
                                $latitude
                            )
                            ->where(
                                'longitude',
                                $longitude
                            )
                            ->first();
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Normalize Port Status
                    |--------------------------------------------------------------------------
                    */

                    $status = strtolower(
                        trim(
                            (string) (
                                $row['status']
                                ?? 'active'
                            )
                        )
                    );

                    if ($status === '') {
                        $status = 'active';
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Normalize Port Type
                    |--------------------------------------------------------------------------
                    */

                    $portType = trim(
                        (string) (
                            $row['port_type']
                            ?? 'commercial'
                        )
                    );

                    if ($portType === '') {
                        $portType = 'commercial';
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Normalize Congestion Level
                    |--------------------------------------------------------------------------
                    */

                    $congestionLevel = min(
                        100,
                        max(
                            0,
                            (int) (
                                $row['congestion_level']
                                ?? 0
                            )
                        )
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Normalize Risk Score
                    |--------------------------------------------------------------------------
                    */

                    $riskScore = min(
                        100,
                        max(
                            0,
                            (float) (
                                $row['risk_score']
                                ?? 0
                            )
                        )
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Normalize Metadata
                    |--------------------------------------------------------------------------
                    */

                    $metadata = is_array(
                        $row['metadata']
                        ?? null
                    )
                        ? $row['metadata']
                        : [];

                    $metadata = array_merge(
                        $metadata,
                        [
                            'source' =>
                                'json-import',
                        ]
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Prepare Payload
                    |--------------------------------------------------------------------------
                    */

                    $payload = [
                        'country_id' =>
                            $country->id,

                        'name' =>
                            $name,

                        'unlocode' =>
                            $unlocode !== ''
                                ? $unlocode
                                : null,

                        'city' =>
                            isset($row['city'])
                                ? trim(
                                    (string) $row['city']
                                )
                                : null,

                        'port_type' =>
                            $portType,

                        'latitude' =>
                            $latitude,

                        'longitude' =>
                            $longitude,

                        'status' =>
                            $status,

                        'congestion_level' =>
                            $congestionLevel,

                        'risk_score' =>
                            $riskScore,

                        'metadata' =>
                            $metadata,

                        'last_synced_at' =>
                            now(),
                    ];

                    /*
                    |--------------------------------------------------------------------------
                    | Create Or Update Port
                    |--------------------------------------------------------------------------
                    */

                    if ($port) {
                        $port->update(
                            $payload
                        );

                        $results['updated']++;
                    } else {
                        Port::query()
                            ->create(
                                $payload
                            );

                        $results['created']++;
                    }
                }
            }
        );

        return $results;
    }

    /*
    |--------------------------------------------------------------------------
    | Check Port Coordinates
    |--------------------------------------------------------------------------
    */

    private function hasValidCoordinates(
        Port $port
    ): bool {
        if (
            $port->latitude === null
            || $port->longitude === null
        ) {
            return false;
        }

        return $this->coordinatesAreValid(
            (float) $port->latitude,
            (float) $port->longitude
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Latitude And Longitude
    |--------------------------------------------------------------------------
    */

    private function coordinatesAreValid(
        float $latitude,
        float $longitude
    ): bool {
        return $latitude >= -90
            && $latitude <= 90
            && $longitude >= -180
            && $longitude <= 180;
    }
}