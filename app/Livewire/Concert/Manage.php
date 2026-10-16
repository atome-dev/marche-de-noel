<?php

namespace App\Livewire\Concert;

use App\Concert\ConcertTicket;
use App\Concert\ConcertTickets;
use App\Concert\TicketList;
use App\Exceptions\HelloAssoUnavailable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Ticket sales and entries of the concert for its stand managers, read from
 * HelloAsso and refreshed every 30 seconds.
 *
 * @property-read TicketList|null $ticketList
 * @property-read list<ConcertTicket> $spectators
 */
class Manage extends Component
{
    #[Url(as: 'q')]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('manage-concert');
    }

    public function refresh(): void
    {
        $this->authorize('manage-concert');

        app(ConcertTickets::class)->forget();
        unset($this->ticketList, $this->spectators);
    }

    /**
     * Null without API keys, or when HelloAsso fails and no list is known.
     */
    #[Computed]
    public function ticketList(): ?TicketList
    {
        $concert = app(ConcertTickets::class);

        if (! $concert->isConnected()) {
            return null;
        }

        try {
            return $concert->tickets();
        } catch (HelloAssoUnavailable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * @return list<ConcertTicket>
     */
    #[Computed]
    public function spectators(): array
    {
        $search = Str::lower(Str::ascii(trim($this->search)));

        return collect($this->ticketList?->tickets ?? [])
            ->filter(fn (ConcertTicket $ticket) => $search === '' || str_contains(
                Str::lower(Str::ascii($ticket->holderName.' '.$ticket->payerName.' '.$ticket->payerEmail)),
                $search,
            ))
            ->sortBy(fn (ConcertTicket $ticket) => Str::lower(Str::ascii($ticket->holderName)))
            ->values()
            ->all();
    }

    public function render(): View
    {
        return view('livewire.concert.manage', [
            'isConnected' => app(ConcertTickets::class)->isConnected(),
        ])->title(__('concert.manage.title'));
    }
}
