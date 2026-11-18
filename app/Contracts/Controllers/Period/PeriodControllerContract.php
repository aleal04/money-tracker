<?php

namespace App\Contracts\Controllers\Period;

use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\Period\StorePeriodFormRequest;
use App\Http\Requests\Period\UpdatePeriodFormRequest;

interface PeriodControllerContract
{
    /**
     * Redirect to the latest period of the space (`?space=`, primary by default), or show the create form when there are none.
     *
     * @param  Request  $request  The request with the optional space.
     * @return Response|RedirectResponse The index page or the redirect.
     */
    public function index(Request $request): Response|RedirectResponse;

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
     * @param  StorePeriodFormRequest  $requestData  The validated space and dates.
     * @return JsonResponse The result of the store operation.
     */
    public function store(StorePeriodFormRequest $requestData): JsonResponse;

    /**
     * Create the period that follows the latest one of the space (`spaceId`, primary by default).
     *
     * @param  Request  $request  The request with the optional space.
     * @return JsonResponse The result of the store operation.
     */
    public function storeNext(Request $request): JsonResponse;

    /**
     * Change the dates of a period.
     *
     * @param  int  $periodId  The period identifier.
     * @param  UpdatePeriodFormRequest  $requestData  The validated dates.
     * @return JsonResponse The result of the update operation.
     */
    public function update(int $periodId, UpdatePeriodFormRequest $requestData): JsonResponse;

    /**
     * The dates and label the space's frequency proposes for its next period (`?space=`, primary by default).
     *
     * @param  Request  $request  The request with the optional space.
     * @return JsonResponse The proposal: startDate, endDate and label.
     */
    public function nextProposal(Request $request): JsonResponse;

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
