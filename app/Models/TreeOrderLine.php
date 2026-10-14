<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\TreeOrderLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trees of one size in an order, with the label and price of the order time.
 *
 * @property int $id
 * @property int $tree_order_id
 * @property string $size_key
 * @property string $label
 * @property string $height
 * @property int $unit_price_cents
 * @property int $quantity
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read TreeOrder $order
 */
#[Fillable(['size_key', 'label', 'height', 'unit_price_cents', 'quantity'])]
class TreeOrderLine extends Model
{
    /** @use HasFactory<TreeOrderLineFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price_cents' => 'integer',
            'quantity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<TreeOrder, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(TreeOrder::class, 'tree_order_id');
    }

    public function amountCents(): int
    {
        return $this->unit_price_cents * $this->quantity;
    }
}
