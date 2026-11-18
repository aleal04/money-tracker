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
}
