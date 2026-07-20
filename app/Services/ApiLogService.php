<?php

namespace App\Services;

use App\Models\ApiLog;
use Throwable;

class ApiLogService
{
    /*
    |--------------------------------------------------------------------------
    | Create API Log
    |--------------------------------------------------------------------------
    */

    public function log(
        string $service,
        string $endpoint,
        string $method = 'GET',
        ?int $statusCode = null,
        ?int $responseTimeMs = null,
        bool $success = false,
        ?string $errorMessage = null,
        array $requestData = []
    ): ApiLog {
        return ApiLog::query()->create([
            'service' => $service,

            'endpoint' => $endpoint,

            'method' => strtoupper($method),

            'status_code' => $statusCode,

            'response_time_ms' => $responseTimeMs,

            'success' => $success,

            'error_message' => $errorMessage,

            'request_data' => empty($requestData)
                ? null
                : $requestData,

            'requested_at' => now(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Success Log
    |--------------------------------------------------------------------------
    */

    public function success(
        string $service,
        string $endpoint,
        int $statusCode,
        int $responseTimeMs,
        array $requestData = [],
        string $method = 'GET'
    ): ApiLog {
        return $this->log(
            service: $service,

            endpoint: $endpoint,

            method: $method,

            statusCode: $statusCode,

            responseTimeMs: $responseTimeMs,

            success: true,

            errorMessage: null,

            requestData: $requestData,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Failure Log
    |--------------------------------------------------------------------------
    */

    public function failure(
        string $service,
        string $endpoint,
        Throwable|string $error,
        ?int $statusCode = null,
        ?int $responseTimeMs = null,
        array $requestData = [],
        string $method = 'GET'
    ): ApiLog {
        return $this->log(
            service: $service,

            endpoint: $endpoint,

            method: $method,

            statusCode: $statusCode,

            responseTimeMs: $responseTimeMs,

            success: false,

            errorMessage: $error instanceof Throwable
                ? $error->getMessage()
                : $error,

            requestData: $requestData,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Measure API Request
    |--------------------------------------------------------------------------
    |
    | Method ini dapat digunakan untuk menghitung response time request API
    | secara otomatis.
    |
    */

    public function measure(
        string $service,
        string $endpoint,
        callable $callback,
        array $requestData = [],
        string $method = 'GET'
    ): mixed {
        $startedAt = microtime(true);

        try {
            /*
             * Jalankan request API.
             */

            $response = $callback();

            /*
             * Hitung response time.
             */

            $responseTimeMs = $this->calculateResponseTime(
                $startedAt
            );

            /*
             * Ambil HTTP status code jika object response
             * memiliki method status().
             */

            $statusCode = method_exists(
                $response,
                'status'
            )
                ? (int) $response->status()
                : 200;

            /*
             * Tentukan apakah request berhasil.
             */

            $successful = method_exists(
                $response,
                'successful'
            )
                ? $response->successful()
                : true;

            /*
             * Simpan API log.
             */

            if ($successful) {
                $this->success(
                    service: $service,

                    endpoint: $endpoint,

                    statusCode: $statusCode,

                    responseTimeMs: $responseTimeMs,

                    requestData: $requestData,

                    method: $method,
                );
            } else {
                $this->failure(
                    service: $service,

                    endpoint: $endpoint,

                    error: $this->getResponseError(
                        $response
                    ),

                    statusCode: $statusCode,

                    responseTimeMs: $responseTimeMs,

                    requestData: $requestData,

                    method: $method,
                );
            }

            return $response;
        } catch (Throwable $exception) {
            /*
             * Hitung response time sampai exception terjadi.
             */

            $responseTimeMs = $this->calculateResponseTime(
                $startedAt
            );

            /*
             * Simpan failed API log.
             */

            $this->failure(
                service: $service,

                endpoint: $endpoint,

                error: $exception,

                statusCode: null,

                responseTimeMs: $responseTimeMs,

                requestData: $requestData,

                method: $method,
            );

            /*
             * Lempar kembali exception agar Service utama
             * tetap dapat menangani error.
             */

            throw $exception;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Calculate Response Time
    |--------------------------------------------------------------------------
    */

    private function calculateResponseTime(
        float $startedAt
    ): int {
        return (int) round(
            (microtime(true) - $startedAt) * 1000
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Get Response Error
    |--------------------------------------------------------------------------
    */

    private function getResponseError(
        mixed $response
    ): string {
        /*
         * Coba ambil message dari JSON response.
         */

        if (
            method_exists($response, 'json')
        ) {
            $message = $response->json('message');

            if (
                is_string($message)
                && $message !== ''
            ) {
                return $message;
            }
        }

        /*
         * Jika tidak ada message, gunakan body response.
         */

        if (
            method_exists($response, 'body')
        ) {
            $body = $response->body();

            if (
                is_string($body)
                && $body !== ''
            ) {
                /*
                 * Batasi error agar database tidak dipenuhi
                 * response yang terlalu panjang.
                 */

                return mb_substr(
                    $body,
                    0,
                    2000
                );
            }
        }

        return 'API request failed.';
    }
}