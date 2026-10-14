<?php

namespace App\Livewire\TreeOrders;

use App\Models\TreeOrder;
use App\TreeOrders\TreeOrdering;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Pickup day: managers tick the paid orders as they are collected.
 *
 * @property-read Collection<int, TreeOrder> $orders
 */
class Pickup extends Component
{
    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('manage-tree-orders');
    }

    public function togglePickup(int $orderId, TreeOrdering $ordering): void
    {
        $this->authorize('manage-tree-orders');
        $order = TreeOrder::query()->findOrFail($orderId);

        try {
            $ordering->setPickedUp($order, ! $order->isPickedUp());
            Flux::toast(variant: 'success', text: __($order->isPickedUp() ? 'trees.toasts.picked_up' : 'trees.toasts.pickup_undone', ['number' => $order->number]));
        } catch (InvalidArgumentException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }

        unset($this->orders);
    }

    /**
     * Paid orders, picked up or not, by customer name.
     *
     * @return Collection<int, TreeOrder>
     */
    #[Computed]
    public function orders(): Collection
    {
        return TreeOrder::query()->counted()->with('lines')->search($this->search)->orderBy('customer_name')->get();
    }

    public function render(): View
    {
        $total = TreeOrder::query()->counted()->count();
        $done = TreeOrder::query()->counted()->whereNotNull('picked_up_at')->count();

        return view('livewire.tree-orders.pickup', [
            'total' => $total,
            'done' => $done,
            'refreshSeconds' => (int) config('marche.planning.refresh_seconds'),
        ])->title(__('trees.pickup_list.title'));
    }
}
