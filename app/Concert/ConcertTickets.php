<?php

namespace App\Concert;

use App\Exceptions\HelloAssoUnavailable;
use App\TreeOrders\HelloAsso\HelloAssoClient;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Tickets of the concert ticketing, read from HelloAsso and kept in the
 * cache for a short time; nothing is stored in the database. The raw answers
 * are cached (arrays only), the last one serving when HelloAsso fails.
 */
class ConcertTickets
{
    private const TICKETS_KEY = 'concert.tickets';

    private const EVENT_KEY = 'concert.event';

    /** The last known list is kept one day at most. */
    private const KEEP_SECONDS = 86400;

    public function __construct(private HelloAssoClient $client) {}

    public function isConnected(): bool
    {
        return $this->client->isConfigured() && filled(config('concert.form_slug'));
    }

    public function isFormSlug(?string $slug): bool
    {
        return filled($slug) && $slug === config('concert.form_slug');
    }

    public function ticketingUrl(): ?string
    {
        $url = config('concert.url');

        return filled($url) ? rtrim((string) $url, '/') : null;
    }

    public function widgetUrl(): ?string
    {
        $url = $this->ticketingUrl();

        return $url !== null && config('concert.display') === 'iframe' ? $url.'/widget' : null;
    }

    /**
     * @throws HelloAssoUnavailable when HelloAsso fails and no list is known
     */
    public function tickets(): TicketList
    {
        /** @var array{fetched_at: int, items: list<array<string, mixed>>}|null $cached */
        $cached = Cache::get(self::TICKETS_KEY);
        $isFresh = $cached !== null && $cached['fetched_at'] > now()->getTimestamp() - (int) config('concert.cache_seconds', 60);

        if (! $isFresh) {
            try {
                $cached = ['fetched_at' => now()->getTimestamp(), 'items' => $this->client->fetchEventItems((string) config('concert.form_slug'))];
                Cache::put(self::TICKETS_KEY, $cached, self::KEEP_SECONDS);
            } catch (HelloAssoUnavailable $exception) {
                if ($cached === null) {
                    throw $exception;
                }

                report($exception);

                return $this->toList($cached['items'], isStale: true);
            }
        }

        return $this->toList($cached['items'], isStale: false);
    }

    /**
     * @return array{title: string, startsAt: ?CarbonImmutable, place: string, tiers: list<array{label: string, priceCents: int}>}|null
     */
    public function event(): ?array
    {
        try {
            $form = Cache::remember(self::EVENT_KEY, 3600, fn (): array => $this->client->fetchEventForm((string) config('concert.form_slug')));
        } catch (HelloAssoUnavailable $exception) {
            report($exception);

            return null;
        }

        return [
            'title' => (string) ($form['title'] ?? ''),
            'startsAt' => filled($form['startDate'] ?? null) ? CarbonImmutable::parse($form['startDate']) : null,
            'place' => implode(', ', array_filter([$form['place']['name'] ?? null, $form['place']['address'] ?? null, $form['place']['city'] ?? null], 'filled')),
            'tiers' => array_map(fn (array $tier): array => ['label' => (string) $tier['label'], 'priceCents' => (int) $tier['price']], array_values((array) ($form['tiers'] ?? []))),
        ];
    }

    public function forget(): void
    {
        Cache::forget(self::TICKETS_KEY);
        Cache::forget(self::EVENT_KEY);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function toList(array $items, bool $isStale): TicketList
    {
        return new TicketList(array_values(array_filter(array_map(ConcertTicket::fromHelloAsso(...), $items))), $isStale);
    }
}
