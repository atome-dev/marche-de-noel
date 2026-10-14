<?php

namespace App\Livewire\TreeOrders;

use App\Enums\TreeOrderStatus;
use App\Models\TreeOrder;
use App\TreeOrders\TreeCatalog;
use App\TreeOrders\TreeOrdering;
use App\TreeOrders\TreeSize;
use Closure;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Tree orders for the tree stand managers: cheques, cancellations and
 * orders for people without an account.
 *
 * @property-read Collection<int, TreeOrder> $orders
 * @property-read array<string, TreeSize> $sizes
 * @property-read int $guestTotalCents
 */
class Manage extends Component
{
    #[Url]
    public string $status = '';

    #[Url(as: 'q')]
    public string $search = '';

    public string $guestName = '';

    public string $guestPhone = '';

    public string $guestEmail = '';

    /** @var array<string, int> size key => number of trees */
    public array $guestQuantities = [];

    public bool $guestChequeReceived = false;

    public function mount(): void
    {
        $this->authorize('manage-tree-orders');

        $this->guestQuantities = array_map(fn () => 0, $this->sizes);
    }

    public function markCheque(int $orderId, TreeOrdering $ordering): void
    {
        $this->authorize('manage-tree-orders');
        $order = TreeOrder::query()->findOrFail($orderId);

        $this->attempt(fn () => $ordering->markChequeReceived($order, Auth::user()), __('trees.toasts.cheque_received', ['number' => $order->number]));
    }

    public function unmarkCheque(int $orderId, TreeOrdering $ordering): void
    {
        $this->authorize('manage-tree-orders');
        $order = TreeOrder::query()->findOrFail($orderId);

        $this->attempt(fn () => $ordering->unmarkCheque($order), __('trees.toasts.cheque_unmarked', ['number' => $order->number]));
    }

    public function cancelOrder(int $orderId, TreeOrdering $ordering): void
    {
        $this->authorize('manage-tree-orders');
        $order = TreeOrder::query()->findOrFail($orderId);

        $this->attempt(fn () => $ordering->cancel($order, byManager: true), __('trees.toasts.cancelled', ['number' => $order->number]));
    }

    public function createGuestOrder(TreeOrdering $ordering): void
    {
        $this->authorize('manage-tree-orders');

        $validated = $this->validate([
            'guestName' => ['required', 'string', 'max:255'],
            'guestPhone' => ['required', 'string', 'max:30'],
            'guestEmail' => ['nullable', 'email', 'max:255'],
            'guestQuantities' => ['array', function (string $attribute, mixed $value, Closure $fail): void {
                if (array_sum(array_map('intval', (array) $value)) === 0) {
                    $fail(__('trees.form.no_tree'));
                }
            }],
            'guestQuantities.*' => ['integer', 'min:0', 'max:'.app(TreeCatalog::class)->maxQuantity()],
            'guestChequeReceived' => ['boolean'],
        ], attributes: [
            'guestName' => __('trees.form.name'),
            'guestPhone' => __('trees.form.phone'),
            'guestEmail' => __('fields.email'),
        ]);

        $order = $ordering->placeForGuest(
            Auth::user(),
            $validated['guestQuantities'],
            $validated['guestName'],
            $validated['guestPhone'],
            $validated['guestEmail'] ?: null,
            $validated['guestChequeReceived'],
        );

        $this->reset('guestName', 'guestPhone', 'guestEmail', 'guestChequeReceived');
        $this->guestQuantities = array_map(fn () => 0, $this->sizes);
        unset($this->orders);

        Flux::modal('guest-order')->close();
        Flux::toast(variant: 'success', text: __('trees.toasts.placed', ['number' => $order->number]));
    }

    /**
     * @return Collection<int, TreeOrder>
     */
    #[Computed]
    public function orders(): Collection
    {
        $status = TreeOrderStatus::tryFrom($this->status);

        return TreeOrder::query()
            ->with('lines')
            ->search($this->search)
            ->when($status, fn ($query) => $query->withStatus($status))
            ->latest('id')
            ->get();
    }

    /**
     * @return array<string, TreeSize>
     */
    #[Computed]
    public function sizes(): array
    {
        return app(TreeCatalog::class)->sizes();
    }

    /**
     * Total of the guest order being typed, from the current prices.
     */
    #[Computed]
    public function guestTotalCents(): int
    {
        return collect($this->sizes)->sum(fn (TreeSize $size) => $size->priceCents * max(0, (int) ($this->guestQuantities[$size->key] ?? 0)));
    }

    public function render(): View
    {
        return view('livewire.tree-orders.manage', [
            'statuses' => TreeOrderStatus::cases(),
            'maxQuantity' => app(TreeCatalog::class)->maxQuantity(),
        ])->title(__('trees.manage.title'));
    }

    /**
     * @param  Closure(): void  $action
     */
    private function attempt(Closure $action, string $success): void
    {
        try {
            $action();
            Flux::toast(variant: 'success', text: $success);
        } catch (InvalidArgumentException $exception) {
            Flux::toast(variant: 'danger', text: $exception->getMessage());
        }

        unset($this->orders);
    }
}
