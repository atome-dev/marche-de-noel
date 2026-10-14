<?php

namespace Database\Factories;

use App\Enums\TreePaymentMethod;
use App\Models\TreeOrder;
use App\Models\TreeOrderLine;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TreeOrder>
 */
class TreeOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'customer_name' => fake()->firstName().' '.fake()->lastName(),
            'customer_phone' => '06 12 34 56 78',
            'customer_email' => fake()->unique()->safeEmail(),
            'total_cents' => 0,
        ];
    }

    /**
     * Entered by a manager for someone without an account.
     */
    public function guest(): static
    {
        return $this->state(fn (array $attributes) => ['user_id' => null]);
    }

    public function paid(TreePaymentMethod $method = TreePaymentMethod::Cheque): static
    {
        return $this->state(fn (array $attributes) => ['paid_at' => now()])
            ->afterMaking(fn (TreeOrder $order) => $order->payment_method = $method);
    }

    public function pickedUp(): static
    {
        return $this->afterMaking(fn (TreeOrder $order) => $order->picked_up_at = now());
    }

    public function cancelled(): static
    {
        return $this->afterMaking(fn (TreeOrder $order) => $order->cancelled_at = now());
    }

    /**
     * Adds a line and keeps the order total in step.
     */
    public function withLine(string $key, int $quantity, int $unitPriceCents, ?string $label = null): static
    {
        return $this->afterMaking(fn (TreeOrder $order) => $order->total_cents += $quantity * $unitPriceCents)
            ->afterCreating(fn (TreeOrder $order) => TreeOrderLine::factory()->for($order, 'order')->create([
                'size_key' => $key,
                'label' => $label ?? ucfirst($key),
                'quantity' => $quantity,
                'unit_price_cents' => $unitPriceCents,
            ]));
    }
}
