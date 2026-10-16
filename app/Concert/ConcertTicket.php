<?php

namespace App\Concert;

use Carbon\CarbonImmutable;
use Throwable;

/**
 * A valid concert ticket read from HelloAsso: a registration, processed and
 * never refunded. Cached as an array holding only the fields shown.
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
     * Null for anything else than a valid ticket, malformed items included.
     *
     * @param  array<string, mixed>  $item  item of the items endpoint (withDetails=true)
     */
    public static function fromHelloAsso(array $item): ?self
    {
        if (($item['state'] ?? null) !== 'Processed'
            || ($item['type'] ?? null) !== 'Registration'
            || ! is_numeric($item['id'] ?? null)
            || ! is_array($item['payments'] ?? [])
            || self::isRefunded($item['payments'] ?? [])) {
            return null;
        }

        try {
            $orderedAt = CarbonImmutable::parse((string) (is_array($item['order'] ?? null) ? ($item['order']['date'] ?? '') : ''));
        } catch (Throwable) {
            return null;
        }

        $payer = is_array($item['payer'] ?? null) ? $item['payer'] : [];

        return new self(
            id: (int) $item['id'],
            tier: (string) ($item['name'] ?? ''),
            amountCents: (int) ($item['amount'] ?? 0),
            holderName: self::name(is_array($item['user'] ?? null) ? $item['user'] : []),
            payerName: self::name($payer),
            payerEmail: trim((string) ($payer['email'] ?? '')),
            orderedAt: $orderedAt,
            ticketUrl: filled($item['ticketUrl'] ?? null) ? (string) $item['ticketUrl'] : null,
            isScanned: (bool) ($item['isScanned'] ?? false),
        );
    }

    /**
     * @param  array{id: int, tier: string, amountCents: int, holderName: string, payerName: string, payerEmail: string, orderedAt: string, ticketUrl: ?string, isScanned: bool}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(...[...$data, 'orderedAt' => CarbonImmutable::parse($data['orderedAt'])]);
    }

    /**
     * @return array{id: int, tier: string, amountCents: int, holderName: string, payerName: string, payerEmail: string, orderedAt: string, ticketUrl: ?string, isScanned: bool}
     */
    public function toArray(): array
    {
        return [...get_object_vars($this), 'orderedAt' => $this->orderedAt->toIso8601String()];
    }

    /**
     * @param  array<int, mixed>  $payments
     */
    private static function isRefunded(array $payments): bool
    {
        foreach ($payments as $payment) {
            if (is_array($payment) && (($payment['state'] ?? null) === 'Refunded' || ! empty($payment['refundOperations']))) {
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
