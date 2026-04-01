<?php

namespace App\Contracts\Reports;

use Carbon\CarbonImmutable;

interface SensorDataProvider
{
    /**
     * Build a historical dataset for the requested report period.
     *
     * @return array{
     *     source:string,
     *     period_start:string,
     *     period_end:string,
     *     sensors:array<int, array<string, mixed>>
     * }
     */
    public function forPeriod(string $type, CarbonImmutable $periodStart, CarbonImmutable $periodEnd): array;
}
