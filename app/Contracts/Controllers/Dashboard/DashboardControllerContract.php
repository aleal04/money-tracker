<?php

namespace App\Contracts\Controllers\Dashboard;

use Inertia\Response;
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
     * The home page data as JSON.
     *
     * @return JsonResponse The latest period detail, the cards and the template count.
     */
    public function data(): JsonResponse;
}
