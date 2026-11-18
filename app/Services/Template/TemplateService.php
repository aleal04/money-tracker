<?php

namespace App\Services\Template;

use App\Helpers\MoneyHelper;
use App\Models\Template\TemplateModel;
use App\DTOs\Template\TemplateStoreDto;
use App\DTOs\Template\TemplateUpdateDto;
use App\Constant\TemplateFrequencyConstant;
use App\Repositories\Space\SpaceQueryRepository;
use App\Repositories\Template\TemplateRepository;
use App\Repositories\Template\TemplateQueryRepository;
use App\Contracts\Services\Template\TemplateServiceContract;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TemplateService implements TemplateServiceContract
{
    /**
     * Create the template service with its dependencies.
     *
     * @param  TemplateRepository  $templateRepository  The template writes.
     * @param  TemplateQueryRepository  $templateQueryRepository  The template reads.
     * @param  SpaceQueryRepository  $spaceQueryRepository  The space reads.
     */
    public function __construct(
        private readonly TemplateRepository $templateRepository,
        private readonly TemplateQueryRepository $templateQueryRepository,
        private readonly SpaceQueryRepository $spaceQueryRepository,
    ) {}

    /**
     * The templates of a space (primary when none is given) in creation order.
     *
     * @param  int|null  $spaceId  The space identifier, or null for the primary space.
     * @return array<int, array<string, mixed>> The template views; a missing space is a 404.
     */
    public function templateList(?int $spaceId = null): array
    {
        $space = $this->spaceQueryRepository->resolve($spaceId) ?? throw new NotFoundHttpException('No se encontró el espacio.');

        return $this->templateQueryRepository->listTemplates($space->IdSpace)->map(fn (TemplateModel $template) => $this->toView($template))->all();
    }

    /**
     * Store a template in its space (primary when none is given).
     *
     * @param  TemplateStoreDto  $templateData  The template fields.
     * @return object The result of the store operation, or a 404 error when the space is not found.
     */
    public function storeTemplate(TemplateStoreDto $templateData): object
    {
        return $this->templateRepository->transaction(function () use ($templateData): object {
            $space = $this->spaceQueryRepository->resolve($templateData->spaceId);
            if ($space === null) {
                return createValidationErrorResponse('No se encontró el espacio.', ['statusCode' => 404]);
            }
            $template = $this->templateRepository->insert($templateData->toArray() + ['FkSpace' => $space->IdSpace]);

            return createValidationSuccessResponse('Plantilla creada correctamente.', ['template' => $this->toView($template->fresh())]);
        }, 'No se pudo crear la plantilla.');
    }

    /**
     * Update a template; the day of the month is cleared when the resulting frequency is not mensual_dia.
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
            if (($changes['Frequency'] ?? $template->Frequency) !== TemplateFrequencyConstant::MENSUAL_DIA) {
                $changes['DayOfMonth'] = null;
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
            'spaceId' => $template->FkSpace,
            'name' => $template->Name,
            'category' => $template->Category,
            'estimated' => MoneyHelper::requireCents($template->Estimated),
            'frequency' => $template->Frequency,
            'dayOfMonth' => $template->DayOfMonth,
            'active' => $template->Active,
        ];
    }
}
