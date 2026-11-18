<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;
use App\Services\Dashboard\DashboardService;
use App\Contracts\Controllers\Dashboard\DashboardControllerContract;

class DashboardController extends Controller implements DashboardControllerContract
{
    /**
     * Create the dashboard controller with its service dependency.
     *
     * @param  DashboardService  $dashboardService  The service that composes the home data.
     */
    public function __construct(private readonly DashboardService $dashboardService) {}

    /**
     * Display the home page.
     *
     * @return Response The home page.
     */
    public function index(): Response
    {
        return Inertia::render('Dashboard/Index');
    }

    /**
     * The home page data as JSON.
     *
     * @return JsonResponse The latest period detail, the cards and the template count.
     */
    public function data(): JsonResponse
    {
        return response()->json($this->dashboardService->data());
    }
}
