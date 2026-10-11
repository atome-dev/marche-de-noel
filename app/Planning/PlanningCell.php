<?php

namespace App\Planning;

use App\Models\Slot;

/**
 * One table cell: a slot spanning several 30 minute columns, or an empty step.
 */
final readonly class PlanningCell
{
    public function __construct(
        public string $time,
        public int $span,
        public ?Slot $slot = null,
    ) {}
}
