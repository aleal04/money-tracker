<?php

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

/**
 * Create a success response.
 *
 * @param  string  $message  The message shown to the user.
 * @param  array<string, mixed>|null  $data  Extra keys merged into the response, such as the created identifier.
 * @return object The response collection with success, message and the extra keys.
 */
if (! function_exists('createValidationSuccessResponse')) {
    function createValidationSuccessResponse(string $message, ?array $data = null): object
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response = array_merge($response, $data);
        }

        return collect($response);
    }
}

/**
 * Create an error response and write it to the log.
 *
 * @param  string  $message  The message shown to the user.
 * @param  array<string, mixed>|null  $data  Extra keys merged into the response, such as the statusCode.
 * @param  Throwable|null  $exception  The exception that caused the error, logged without the bound query values.
 * @return object The response collection with success, message, timestamp, requestId and the extra keys.
 */
if (! function_exists('createValidationErrorResponse')) {
    function createValidationErrorResponse(string $message, ?array $data = null, ?Throwable $exception = null): object
    {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => Carbon::now()->toISOString(),
            'requestId' => (string) Str::uuid(),
        ];

        if ($data !== null) {
            $response = array_merge($response, $data);
        }

        writeErrorLog($message, $response, $data, 'warning', $exception);

        return collect($response);
    }
}

/**
 * Replace the values of sensitive keys (passwords, tokens, secrets) with a placeholder, recursively.
 *
 * @param  mixed  $value  The value to sanitize; only arrays are inspected.
 * @return mixed The same value, with the sensitive array entries replaced by '[REDACTED]'.
 */
if (! function_exists('sanitizeErrorContext')) {
    function sanitizeErrorContext(mixed $value): mixed
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'current_password', 'token', 'access_token', 'refresh_token', 'id_token', 'authorization', 'cookie', 'secret', 'client_secret', 'api_key', 'apikey'];

        if (! is_array($value)) {
            return $value;
        }

        $sanitized = [];

        foreach ($value as $key => $item) {
            $normalizedKey = is_string($key) ? strtolower($key) : $key;

            if (is_string($normalizedKey) && in_array($normalizedKey, $sensitiveKeys, true)) {
                $sanitized[$key] = '[REDACTED]';

                continue;
            }

            $sanitized[$key] = is_array($item) ? sanitizeErrorContext($item) : $item;
        }

        return $sanitized;
    }
}

/**
 * Get the identifiers of the request (id, *Id, *_id and *Code fields with a short scalar value), so the
 * log can locate the record without copying the personal or medical data typed in the form.
 *
 * @param  array<string, mixed>  $input
 * @return array<string, int|string|bool>
 */
if (! function_exists('extractRequestIdentifiers')) {
    function extractRequestIdentifiers(array $input): array
    {
        $identifiers = [];

        foreach ($input as $key => $value) {
            if (! is_string($key) || ! preg_match('/^id$|Id$|_id$|Code$/', $key)) {
                continue;
            }

            if (is_int($value) || is_bool($value) || (is_string($value) && mb_strlen($value) <= 50)) {
                $identifiers[$key] = $value;
            }
        }

        return $identifiers;
    }
}

/**
 * Build the log context of an error response: request metadata, sanitized data and exception details.
 *
 * @param  string  $message  The message shown to the user.
 * @param  array{success: bool, message: string, timestamp: string, requestId: string}  $response  The error response being returned.
 * @param  array<string, mixed>|null  $data  Extra keys merged into the response.
 * @param  Throwable|null  $exception  The exception that caused the error.
 * @return array{message: string, requestId: string, timestamp: string, data: mixed, method?: string, path?: string, ip?: string|null, inputFields?: list<string>, identifiers?: array<string, int|string|bool>, userId?: mixed, exception?: array{class: string, message: string, code: int|string, file: string, line: int, sql?: string, trace?: string}} The context written to the log.
 */
if (! function_exists('buildErrorLogContext')) {
    function buildErrorLogContext(string $message, array $response, ?array $data = null, ?Throwable $exception = null): array
    {
        $logContext = [
            'message' => $message,
            'requestId' => $response['requestId'],
            'timestamp' => $response['timestamp'],
            'data' => sanitizeErrorContext($data ?? []),
        ];

        try {
            $request = request();
            $logContext['method'] = $request->method();
            $logContext['path'] = $request->path();
            $logContext['ip'] = $request->ip();
            // Only the field names and the identifiers: the values typed in the forms stay out of the log
            $logContext['inputFields'] = array_keys($request->all());
            $logContext['identifiers'] = extractRequestIdentifiers($request->all());

            if ($request->user()) {
                $logContext['userId'] = $request->user()->getAuthIdentifier();
            }
        } catch (Throwable $throwable) {
        }

        if ($exception !== null) {
            $logContext['exception'] = [
                'class' => $exception::class,
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];

            // The message of a query error includes the bound values; keep the SQL with its placeholders instead
            if ($exception instanceof QueryException) {
                $logContext['exception']['message'] = $exception->errorInfo[2] ?? 'Database query error';
                $logContext['exception']['sql'] = $exception->getSql();
                $logContext['exception']['trace'] = $exception->getTraceAsString();
            }
        }

        return $logContext;
    }
}

/**
 * Write an error response to the log, raising the level to error when an exception is given.
 *
 * @param  string  $message  The message shown to the user.
 * @param  array{success: bool, message: string, timestamp: string, requestId: string}  $response  The error response being returned.
 * @param  array<string, mixed>|null  $data  Extra keys merged into the response.
 * @param  string  $level  The log level used when there is no exception.
 * @param  Throwable|null  $exception  The exception that caused the error.
 * @return void
 */
if (! function_exists('writeErrorLog')) {
    function writeErrorLog(string $message, array $response, ?array $data = null, string $level = 'warning', ?Throwable $exception = null): void
    {
        $context = buildErrorLogContext($message, $response, $data, $exception);

        if ($exception !== null) {
            // A query exception is not attached, because the logger would write its message with the bound values
            if (! $exception instanceof QueryException) {
                $context['exception'] = $exception;
            }

            $level = 'error';
        }

        Log::log($level, 'Application error response generated', $context);
    }
}
