<?php

namespace App\Cookies;

use Illuminate\Support\Str;

/**
 * Ingredients families can order this year (ORDER_INGREDIENTS in .env),
 * keyed by a stable slug of their name.
 */
class OrderableIngredients
{
    /**
     * @return array<string, string> key => name
     */
    public function all(): array
    {
        $ingredients = [];

        foreach ((array) config('marche.orders.ingredients') as $name) {
            $ingredients[Str::slug((string) $name)] = (string) $name;
        }

        return $ingredients;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    public function name(string $key): ?string
    {
        return $this->all()[$key] ?? null;
    }
}
