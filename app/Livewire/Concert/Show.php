<?php

namespace App\Livewire\Concert;

use App\Concert\ConcertTicket;
use App\Concert\ConcertTickets;
use App\Concert\TicketList;
use App\Exceptions\HelloAssoUnavailable;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Concert page: the HelloAsso ticketing and the tickets of the family.
 *
 * @property-read array{title: string, startsAt: ?CarbonImmutable, place: string, tiers: list<array{label: string, priceCents: int}>}|null $event
 * @property-read TicketList|null $ticketList
 * @property-read list<ConcertTicket> $myTickets
 */
class Show extends Component
{
    #[Computed]
    public function event(): ?array
    {
        return $this->concert()->isConnected() ? $this->concert()->event() : null;
    }

    /**
     * Null when HelloAsso fails and no list is known.
     */
    #[Computed]
    public function ticketList(): ?TicketList
    {
        try {
            return $this->concert()->tickets();
        } catch (HelloAssoUnavailable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * @return list<ConcertTicket>
     */
    #[Computed]
    public function myTickets(): array
    {
        return $this->ticketList?->forEmail(Auth::user()->email) ?? [];
    }

    public function render(): View
    {
        return view('livewire.concert.show', [
            'isConnected' => $this->concert()->isConnected(),
            'ticketingUrl' => $this->concert()->ticketingUrl(),
            'widgetUrl' => $this->concert()->widgetUrl(),
        ])->title(__('concert.title'));
    }

    private function concert(): ConcertTickets
    {
        return app(ConcertTickets::class);
    }
}
