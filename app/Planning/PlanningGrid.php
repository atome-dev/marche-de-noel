<?php

namespace App\Planning;

use App\Models\Slot;
use App\Models\Stand;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Collection;

/**
 * Volunteer planning: one table per market day.
 *
 * Columns always run from the planning opening hour to the closing hour, in
 * 30 minute steps. Each slot starts at the column of its start time and spans
 * as many columns as its duration; a day only lists the stands open that day.
 */
class PlanningGrid
{
    public const STEP_MINUTES = 30;

    private int $openingMinutes;

    private int $columnCount;

    /** @var Collection<string, PlanningDay> */
    private Collection $days;

    /**
     * @param  Collection<int, Stand>  $stands  stands with their slots loaded
     * @param  (Closure(Stand, Collection<int, Slot>): bool)|null  $rowFilter  keeps a stand on a given day
     */
    public function __construct(
        Collection $stands,
        ?string $day = null,
        ?string $hourMin = null,
        ?string $hourMax = null,
        ?Closure $rowFilter = null,
    ) {
        $this->openingMinutes = self::toMinutes($hourMin ?? config('marche.planning.hour_min'));
        $closingMinutes = self::toMinutes($hourMax ?? config('marche.planning.hour_max'));
        $this->columnCount = max(1, intdiv($closingMinutes - $this->openingMinutes, self::STEP_MINUTES));

        $this->days = $stands
            ->flatMap(fn (Stand $stand) => $stand->slots)
            ->map(fn (Slot $slot) => $slot->date->toDateString())
            ->unique()
            ->filter(fn (string $date) => $day === null || $date === $day)
            ->sort()
            ->mapWithKeys(fn (string $date) => [$date => $this->buildDay($date, $stands, $rowFilter)])
            ->filter(fn (PlanningDay $planningDay) => $planningDay->rows->isNotEmpty());
    }

    /**
     * Start time (HH:MM) of every column.
     *
     * @return list<string>
     */
    public function times(): array
    {
        return array_map(fn (int $index) => $this->timeAt($index), range(0, $this->columnCount - 1));
    }

    /**
     * @return Collection<string, PlanningDay>
     */
    public function days(): Collection
    {
        return $this->days;
    }

    public function isEmpty(): bool
    {
        return $this->days->isEmpty();
    }

    /**
     * @param  Collection<int, Stand>  $stands
     * @param  (Closure(Stand, Collection<int, Slot>): bool)|null  $rowFilter
     */
    private function buildDay(string $date, Collection $stands, ?Closure $rowFilter): PlanningDay
    {
        $rows = $stands
            ->map(function (Stand $stand) use ($date, $rowFilter): ?PlanningRow {
                $slots = $stand->slots
                    ->filter(fn (Slot $slot) => $slot->date->toDateString() === $date)
                    ->sortBy(fn (Slot $slot) => $slot->starts_at.$slot->ends_at)
                    ->values();

                if ($slots->isEmpty() || ($rowFilter !== null && ! $rowFilter($stand, $slots))) {
                    return null;
                }

                return new PlanningRow($stand, $slots, $this->buildCells($slots));
            })
            ->filter()
            ->values();

        return new PlanningDay(CarbonImmutable::parse($date), $rows);
    }

    /**
     * @param  Collection<int, Slot>  $slots
     * @return list<PlanningCell>
     */
    private function buildCells(Collection $slots): array
    {
        $cells = [];
        $cursor = 0;

        foreach ($slots as $slot) {
            $start = max($cursor, $this->columnOf($slot->starts_at));
            $end = min($this->columnCount, max($start + 1, $this->columnOf($slot->ends_at, roundUp: true)));

            // A slot that does not fit (overlap or outside the hours) cannot be drawn.
            if ($start >= $this->columnCount) {
                continue;
            }

            for (; $cursor < $start; $cursor++) {
                $cells[] = new PlanningCell($this->timeAt($cursor), 1);
            }

            $cells[] = new PlanningCell($this->timeAt($start), $end - $start, $slot);
            $cursor = $end;
        }

        for (; $cursor < $this->columnCount; $cursor++) {
            $cells[] = new PlanningCell($this->timeAt($cursor), 1);
        }

        return $cells;
    }

    private function columnOf(string $time, bool $roundUp = false): int
    {
        $offset = (self::toMinutes($time) - $this->openingMinutes) / self::STEP_MINUTES;

        return max(0, (int) ($roundUp ? ceil($offset) : floor($offset)));
    }

    private function timeAt(int $column): string
    {
        $minutes = $this->openingMinutes + $column * self::STEP_MINUTES;

        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    private static function toMinutes(string $time): int
    {
        return (int) substr($time, 0, 2) * 60 + (int) substr($time, 3, 2);
    }
}
