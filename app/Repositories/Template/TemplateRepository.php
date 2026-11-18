<?php

namespace App\Repositories\Template;

use Closure;
use App\Models\Period\LineModel;
use Illuminate\Support\Facades\DB;
use App\Models\Template\TemplateModel;
use App\Repositories\Concerns\RunsTransactionStepsTrait;

class TemplateRepository
{
    use RunsTransactionStepsTrait;

    /**
     * Run the steps of a template write in a single transaction.
     *
     * @param  Closure  $steps  The steps; they return the success or the failed response.
     * @param  string  $failureMessage  The message when an unexpected error stops the transaction.
     * @return object The steps response or the generic error.
     */
    public function transaction(Closure $steps, string $failureMessage): object
    {
        return $this->runTransactionSteps(DB::connection(), $steps, $failureMessage, ['statusCode' => 500]);
    }

    /**
     * Insert a template; to run inside a transaction.
     *
     * @param  array<string, mixed>  $attributes  The template columns.
     * @return TemplateModel The created template.
     */
    public function insert(array $attributes): TemplateModel
    {
        return TemplateModel::query()->create($attributes);
    }

    /**
     * Update a template; to run inside a transaction.
     *
     * @param  TemplateModel  $template  The locked template.
     * @param  array<string, mixed>  $changes  The changed columns.
     */
    public function update(TemplateModel $template, array $changes): void
    {
        $template->update($changes);
    }

    /**
     * Logically delete a template and detach its lines (as the FK's ON DELETE SET NULL did); to run inside a transaction.
     *
     * @param  TemplateModel  $template  The locked template.
     */
    public function delete(TemplateModel $template): void
    {
        LineModel::query()->where('FkTemplate', $template->IdTemplate)->update(['FkTemplate' => null]);
        $template->logicalDelete();
    }
}
