<?php

namespace App\Livewire\Raffle;

use App\Models\RafflePrize;
use App\Raffle\PrizeList;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Raffle prizes: everyone reads the list, the raffle managers edit it.
 *
 * @property-read Collection<int, RafflePrize> $prizes
 * @property-read bool $isValidated
 * @property-read bool $canManage
 */
class Index extends Component
{
    public string $label = '';

    public function add(PrizeList $list): void
    {
        $this->authorize('manage-raffle');

        $validated = $this->validate(['label' => ['required', 'string', 'max:255']]);

        $list->add(trim($validated['label']));

        $this->reset('label');
        $this->refreshList();
    }

    public function rename(int $prizeId, string $label): void
    {
        $this->authorize('manage-raffle');

        $validated = Validator::make(['label' => trim($label)], [
            'label' => ['required', 'string', 'max:255'],
        ])->validate();

        RafflePrize::findOrFail($prizeId)->update($validated);

        $this->refreshList();

        Flux::toast(variant: 'success', text: __('raffle.toasts.saved'));
    }

    /**
     * Drag and drop: the position is the zero-based index in the list.
     */
    public function reorder(int $prizeId, int $position, PrizeList $list): void
    {
        $this->authorize('manage-raffle');

        $list->move(RafflePrize::findOrFail($prizeId), $position + 1);

        $this->refreshList();
    }

    public function moveUp(int $prizeId, PrizeList $list): void
    {
        $this->authorize('manage-raffle');

        $prize = RafflePrize::findOrFail($prizeId);
        $list->move($prize, $prize->position - 1);

        $this->refreshList();
    }

    public function moveDown(int $prizeId, PrizeList $list): void
    {
        $this->authorize('manage-raffle');

        $prize = RafflePrize::findOrFail($prizeId);
        $list->move($prize, $prize->position + 1);

        $this->refreshList();
    }

    public function delete(int $prizeId, PrizeList $list): void
    {
        $this->authorize('manage-raffle');

        $list->delete(RafflePrize::findOrFail($prizeId));

        $this->refreshList();
    }

    /**
     * Freeze the prize numbers before the draw.
     */
    public function validateList(PrizeList $list): void
    {
        $this->authorize('manage-raffle');

        if ($list->validate()) {
            Flux::toast(variant: 'success', text: __('raffle.toasts.validated'));
        }

        $this->refreshList();
    }

    public function saveTicket(int $prizeId, string $ticket, PrizeList $list): void
    {
        $this->authorize('manage-raffle');

        abort_unless($list->isValidated(), 403);

        $validated = Validator::make(['winning_ticket' => trim($ticket)], [
            'winning_ticket' => ['nullable', 'string', 'max:50'],
        ])->validate();

        RafflePrize::findOrFail($prizeId)->update([
            'winning_ticket' => filled($validated['winning_ticket']) ? $validated['winning_ticket'] : null,
        ]);

        $this->refreshList();

        Flux::toast(variant: 'success', text: __('raffle.toasts.saved'));
    }

    /**
     * @return Collection<int, RafflePrize>
     */
    #[Computed]
    public function prizes(): Collection
    {
        return RafflePrize::query()->ordered()->get();
    }

    #[Computed]
    public function isValidated(): bool
    {
        return app(PrizeList::class)->isValidated();
    }

    #[Computed]
    public function canManage(): bool
    {
        return Gate::allows('manage-raffle');
    }

    public function render(): View
    {
        return view('livewire.raffle.index')
            ->title(__('raffle.title'));
    }

    private function refreshList(): void
    {
        unset($this->prizes, $this->isValidated);
    }
}
