<?php

namespace App\Planning;

use App\Models\Slot;
use App\Models\Stand;
use Illuminate\Support\Collection;

final readonly class PlanningRow
{
    /**
     * @param  Collection<int, Slot>  $slots  slots of the stand that day, in time order
     * @param  list<PlanningCell>  $cells
     */
    public function __construct(
        public Stand $stand,
        public Collection $slots,
        public array $cells,
    ) {}
}
