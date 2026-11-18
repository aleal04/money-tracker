<?php

namespace App\Repositories\Template;

use Illuminate\Support\Collection;
use App\Models\Template\TemplateModel;
use App\Repositories\BaseQueryRepository;

class TemplateQueryRepository extends BaseQueryRepository
{
    /**
     * Find a template by identifier, optionally locking its row for the current transaction.
     *
     * @param  int  $templateId  The template identifier.
     * @param  bool  $lock  Whether to lock the row (FOR UPDATE).
     * @return TemplateModel|null The template, or null when not found.
     */
    public function findTemplate(int $templateId, bool $lock = false): ?TemplateModel
    {
        $query = TemplateModel::query();

        return ($lock ? $query->lockForUpdate() : $query)->find($templateId);
    }

    /**
     * Every template in creation order.
     *
     * @return Collection<int, TemplateModel> The templates.
     */
    public function listTemplates(): Collection
    {
        return TemplateModel::query()->orderBy('id')->get();
    }
}
