<?php

namespace App\Http\Controllers\Period;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;
use App\DTOs\Period\PeriodStoreDto;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use App\Services\Period\PeriodService;
use App\Http\Requests\Period\StorePeriodFormRequest;
use App\Contracts\Controllers\Period\PeriodControllerContract;

class PeriodController extends Controller implements PeriodControllerContract
{
    /**
     * Create the period controller with its service dependency.
     *
     * @param  PeriodService  $periodService  The service for period operations.
     */
    public function __construct(private readonly PeriodService $periodService) {}

    /**
     * Redirect to the latest period, or show the create form when there are none.
     *
     * @return Response|RedirectResponse The index page or the redirect.
     */
    public function index(): Response|RedirectResponse
    {
        $latestId = $this->periodService->latestPeriodId();

        return $latestId === null ? Inertia::render('Period/Index') : redirect()->route('periods.show', $latestId);
    }

    /**
     * Display the period page.
     *
     * @param  int  $periodId  The period identifier.
     * @return Response The period page.
     */
    public function show(int $periodId): Response
    {
        abort_unless($this->periodService->exists($periodId), 404);

        return Inertia::render('Period/Show', ['periodId' => $periodId]);
    }

    /**
     * The period data as JSON: period, prevId, nextId, lines and summary.
     *
     * @param  int  $periodId  The period identifier.
     * @return JsonResponse The detail, or 404.
     */
    public function data(int $periodId): JsonResponse
    {
        $detail = $this->periodService->detail($periodId);

        return $detail === null ? response()->json(['message' => 'No se encontró la quincena.'], 404) : response()->json($detail);
    }

    /**
     * Create a period (idempotent).
     *
     * @param  StorePeriodFormRequest  $requestData  The validated period key.
     * @return JsonResponse The result of the store operation.
     */
    public function store(StorePeriodFormRequest $requestData): JsonResponse
    {
        return $this->respond($this->periodService->storePeriod(PeriodStoreDto::fromRequest($requestData)), 201);
    }

    /**
     * Create the period that follows the latest one.
     *
     * @return JsonResponse The result of the store operation.
     */
    public function storeNext(): JsonResponse
    {
        return $this->respond($this->periodService->storeNextPeriod(), 201);
    }
}
