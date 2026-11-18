<?php

namespace App\Http\Controllers\Template;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\DTOs\Template\TemplateStoreDto;
use App\DTOs\Template\TemplateUpdateDto;
use App\Services\Template\TemplateService;
use App\Http\Requests\Template\StoreTemplateFormRequest;
use App\Http\Requests\Template\UpdateTemplateFormRequest;
use App\Contracts\Controllers\Template\TemplateControllerContract;

class TemplateController extends Controller implements TemplateControllerContract
{
    /**
     * Create the template controller with its service dependency.
     *
     * @param  TemplateService  $templateService  The service for template operations.
     */
    public function __construct(private readonly TemplateService $templateService) {}

    /**
     * Display the templates page.
     *
     * @return Response The templates page.
     */
    public function index(): Response
    {
        return Inertia::render('Template/Index');
    }

    /**
     * The templates of the space (`?space=`, primary by default) as JSON.
     *
     * @param  Request  $request  The request with the optional space.
     * @return JsonResponse The template views.
     */
    public function templateList(Request $request): JsonResponse
    {
        return response()->json($this->templateService->templateList($this->spaceIdInput($request, 'space')));
    }

    /**
     * Store a template.
     *
     * @param  StoreTemplateFormRequest  $requestData  The validated template fields.
     * @return JsonResponse The result of the store operation.
     */
    public function store(StoreTemplateFormRequest $requestData): JsonResponse
    {
        return $this->respond($this->templateService->storeTemplate(TemplateStoreDto::fromRequest($requestData)), 201);
    }

    /**
     * Update the sent fields of a template.
     *
     * @param  UpdateTemplateFormRequest  $requestData  The validated template fields.
     * @return JsonResponse The result of the update operation.
     */
    public function update(UpdateTemplateFormRequest $requestData): JsonResponse
    {
        return $this->respond($this->templateService->updateTemplate(TemplateUpdateDto::fromRequest($requestData)));
    }

    /**
     * Delete a template.
     *
     * @param  int  $templateId  The template identifier.
     * @return JsonResponse The result of the delete operation.
     */
    public function destroy(int $templateId): JsonResponse
    {
        return $this->respond($this->templateService->deleteTemplate($templateId));
    }
}
