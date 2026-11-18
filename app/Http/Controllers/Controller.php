<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

abstract class Controller
{
    /**
     * Read an optional space identifier from the request; absent or blank is null, anything but a positive integer is a 404.
     *
     * @param  Request  $request  The request.
     * @param  string  $key  The input key, e.g. 'space' or 'spaceId'.
     * @return int|null The identifier, or null for the primary space.
     */
    protected function spaceIdInput(Request $request, string $key): ?int
    {
        $value = $request->input($key);
        if ($value === null || $value === '') {
            return null;
        }
        abort_unless(filter_var($value, FILTER_VALIDATE_INT) !== false && (int) $value > 0, 404, 'No se encontró el espacio.');

        return (int) $value;
    }

    /**
     * Turn a service response into JSON: success uses the given status, a failure uses its statusCode (500 by default).
     *
     * @param  object  $result  The response created by createValidationSuccessResponse or createValidationErrorResponse.
     * @param  int  $successStatus  The HTTP status of a successful result.
     * @return JsonResponse The JSON response.
     */
    protected function respond(object $result, int $successStatus = 200): JsonResponse
    {
        return response()->json($result, $result->get('success') ? $successStatus : (int) $result->get('statusCode', 500));
    }
}
