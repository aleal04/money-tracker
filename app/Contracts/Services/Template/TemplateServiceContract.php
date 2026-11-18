<?php

namespace App\Contracts\Services\Template;

use App\DTOs\Template\TemplateStoreDto;
use App\DTOs\Template\TemplateUpdateDto;

interface TemplateServiceContract
{
    /**
     * The templates of a space (primary when none is given) in creation order.
     *
     * @param  int|null  $spaceId  The space identifier, or null for the primary space.
     * @return array<int, array<string, mixed>> The template views.
     */
    public function templateList(?int $spaceId = null): array;

    /**
     * Store a template.
     *
     * @param  TemplateStoreDto  $templateData  The template fields.
     * @return object The result of the store operation.
     */
    public function storeTemplate(TemplateStoreDto $templateData): object;

    /**
     * Update a template.
     *
     * @param  TemplateUpdateDto  $templateData  The template identifier and changed fields.
     * @return object The result of the update operation.
     */
    public function updateTemplate(TemplateUpdateDto $templateData): object;

    /**
     * Delete a template.
     *
     * @param  int  $templateId  The template identifier.
     * @return object The result of the delete operation.
     */
    public function deleteTemplate(int $templateId): object;
}
