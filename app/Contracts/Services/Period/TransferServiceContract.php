<?php

namespace App\Contracts\Services\Period;

use App\Models\Period\LineModel;

interface TransferServiceContract
{
    /**
     * The 422 response when a line or template cannot send money to the given space, or null when it can.
     *
     * @param  int|null  $targetSpaceId  The target space identifier, or null for no transfer.
     * @param  string  $category  The category of the line or template.
     * @param  int  $ownSpaceId  The space the line or template belongs to.
     * @return object|null The error response, or null when the target is allowed.
     */
    public function targetError(?int $targetSpaceId, string $category, int $ownSpaceId): ?object;

    /**
     * Validate the target of a line that was just written and make its destination line match it; to run inside the
     * transaction of the write.
     *
     * @param  LineModel  $line  The line after the write (it may be logically deleted).
     * @return object|null The 409/422 error response, or null when everything is in place.
     */
    public function sync(LineModel $line): ?object;
}
