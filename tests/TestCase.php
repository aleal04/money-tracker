<?php

namespace Tests;

use Carbon\CarbonImmutable;
use App\Models\Space\SpaceModel;
use Tests\Concerns\CreatesSchemaTrait;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesSchemaTrait;

    /**
     * Boot the application without the Vite manifest, so the tests do not need a frontend build.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * The id of the primary space seeded by CreatesSchemaTrait ("Salario", biweekly).
     */
    protected function primarySpaceId(): int
    {
        return SpaceModel::query()->where('IsPrimary', true)->value('IdSpace');
    }

    /**
     * The dates of a quincena of the primary space, e.g. (10, 1) -> 2026-10-01..2026-10-15.
     *
     * @param  int  $month  The month.
     * @param  int  $half  The quincena, 1 or 2.
     * @param  int  $year  The year.
     * @return array{startDate:string,endDate:string} The period dates.
     */
    protected function dates(int $month, int $half, int $year = 2026): array
    {
        $first = CarbonImmutable::create($year, $month, 1);

        return $half === 1
            ? ['startDate' => $first->toDateString(), 'endDate' => $first->day(15)->toDateString()]
            : ['startDate' => $first->day(16)->toDateString(), 'endDate' => $first->endOfMonth()->toDateString()];
    }
}
