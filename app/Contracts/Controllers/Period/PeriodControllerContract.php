<?php

namespace App\Contracts\Controllers\Period;

use Inertia\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\Period\StorePeriodFormRequest;

interface PeriodControllerContract
{
    /**
     * Redirect to the latest period, or show the create form when there are none.
     *
     * @return Response|RedirectResponse The index page or the redirect.
     */
    public function index(): Response|RedirectResponse;

    /**
     * Display the period page.
     *
     * @param  int  $periodId  The period identifier.
     * @return Response The period page.
     */
    public function show(int $periodId): Response;

    /**
     * The period data as JSON: period, prevId, nextId, lines and summary.
     *
     * @param  int  $periodId  The period identifier.
     * @return JsonResponse The detail, or 404.
     */
    public function data(int $periodId): JsonResponse;

    /**
     * Create a period (idempotent).
     *
     * @param  StorePeriodFormRequest  $requestData  The validated period key.
     * @return JsonResponse The result of the store operation.
     */
    public function store(StorePeriodFormRequest $requestData): JsonResponse;

    /**
     * Create the period that follows the latest one.
     *
     * @return JsonResponse The result of the store operation.
     */
    public function storeNext(): JsonResponse;

    /**
     * Preview what closing the period would carry: unpaid egreso lines and their total.
     *
     * @param  int  $periodId  The period identifier.
     * @return JsonResponse The preview, or 404.
     */
    public function closePreview(int $periodId): JsonResponse;

    /**
     * Close the period, carrying its unpaid egresos into one arrastre line of the next period.
     *
     * @param  int  $periodId  The period identifier.
     * @return JsonResponse The result with arrastreLineId, or a 404/409 error.
     */
    public function close(int $periodId): JsonResponse;

    /**
     * Reopen the period, undoing its unpaid arrastre.
     *
     * @param  int  $periodId  The period identifier.
     * @return JsonResponse The result, or a 404/409 error.
     */
    public function reopen(int $periodId): JsonResponse;
}
