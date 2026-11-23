<?php

namespace App\Planning;

use App\Models\Slot;
use App\Models\Stand;
use Carbon\CarbonImmutable;

/**
 * Places still to fill, by stand and by day.
 *
 * Covers the stands of the planning (active, with at least one place), from
 * today onwards like the planning itself. A cell is null when the stand has
 * no slot that day.
 */
class OpenPlacesSummary
{
    /** @var list<string> days shown (Y-m-d), in chronological order */
    private array $days;

    /** @var list<array{stand: Stand, cells: array<string, array{open: int, capacity: int}|null>, open: int, capacity: int}> */
    private array $rows;

    /**
     * @param  list<int>|null  $standIds  stands to cover; null for every stand
     */
    public function __construct(?array $standIds = null, ?string $from = null)
    {
        $from ??= PlanningGrid::today();

        $stands = Stand::query()
            ->inPlanning()
            ->when($standIds !== null, fn ($query) => $query->whereKey($standIds))
            ->ordered()
            ->with(['managers', 'slots' => fn ($slots) => $slots
                ->where('date', '>=', $from)
                ->where('capacity', '>', 0)
                ->withCount('registrations')])
            ->get()
            ->filter(fn (Stand $stand): bool => $stand->slots->isNotEmpty())
            ->values();

        $this->days = $stands
            ->flatMap(fn (Stand $stand) => $stand->slots->map(fn (Slot $slot): string => $slot->date->toDateString()))
            ->unique()
            ->sort()
            ->values()
            ->all();

        $this->rows = $stands->map(fn (Stand $stand): array => $this->buildRow($stand))->all();
    }

    /**
     * @return list<string>
     */
    public function days(): array
    {
        return $this->days;
    }

    /**
     * @return list<array{stand: Stand, cells: array<string, array{open: int, capacity: int}|null>, open: int, capacity: int}>
     */
    public function rows(): array
    {
        return $this->rows;
    }

    /**
     * Rows of the stands with places still to fill.
     *
     * @return list<array{stand: Stand, cells: array<string, array{open: int, capacity: int}|null>, open: int, capacity: int}>
     */
    public function incompleteRows(): array
    {
        return array_values(array_filter($this->rows, fn (array $row): bool => $row['open'] > 0));
    }

    /**
     * Rows of the stands whose managers can be reminded now: incomplete, with
     * managers, and not reminded too recently.
     *
     * @return list<array{stand: Stand, cells: array<string, array{open: int, capacity: int}|null>, open: int, capacity: int}>
     */
    public function remindableRows(): array
    {
        return array_values(array_filter($this->incompleteRows(), fn (array $row): bool => $row['stand']->managers->isNotEmpty()
            && $row['stand']->canSendPlanningReminder()));
    }

    /**
     * Number of emails a reminder sends: one per manager of each remindable stand.
     */
    public function reminderCount(): int
    {
        return array_sum(array_map(fn (array $row): int => $row['stand']->managers->count(), $this->remindableRows()));
    }

    public function isEmpty(): bool
    {
        return $this->rows === [];
    }

    /**
     * A day before the first market day, used to get ready.
     */
    public function isPreparation(string $day): bool
    {
        $marketDates = config('marche.dates');

        return $marketDates !== [] && $day < min($marketDates);
    }

    /**
     * @return array{open: int, capacity: int}
     */
    public function dayTotal(string $day): array
    {
        $cells = array_filter(array_column(array_column($this->rows, 'cells'), $day));

        return [
            'open' => array_sum(array_column($cells, 'open')),
            'capacity' => array_sum(array_column($cells, 'capacity')),
        ];
    }

    /**
     * @return array{open: int, capacity: int}
     */
    public function total(): array
    {
        return [
            'open' => array_sum(array_column($this->rows, 'open')),
            'capacity' => array_sum(array_column($this->rows, 'capacity')),
        ];
    }

    public static function dayLabel(string $day): string
    {
        return ucfirst(CarbonImmutable::parse($day)->locale(app()->getLocale())->translatedFormat('D j M'));
    }

    /**
     * @return array{stand: Stand, cells: array<string, array{open: int, capacity: int}|null>, open: int, capacity: int}
     */
    private function buildRow(Stand $stand): array
    {
        $cells = [];

        foreach ($this->days as $day) {
            $slots = $stand->slots->filter(fn (Slot $slot): bool => $slot->date->toDateString() === $day);

            $cells[$day] = $slots->isEmpty() ? null : [
                // An overbooked slot does not make up for another one.
                'open' => (int) $slots->sum(fn (Slot $slot): int => max(0, $slot->capacity - $slot->registrations_count)),
                'capacity' => (int) $slots->sum('capacity'),
            ];
        }

        $filled = array_filter($cells);

        return [
            'stand' => $stand,
            'cells' => $cells,
            'open' => array_sum(array_column($filled, 'open')),
            'capacity' => array_sum(array_column($filled, 'capacity')),
        ];
    }
}
