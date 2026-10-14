<?php

namespace App\TreeOrders;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

/**
 * Christmas trees on sale this year, order deadline and pickup, read from
 * config('marche.trees').
 */
class TreeCatalog
{
    /**
     * Sizes written "key|name|height|price in euros". Malformed lines are
     * skipped and logged rather than breaking the order page.
     *
     * @return array<string, TreeSize>
     */
    public function sizes(): array
    {
        $sizes = [];

        foreach ((array) config('marche.trees.sizes') as $line) {
            $parts = array_map('trim', explode('|', (string) $line));

            if (count($parts) !== 4 || $parts[0] === '' || ! is_numeric($parts[3])) {
                Log::warning('Ignored malformed TREE_SIZES entry.', ['entry' => $line]);

                continue;
            }

            [$key, $name, $height, $price] = $parts;
            $sizes[$key] = new TreeSize($key, $name, $height, (int) round((float) $price * 100));
        }

        return $sizes;
    }

    public function find(string $key): ?TreeSize
    {
        return $this->sizes()[$key] ?? null;
    }

    public function maxQuantity(): int
    {
        return max(1, (int) config('marche.trees.max_quantity'));
    }

    /**
     * Last moment to order: the end of the deadline day, in the market timezone.
     */
    public function deadline(): ?CarbonImmutable
    {
        $deadline = config('marche.trees.deadline');

        return filled($deadline)
            ? CarbonImmutable::parse($deadline, config('marche.timezone'))->endOfDay()
            : null;
    }

    public function isClosed(): bool
    {
        $deadline = $this->deadline();

        return $deadline !== null && now()->greaterThan($deadline);
    }

    public function pickupDate(): ?CarbonImmutable
    {
        $date = config('marche.trees.pickup.date');

        return filled($date) ? CarbonImmutable::parse($date, config('marche.timezone')) : null;
    }
}
