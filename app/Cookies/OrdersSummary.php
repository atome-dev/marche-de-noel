<?php

namespace App\Cookies;

use App\Models\IngredientOrder;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Purchasing summary: families as rows, orderable ingredients as columns.
 */
class OrdersSummary
{
    /** @var Collection<int, array{user: User, grams: array<string, int>, total: int}>|null */
    private ?Collection $rows = null;

    public function __construct(private OrderableIngredients $orderable) {}

    /**
     * @return array<string, string> key => name
     */
    public function ingredients(): array
    {
        return $this->orderable->all();
    }

    /**
     * One row per family having at least one order, sorted by name.
     *
     * @return Collection<int, array{user: User, grams: array<string, int>, total: int}>
     */
    public function rows(): Collection
    {
        if ($this->rows !== null) {
            return $this->rows;
        }

        $keys = array_keys($this->ingredients());

        return $this->rows = IngredientOrder::query()
            ->whereIn('orderable_key', $keys)
            ->with('commitment.user')
            ->get()
            ->groupBy(fn (IngredientOrder $order) => $order->commitment->user_id)
            ->map(function (Collection $orders) use ($keys): array {
                $grams = array_fill_keys($keys, 0);

                foreach ($orders as $order) {
                    $grams[$order->orderable_key] += $order->grams;
                }

                return [
                    'user' => $orders->first()->commitment->user,
                    'grams' => $grams,
                    'total' => array_sum($grams),
                ];
            })
            ->sortBy(fn (array $row) => mb_strtolower($row['user']->last_name.' '.$row['user']->first_name))
            ->values();
    }

    /**
     * @return array<string, int> grams per ingredient
     */
    public function totals(): array
    {
        $totals = array_fill_keys(array_keys($this->ingredients()), 0);

        foreach ($this->rows() as $row) {
            foreach ($row['grams'] as $key => $grams) {
                $totals[$key] += $grams;
            }
        }

        return $totals;
    }

    public function grandTotal(): int
    {
        return array_sum($this->totals());
    }
}
