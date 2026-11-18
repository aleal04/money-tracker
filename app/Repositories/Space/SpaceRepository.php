<?php

namespace App\Repositories\Space;

use Closure;
use App\Models\Space\SpaceModel;
use Illuminate\Support\Facades\DB;
use App\Repositories\Concerns\RunsTransactionStepsTrait;

class SpaceRepository
{
    use RunsTransactionStepsTrait;

    /**
     * Run the steps of a space write in a single transaction.
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
     * Insert a space; to run inside a transaction.
     *
     * @param  array<string, mixed>  $attributes  The space columns.
     * @return SpaceModel The created space.
     */
    public function insert(array $attributes): SpaceModel
    {
        return SpaceModel::query()->create($attributes);
    }

    /**
     * Update a space; to run inside a transaction.
     *
     * @param  SpaceModel  $space  The locked space.
     * @param  array<string, mixed>  $changes  The changed columns.
     */
    public function update(SpaceModel $space, array $changes): void
    {
        $space->update($changes);
    }

    /**
     * Make the space the only primary one: the other live spaces are demoted first (the unique index allows one);
     * to run inside a transaction.
     *
     * @param  SpaceModel  $space  The locked space.
     */
    public function makePrimary(SpaceModel $space): void
    {
        SpaceModel::query()->where('IdSpace', '<>', $space->IdSpace)->where('IsPrimary', true)->update(['IsPrimary' => false]);
        $space->update(['IsPrimary' => true]);
    }

    /**
     * Logically delete a space; to run inside a transaction.
     *
     * @param  SpaceModel  $space  The locked space.
     */
    public function delete(SpaceModel $space): void
    {
        $space->logicalDelete();
    }
}
