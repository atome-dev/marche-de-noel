<?php

namespace App\Livewire\Cookies;

use App\Cookies\CookieOrdering;
use App\Cookies\OrderableIngredients;
use App\Exceptions\OrdersLocked;
use App\Models\Commitment;
use App\Models\Recipe;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Recipe page with the family commitment and its ingredient orders.
 *
 * @property-read Recipe $recipe
 * @property-read Commitment|null $commitment
 * @property-read array<string, array{name: string, grams: int, ordered: int|null}> $orderables
 * @property-read bool $locked
 * @property-read CarbonImmutable|null $deadline
 */
class Show extends Component
{
    #[Locked]
    public int $recipeId;

    public int $quantity = 1;

    public bool $ordersCancelled = false;

    public function mount(Recipe $recipe): void
    {
        $this->recipeId = $recipe->id;
        $this->quantity = $this->commitment->quantity_kg ?? (config('marche.orders.quantities')[0] ?? 1);
    }

    public function saveCommitment(CookieOrdering $ordering): void
    {
        $this->resetErrorBag();

        try {
            [, $cancelled] = $ordering->commit(Auth::user(), $this->recipe, $this->quantity);
        } catch (OrdersLocked $exception) {
            $this->quantity = $this->commitment->quantity_kg ?? $this->quantity;
            $this->addError('orders', $exception->getMessage());

            return;
        } catch (InvalidArgumentException) {
            $this->addError('quantity', __('validation.in', ['attribute' => __('cookies.show.quantity')]));

            return;
        }

        $this->ordersCancelled = $cancelled > 0;
        unset($this->commitment, $this->orderables);

        Flux::toast(variant: 'success', text: __('cookies.show.committed', ['quantity' => $this->quantity]));
    }

    public function withdraw(CookieOrdering $ordering): void
    {
        $this->resetErrorBag();

        try {
            $ordering->withdraw(Auth::user(), $this->recipe);
        } catch (OrdersLocked $exception) {
            $this->addError('orders', $exception->getMessage());

            return;
        }

        $this->ordersCancelled = false;
        unset($this->commitment, $this->orderables);

        Flux::toast(text: __('cookies.show.withdrawn'));
    }

    public function order(CookieOrdering $ordering, string $key): void
    {
        $this->changeOrder(fn (Commitment $commitment) => $ordering->order($commitment, $key));
    }

    public function cancelOrder(CookieOrdering $ordering, string $key): void
    {
        $this->changeOrder(fn (Commitment $commitment) => $ordering->cancelOrder($commitment, $key));
    }

    #[Computed]
    public function recipe(): Recipe
    {
        return Recipe::query()
            ->with(['category', 'schoolClasses', 'ingredients', 'photos'])
            ->findOrFail($this->recipeId);
    }

    #[Computed]
    public function commitment(): ?Commitment
    {
        return Commitment::query()
            ->with('orders')
            ->where(['user_id' => Auth::id(), 'recipe_id' => $this->recipeId])
            ->first();
    }

    /**
     * Orderable ingredients of the recipe for the validated quantity.
     *
     * @return array<string, array{name: string, grams: int, ordered: int|null}>
     */
    #[Computed]
    public function orderables(): array
    {
        if ($this->commitment === null) {
            return [];
        }

        $names = app(OrderableIngredients::class);
        $ordered = $this->commitment->orders->pluck('grams', 'orderable_key');

        return collect($this->recipe->orderableGramsPerKg())
            ->map(fn (int $gramsPerKg, string $key): array => [
                'name' => (string) $names->name($key),
                'grams' => $gramsPerKg * $this->commitment->quantity_kg,
                'ordered' => $ordered->get($key),
            ])
            ->all();
    }

    #[Computed]
    public function locked(): bool
    {
        return app(CookieOrdering::class)->isLocked();
    }

    #[Computed]
    public function deadline(): ?CarbonImmutable
    {
        return app(CookieOrdering::class)->deadline();
    }

    public function render(): View
    {
        return view('livewire.cookies.show')
            ->title($this->recipe->title);
    }

    /**
     * @param  callable(Commitment): mixed  $change
     */
    private function changeOrder(callable $change): void
    {
        $this->resetErrorBag();

        abort_if($this->commitment === null, 404);

        try {
            $change($this->commitment);
        } catch (OrdersLocked $exception) {
            $this->addError('orders', $exception->getMessage());

            return;
        } catch (InvalidArgumentException) {
            abort(404);
        }

        $this->ordersCancelled = false;
        unset($this->commitment, $this->orderables);
    }
}
