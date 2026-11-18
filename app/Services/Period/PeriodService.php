<?php

namespace App\Services\Period;

use Closure;
use App\Helpers\MoneyHelper;
use App\Helpers\PeriodKeyHelper;
use App\Models\Period\LineModel;
use App\DTOs\Period\PeriodStoreDto;
use App\Constant\LineSourceConstant;
use App\Repositories\Period\LineRepository;
use App\Services\Template\TemplateExpander;
use App\Repositories\Period\PeriodRepository;
use App\Repositories\Period\LineQueryRepository;
use App\Repositories\Period\PeriodQueryRepository;
use App\Contracts\Services\Period\PeriodServiceContract;

class PeriodService implements PeriodServiceContract
{
    /**
     * Create the period service with its dependencies.
     *
     * @param  PeriodRepository  $periodRepository  The period writes and transactions.
     * @param  PeriodQueryRepository  $periodQueryRepository  The period reads.
     * @param  LineRepository  $lineRepository  The line writes.
     * @param  LineQueryRepository  $lineQueryRepository  The line reads.
     * @param  CuotasSyncService  $cuotasSyncService  The cuotas lines sync.
     */
    public function __construct(
        private readonly PeriodRepository $periodRepository,
        private readonly PeriodQueryRepository $periodQueryRepository,
        private readonly LineRepository $lineRepository,
        private readonly LineQueryRepository $lineQueryRepository,
        private readonly CuotasSyncService $cuotasSyncService,
    ) {}

    /**
     * Create the period of the given key in one transaction (idempotent).
     *
     * @param  PeriodStoreDto  $periodData  The period key.
     * @return object The response with the period view.
     */
    public function storePeriod(PeriodStoreDto $periodData): object
    {
        return $this->periodRepository->transaction(function (Closure $stop) use ($periodData): object {
            $result = $this->createWithinTransaction($periodData->toArray());
            $stop($result);

            return $result;
        }, 'No se pudo crear la quincena.');
    }

    /**
     * Create the period that follows the latest one, in one transaction (idempotent under concurrent calls).
     *
     * @return object The response with the period view, or a 409 error when there are no periods.
     */
    public function storeNextPeriod(): object
    {
        return $this->periodRepository->transaction(function (Closure $stop): object {
            $latest = $this->periodQueryRepository->latest(true);
            if ($latest === null) {
                $result = createValidationErrorResponse('No hay quincenas para continuar.', ['statusCode' => 409]);
            } else {
                $result = $this->createWithinTransaction(PeriodKeyHelper::next($latest->Year, $latest->Month, $latest->Half));
            }
            $stop($result);

            return $result;
        }, 'No se pudo crear la quincena.');
    }

    /**
     * Create the period without opening a transaction, so it can run inside an outer one (e.g. closing a period).
     * An existing key returns the same period and adds nothing; a new half 1 advances the installments, then the
     * template lines and the cuotas lines are generated.
     *
     * @param  array{year:int,month:int,half:int}  $key  The period key.
     * @return object The response with the period view.
     */
    public function createWithinTransaction(array $key): object
    {
        ['period' => $period, 'created' => $created] = $this->periodRepository->insertIfAbsent($key);

        if ($created) {
            if ($key['half'] === 1) {
                $this->periodRepository->advanceInstallments(PeriodKeyHelper::prevMonthKey($key['year'], $key['month']));
            }
            $templates = $this->periodQueryRepository->listActiveTemplateInputs();
            foreach (TemplateExpander::expand($templates, $key['half']) as $line) {
                $this->lineRepository->insert([
                    'FkPeriod' => $period->IdPeriod,
                    'Category' => $line['category'],
                    'Name' => $line['name'],
                    'Estimated' => MoneyHelper::fromCents($line['estimated']),
                    'Actual' => null,
                    'Source' => LineSourceConstant::TEMPLATE,
                    'FkTemplate' => $line['templateId'],
                ]);
            }
            $this->cuotasSyncService->syncWithinTransaction($period->IdPeriod);
        }

        return createValidationSuccessResponse('Quincena lista.', ['period' => $period->toView(), 'created' => $created]);
    }

    /**
     * The id of the latest period, or null when there are none.
     *
     * @return int|null The latest period id.
     */
    public function latestPeriodId(): ?int
    {
        return $this->periodQueryRepository->latest()?->IdPeriod;
    }

    /**
     * Whether a period exists.
     *
     * @param  int  $periodId  The period identifier.
     * @return bool True when found.
     */
    public function exists(int $periodId): bool
    {
        return $this->periodQueryRepository->find($periodId) !== null;
    }

    /**
     * A period with its neighbours, lines (with the carried flag) and summary.
     *
     * @param  int  $periodId  The period identifier.
     * @return array{period: array<string, mixed>, prevId: ?int, nextId: ?int, lines: array<int, array<string, mixed>>, summary: array<string, mixed>}|null The detail, or null when not found.
     */
    public function detail(int $periodId): ?array
    {
        $period = $this->periodQueryRepository->find($periodId);
        if ($period === null) {
            return null;
        }

        $ids = $this->periodQueryRepository->listChronological()->pluck('IdPeriod')->values();
        $index = $ids->search($periodId);
        $lines = $this->lineQueryRepository->listByPeriod($periodId)->map(fn (LineModel $line) => $line->toView())->all();

        return [
            'period' => $period->toView(),
            'prevId' => $ids[$index - 1] ?? null,
            'nextId' => $ids[$index + 1] ?? null,
            'lines' => $lines,
            'summary' => PeriodSummaryCalculator::summarize($lines),
        ];
    }
}
