<?php

namespace App\Concert;

use Carbon\CarbonImmutable;

/**
 * A valid concert ticket read from HelloAsso: processed and never refunded.
 */
final readonly class ConcertTicket
{
    public function __construct(
        public int $id,
        public string $tier,
        public int $amountCents,
        public string $holderName,
        public string $payerName,
        public string $payerEmail,
        public CarbonImmutable $orderedAt,
        public ?string $ticketUrl,
        public bool $isScanned,
    ) {}

    /**
     * @param  array<string, mixed>  $item  ticket of the items endpoint (withDetails=true)
     */
    public static function fromHelloAsso(array $item): ?self
    {
        if (($item['state'] ?? null) !== 'Processed' || self::isRefunded((array) ($item['payments'] ?? []))) {
            return null;
        }

        return new self(
            id: (int) $item['id'],
            tier: (string) ($item['name'] ?? ''),
            amountCents: (int) ($item['amount'] ?? 0),
            holderName: self::name((array) ($item['user'] ?? [])),
            payerName: self::name((array) ($item['payer'] ?? [])),
            payerEmail: trim((string) ($item['payer']['email'] ?? '')),
            orderedAt: CarbonImmutable::parse((string) ($item['order']['date'] ?? 'now')),
            ticketUrl: filled($item['ticketUrl'] ?? null) ? (string) $item['ticketUrl'] : null,
            isScanned: (bool) ($item['isScanned'] ?? false),
        );
    }

    /**
     * @param  array<int, mixed>  $payments
     */
    private static function isRefunded(array $payments): bool
    {
        foreach ($payments as $payment) {
            if (($payment['state'] ?? null) === 'Refunded' || ! empty($payment['refundOperations'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $person
     */
    private static function name(array $person): string
    {
        return trim(($person['firstName'] ?? '').' '.($person['lastName'] ?? ''));
    }
}
