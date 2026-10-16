<?php

namespace App\Concert;

/**
 * Concert tickets at a given time; stale when HelloAsso did not answer and
 * the last known list is shown.
 */
final readonly class TicketList
{
    /**
     * @param  list<ConcertTicket>  $tickets
     */
    public function __construct(public array $tickets, public bool $isStale) {}

    /**
     * @return list<ConcertTicket>
     */
    public function forEmail(string $email): array
    {
        $email = mb_strtolower(trim($email));

        return array_values(array_filter($this->tickets, fn (ConcertTicket $ticket) => mb_strtolower($ticket->payerEmail) === $email));
    }

    /**
     * @return array{tiers: list<array{tier: string, count: int, amountCents: int}>, count: int, amountCents: int, scanned: int}
     */
    public function summary(): array
    {
        $tiers = [];

        foreach ($this->tickets as $ticket) {
            $tiers[$ticket->tier] ??= ['tier' => $ticket->tier, 'count' => 0, 'amountCents' => 0];
            $tiers[$ticket->tier]['count']++;
            $tiers[$ticket->tier]['amountCents'] += $ticket->amountCents;
        }

        return [
            'tiers' => array_values($tiers),
            'count' => count($this->tickets),
            'amountCents' => array_sum(array_map(fn (ConcertTicket $ticket) => $ticket->amountCents, $this->tickets)),
            'scanned' => count(array_filter($this->tickets, fn (ConcertTicket $ticket) => $ticket->isScanned)),
        ];
    }
}
