<?php

namespace App\Repositories\Concerns;

use Closure;
use Throwable;
use RuntimeException;
use Illuminate\Database\ConnectionInterface;

/**
 * Run a write made of several steps in a single transaction: the first step that fails rolls back the writes stored
 * before it, and its response is returned instead of a generic error.
 */
trait RunsTransactionStepsTrait
{
    /**
     * Run the steps of a write in a single transaction.
     *
     * @param  ConnectionInterface  $connection  The connection of the written tables.
     * @param  Closure(Closure(object): void): object  $steps  The steps; they receive the function that checks each step response and stop the transaction on the first failure, and return the success response.
     * @param  string  $failureMessage  The message of the response returned when an unexpected error stops the transaction.
     * @param  array<string, mixed>|null  $failureData  The extra fields of that response, such as its status code.
     * @return object The success response, the response of the failed step, or the generic error response.
     */
    private function runTransactionSteps(ConnectionInterface $connection, Closure $steps, string $failureMessage, ?array $failureData = null): object
    {
        $failedStepResponse = null;

        $stopOnFailedStep = function (object $stepResponse) use (&$failedStepResponse): void {
            if (! $stepResponse->get('success')) {
                $failedStepResponse = $stepResponse;

                throw new RuntimeException((string) $stepResponse->get('message'));
            }
        };

        try {
            return $connection->transaction(fn (): object => $steps($stopOnFailedStep));
        } catch (Throwable $exception) {
            return $failedStepResponse ?? createValidationErrorResponse($failureMessage, $failureData, $exception);
        }
    }
}
