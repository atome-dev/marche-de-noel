<?php

namespace App\Cookies;

use App\Exceptions\OrdersLocked;
use App\Models\Commitment;
use App\Models\IngredientOrder;
use App\Models\Recipe;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Baking commitments of the families and their ingredient orders.
 */
class CookieOrdering
{
    public function deadline(): ?CarbonImmutable
    {
        $deadline = config('marche.orders.deadline');

        return filled($deadline) ? CarbonImmutable::parse($deadline) : null;
    }

    /**
     * After the deadline, orders can neither be placed nor cancelled.
     */
    public function isLocked(): bool
    {
        $deadline = $this->deadline();

        return $deadline !== null && now()->greaterThan($deadline);
    }

    /**
     * "I plan to bake N kg". Changing the quantity cancels the linked orders.
     *
     * @return array{Commitment, int} the commitment and the number of cancelled orders
     *
     * @throws OrdersLocked
     */
    public function commit(User $user, Recipe $recipe, int $quantityKg): array
    {
        if (! in_array($quantityKg, config('marche.orders.quantities'), true)) {
            throw new InvalidArgumentException("Unsupported quantity [{$quantityKg}].");
        }

        return DB::transaction(function () use ($user, $recipe, $quantityKg): array {
            $commitment = Commitment::query()->firstOrNew(['user_id' => $user->id, 'recipe_id' => $recipe->id]);

            if ($commitment->exists && $commitment->quantity_kg === $quantityKg) {
                return [$commitment, 0];
            }

            $cancelled = 0;

            if ($commitment->exists) {
                $this->ensureOrdersCanChange($commitment);

                $cancelled = $commitment->orders()->delete();
            }

            $commitment->quantity_kg = $quantityKg;
            $commitment->save();

            return [$commitment, $cancelled];
        });
    }

    /**
     * @throws OrdersLocked
     */
    public function withdraw(User $user, Recipe $recipe): void
    {
        $commitment = Commitment::query()->where(['user_id' => $user->id, 'recipe_id' => $recipe->id])->first();

        if ($commitment === null) {
            return;
        }

        $this->ensureOrdersCanChange($commitment);

        $commitment->delete();
    }

    /**
     * Order one orderable ingredient of the recipe, in the recipe proportion.
     *
     * @throws OrdersLocked
     */
    public function order(Commitment $commitment, string $orderableKey): IngredientOrder
    {
        $this->ensureNotLocked();

        $gramsPerKg = $commitment->recipe->orderableGramsPerKg()[$orderableKey] ?? null;

        if ($gramsPerKg === null) {
            throw new InvalidArgumentException("Ingredient [{$orderableKey}] cannot be ordered for this recipe.");
        }

        return $commitment->orders()->updateOrCreate(
            ['orderable_key' => $orderableKey],
            ['grams' => $gramsPerKg * $commitment->quantity_kg],
        );
    }

    /**
     * @throws OrdersLocked
     */
    public function cancelOrder(Commitment $commitment, string $orderableKey): void
    {
        $this->ensureNotLocked();

        $commitment->orders()->where('orderable_key', $orderableKey)->delete();
    }

    private function ensureNotLocked(): void
    {
        if ($this->isLocked()) {
            throw new OrdersLocked;
        }
    }

    /**
     * A commitment without orders stays free after the deadline.
     */
    private function ensureOrdersCanChange(Commitment $commitment): void
    {
        if ($this->isLocked() && $commitment->orders()->exists()) {
            throw new OrdersLocked;
        }
    }
}
