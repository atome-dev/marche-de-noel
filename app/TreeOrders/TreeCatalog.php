<?php

namespace App\TreeOrders;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

/**
 * Christmas trees on sale this year, order deadline and pickup, read from
 * config/trees.php.
 */
class TreeCatalog
{
    /**
     * Sizes of config('trees.sizes'). Incomplete entries are skipped and
     * logged rather than breaking the order page.
     *
     * @return array<string, TreeSize>
     */
    public function sizes(): array
    {
        $sizes = [];

        foreach ((array) config('trees.sizes') as $size) {
            $key = trim((string) ($size['key'] ?? ''));
            $price = $size['price'] ?? null;

            if ($key === '' || blank($size['name'] ?? null) || ! is_numeric($price)) {
                Log::warning('Ignored incomplete tree size in config/trees.php.', ['size' => $size]);

                continue;
            }

            $sizes[$key] = new TreeSize($key, trim((string) $size['name']), trim((string) ($size['height'] ?? '')), (int) round((float) $price * 100));
        }

        return $sizes;
    }

    public function find(string $key): ?TreeSize
    {
        return $this->sizes()[$key] ?? null;
    }

    public function maxQuantity(): int
    {
        return max(1, (int) config('trees.max_quantity'));
    }

    /**
     * Last moment to order: the end of the deadline day, in the market timezone.
     */
    public function deadline(): ?CarbonImmutable
    {
        $deadline = config('trees.deadline');

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
        $date = config('trees.pickup.date');

        return filled($date) ? CarbonImmutable::parse($date, config('marche.timezone')) : null;
    }
}
