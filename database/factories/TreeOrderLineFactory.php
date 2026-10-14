<?php

namespace Database\Factories;

use App\Models\TreeOrder;
use App\Models\TreeOrderLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TreeOrderLine>
 */
class TreeOrderLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tree_order_id' => TreeOrder::factory(),
            'size_key' => 'moyen',
            'label' => 'Le sapin moyen',
            'height' => '125/150 cm',
            'unit_price_cents' => 3500,
            'quantity' => 1,
        ];
    }
}
