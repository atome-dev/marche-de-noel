<?php

namespace App\Planning;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final readonly class PlanningDay
{
    /**
     * @param  Collection<int, PlanningRow>  $rows  stands open that day
     */
    public function __construct(
        public CarbonImmutable $date,
        public Collection $rows,
    ) {}
}
