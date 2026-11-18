<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class Controller
{
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
