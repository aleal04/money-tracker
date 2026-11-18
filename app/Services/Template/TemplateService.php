<?php

namespace App\Services\Template;

use App\Helpers\MoneyHelper;
use App\Models\Template\TemplateModel;
use App\DTOs\Template\TemplateStoreDto;
use App\DTOs\Template\TemplateUpdateDto;
use App\Constant\TemplateFrequencyConstant;
use App\Repositories\Template\TemplateRepository;
use App\Repositories\Template\TemplateQueryRepository;
use App\Contracts\Services\Template\TemplateServiceContract;

class TemplateService implements TemplateServiceContract
{
    /**
     * Create the template service with its dependencies.
     *
     * @param  TemplateRepository  $templateRepository  The template writes.
     * @param  TemplateQueryRepository  $templateQueryRepository  The template reads.
     */
    public function __construct(
        private readonly TemplateRepository $templateRepository,
        private readonly TemplateQueryRepository $templateQueryRepository,
    ) {}

    /**
     * Every template in creation order.
     *
     * @return array<int, array<string, mixed>> The template views.
     */
    public function templateList(): array
    {
        return $this->templateQueryRepository->listTemplates()->map(fn (TemplateModel $template) => $this->toView($template))->all();
    }

    /**
     * Store a template.
     *
     * @param  TemplateStoreDto  $templateData  The template fields.
     * @return object The result of the store operation.
     */
    public function storeTemplate(TemplateStoreDto $templateData): object
    {
        return $this->templateRepository->transaction(function () use ($templateData): object {
            $template = $this->templateRepository->insert($templateData->toArray());

            return createValidationSuccessResponse('Plantilla creada correctamente.', ['template' => $this->toView($template->fresh())]);
        }, 'No se pudo crear la plantilla.');
    }

    /**
     * Update a template; half is cleared when the resulting frequency is not mensual_en_quincena.
     *
     * @param  TemplateUpdateDto  $templateData  The template identifier and changed fields.
     * @return object The result of the update operation.
     */
    public function updateTemplate(TemplateUpdateDto $templateData): object
    {
        return $this->templateRepository->transaction(function () use ($templateData): object {
            $template = $this->templateQueryRepository->findTemplate($templateData->templateId, true);
            if ($template === null) {
                return createValidationErrorResponse('No se encontró la plantilla.', ['statusCode' => 404]);
            }

            $changes = $templateData->toArray();
            if (($changes['Frequency'] ?? $template->Frequency) !== TemplateFrequencyConstant::MENSUAL_EN_QUINCENA) {
                $changes['Half'] = null;
            }
            $this->templateRepository->update($template, $changes);

            return createValidationSuccessResponse('Plantilla actualizada correctamente.', ['template' => $this->toView($template->fresh())]);
        }, 'No se pudo actualizar la plantilla.');
    }

    /**
     * Delete a template.
     *
     * @param  int  $templateId  The template identifier.
     * @return object The result of the delete operation.
     */
    public function deleteTemplate(int $templateId): object
    {
        return $this->templateRepository->transaction(function () use ($templateId): object {
            $template = $this->templateQueryRepository->findTemplate($templateId, true);
            if ($template === null) {
                return createValidationErrorResponse('No se encontró la plantilla.', ['statusCode' => 404]);
            }

            $this->templateRepository->delete($template);

            return createValidationSuccessResponse('Plantilla eliminada correctamente.');
        }, 'No se pudo eliminar la plantilla.');
    }

    /**
     * The JSON view of a template; estimated is in cents.
     *
     * @param  TemplateModel  $template  The template.
     * @return array<string, mixed> The view.
     */
    private function toView(TemplateModel $template): array
    {
        return [
            'id' => $template->IdTemplate,
            'name' => $template->Name,
            'category' => $template->Category,
            'estimated' => MoneyHelper::requireCents($template->Estimated),
            'frequency' => $template->Frequency,
            'half' => $template->Half,
            'active' => $template->Active,
        ];
    }
}
