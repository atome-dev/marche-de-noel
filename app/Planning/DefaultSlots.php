<?php

namespace App\Planning;

use App\Models\Slot;
use App\Models\Stand;

/**
 * Every stand starts with the same slots (PLANNING_DEFAULT_SLOTS), on each
 * market day; managers then adjust them freely.
 */
class DefaultSlots
{
    /**
     * Create the default slots the stand does not have yet.
     *
     * @return int number of created slots
     */
    public function createFor(Stand $stand): int
    {
        $created = 0;
        $existing = $stand->slots()->get();

        foreach (config('marche.dates') as $date) {
            foreach ($this->ranges() as [$startsAt, $endsAt]) {
                $overlaps = $existing->contains(fn (Slot $slot) => $slot->date->toDateString() === $date
                    && $slot->starts_at < $endsAt
                    && $startsAt < $slot->ends_at);

                if ($overlaps) {
                    continue;
                }

                $slot = new Slot([
                    'date' => $date,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'capacity' => config('marche.planning.default_capacity'),
                ]);
                $slot->stand()->associate($stand);
                $slot->save();

                $existing->push($slot);
                $created++;
            }
        }

        return $created;
    }

    /**
     * Valid "HH:MM-HH:MM" ranges that fit the planning hours.
     *
     * @return list<array{string, string}>
     */
    public function ranges(): array
    {
        $hourMin = config('marche.planning.hour_min');
        $hourMax = config('marche.planning.hour_max');
        $ranges = [];

        foreach (config('marche.planning.default_slots') as $range) {
            if (! preg_match('/^(\d{2}:\d{2})-(\d{2}:\d{2})$/', trim((string) $range), $matches)) {
                continue;
            }

            [, $startsAt, $endsAt] = $matches;

            if ($startsAt < $endsAt && $startsAt >= $hourMin && $endsAt <= $hourMax) {
                $ranges[] = [$startsAt, $endsAt];
            }
        }

        return $ranges;
    }
}
