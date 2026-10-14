<?php

namespace App\TreeOrders;

use App\Models\TreeOrder;
use App\Models\TreeOrderLine;
use Illuminate\Database\Eloquent\Collection;

/**
 * Trees to order from the producer: paid orders only, by size.
 */
class TreeOrdersSummary
{
    /** @var list<array{key: string, label: string, height: string, quantity: int, amount_cents: int}>|null */
    private ?array $rows = null;

    /**
     * Sizes of the catalogue first, in its order, then sizes no longer on sale.
     *
     * @return list<array{key: string, label: string, height: string, quantity: int, amount_cents: int}>
     */
    public function rows(): array
    {
        if ($this->rows !== null) {
            return $this->rows;
        }

        $rows = [];

        foreach (app(TreeCatalog::class)->sizes() as $size) {
            $rows[$size->key] = ['key' => $size->key, 'label' => $size->name, 'height' => $size->height, 'quantity' => 0, 'amount_cents' => 0];
        }

        $lines = TreeOrderLine::query()->whereIn('tree_order_id', TreeOrder::query()->counted()->select('id'))->get();

        foreach ($lines as $line) {
            $rows[$line->size_key] ??= ['key' => $line->size_key, 'label' => $line->label, 'height' => $line->height, 'quantity' => 0, 'amount_cents' => 0];
            $rows[$line->size_key]['quantity'] += $line->quantity;
            $rows[$line->size_key]['amount_cents'] += $line->amountCents();
        }

        return $this->rows = array_values($rows);
    }

    public function totalQuantity(): int
    {
        return array_sum(array_column($this->rows(), 'quantity'));
    }

    public function totalAmountCents(): int
    {
        return array_sum(array_column($this->rows(), 'amount_cents'));
    }

    /**
     * Pending orders, not counted until paid.
     */
    public function pendingCount(): int
    {
        return TreeOrder::query()->whereNull('paid_at')->whereNull('cancelled_at')->count();
    }

    public function pendingAmountCents(): int
    {
        return (int) TreeOrder::query()->whereNull('paid_at')->whereNull('cancelled_at')->sum('total_cents');
    }

    /**
     * @return Collection<int, TreeOrder>
     */
    public function paidOrders(): Collection
    {
        return TreeOrder::query()->counted()->with('lines')->orderBy('customer_name')->get();
    }
}
