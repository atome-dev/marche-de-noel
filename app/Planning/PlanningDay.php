<?php

namespace App\Planning;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final readonly class PlanningDay
{
    /**
     * @param  Collection<int, PlanningRow>  $rows  stands open that day
     * @param  bool  $isPreparation  a day before the market, used to get ready
     */
    public function __construct(
        public CarbonImmutable $date,
        public Collection $rows,
        public bool $isPreparation = false,
    ) {}
}
