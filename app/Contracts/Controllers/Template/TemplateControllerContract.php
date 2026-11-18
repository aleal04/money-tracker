<?php

namespace App\Contracts\Controllers\Template;

use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Template\StoreTemplateFormRequest;
use App\Http\Requests\Template\UpdateTemplateFormRequest;

interface TemplateControllerContract
{
    /**
     * Display the templates page.
     *
     * @return Response The templates page.
     */
    public function index(): Response;

    /**
     * The templates of the space (`?space=`, primary by default) as JSON.
     *
     * @param  Request  $request  The request with the optional space.
     * @return JsonResponse The template views.
     */
    public function templateList(Request $request): JsonResponse;

    /**
     * Store a template.
     *
     * @param  StoreTemplateFormRequest  $requestData  The validated template fields.
     * @return JsonResponse The result of the store operation.
     */
    public function store(StoreTemplateFormRequest $requestData): JsonResponse;

    /**
     * Update the sent fields of a template.
     *
     * @param  UpdateTemplateFormRequest  $requestData  The validated template fields.
     * @return JsonResponse The result of the update operation.
     */
    public function update(UpdateTemplateFormRequest $requestData): JsonResponse;

    /**
     * Delete a template.
     *
     * @param  int  $templateId  The template identifier.
     * @return JsonResponse The result of the delete operation.
     */
    public function destroy(int $templateId): JsonResponse;
}
