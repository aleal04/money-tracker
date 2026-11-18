<?php

namespace App\Contracts\Controllers\Dashboard;

use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

interface DashboardControllerContract
{
    /**
     * Display the home page.
     *
     * @return Response The home page.
     */
    public function index(): Response;

    /**
     * The home page data as JSON for the space (`?space=`, primary by default).
     *
     * @param  Request  $request  The request with the optional space.
     * @return JsonResponse The latest period detail, the cards and the template count.
     */
    public function data(Request $request): JsonResponse;
}
