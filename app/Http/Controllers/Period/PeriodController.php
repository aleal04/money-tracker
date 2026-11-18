<?php

namespace App\Http\Controllers\Period;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\DTOs\Period\PeriodStoreDto;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use App\Services\Period\PeriodService;
use App\Services\Period\PeriodCloseService;
use App\Http\Requests\Period\StorePeriodFormRequest;
use App\Contracts\Controllers\Period\PeriodControllerContract;

class PeriodController extends Controller implements PeriodControllerContract
{
    /**
     * Create the period controller with its service dependency.
     *
     * @param  PeriodService  $periodService  The service for period operations.
     * @param  PeriodCloseService  $periodCloseService  The service for closing and reopening periods.
     */
    public function __construct(
        private readonly PeriodService $periodService,
        private readonly PeriodCloseService $periodCloseService,
    ) {}

    /**
     * Redirect to the latest period of the space (`?space=`, primary by default), or show the create form when there are none.
     *
     * @param  Request  $request  The request with the optional space.
     * @return Response|RedirectResponse The index page or the redirect.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $latestId = $this->periodService->latestPeriodId($request->integer('space') ?: null);

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
     * @param  StorePeriodFormRequest  $requestData  The validated space and dates.
     * @return JsonResponse The result of the store operation.
     */
    public function store(StorePeriodFormRequest $requestData): JsonResponse
    {
        return $this->respond($this->periodService->storePeriod(PeriodStoreDto::fromRequest($requestData)), 201);
    }

    /**
     * Create the period that follows the latest one of the space (`spaceId`, primary by default).
     *
     * @param  Request  $request  The request with the optional space.
     * @return JsonResponse The result of the store operation.
     */
    public function storeNext(Request $request): JsonResponse
    {
        return $this->respond($this->periodService->storeNextPeriod($request->integer('spaceId') ?: null), 201);
    }

    /**
     * Preview what closing the period would carry: unpaid egreso lines and their total.
     *
     * @param  int  $periodId  The period identifier.
     * @return JsonResponse The preview, or 404.
     */
    public function closePreview(int $periodId): JsonResponse
    {
        $preview = $this->periodCloseService->preview($periodId);

        return $preview === null ? response()->json(['message' => 'No se encontró la quincena.'], 404) : response()->json($preview);
    }

    /**
     * Close the period, carrying its unpaid egresos into one arrastre line of the next period.
     *
     * @param  int  $periodId  The period identifier.
     * @return JsonResponse The result with arrastreLineId, or a 404/409 error.
     */
    public function close(int $periodId): JsonResponse
    {
        return $this->respond($this->periodCloseService->close($periodId), 201);
    }

    /**
     * Reopen the period, undoing its unpaid arrastre.
     *
     * @param  int  $periodId  The period identifier.
     * @return JsonResponse The result, or a 404/409 error.
     */
    public function reopen(int $periodId): JsonResponse
    {
        return $this->respond($this->periodCloseService->reopen($periodId));
    }
}
