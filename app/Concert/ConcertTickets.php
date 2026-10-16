<?php

namespace App\Concert;

use App\Exceptions\HelloAssoUnavailable;
use App\TreeOrders\HelloAsso\HelloAssoClient;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Tickets of the concert ticketing, read from HelloAsso and kept in the
 * cache for a short time; nothing is stored in the database. Only the fields
 * shown are cached (arrays only). When HelloAsso fails, the last known list
 * is served and HelloAsso is not asked again before the cache delay.
 */
class ConcertTickets
{
    private const TICKETS_KEY = 'concert.tickets';

    private const TICKETS_FAILED_KEY = 'concert.tickets.failed';

    private const EVENT_KEY = 'concert.event';

    private const EVENT_FAILED_KEY = 'concert.event.failed';

    /** The last known list is kept one day at most. */
    private const KEEP_SECONDS = 86400;

    /** The event description (title, date, prices) is read again every hour. */
    private const EVENT_SECONDS = 3600;

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
        /** @var array{fetched_at: int, stale: bool, tickets: list<array<string, mixed>>}|null $cached */
        $cached = Cache::get(self::TICKETS_KEY);

        if ($cached !== null && $cached['fetched_at'] > now()->getTimestamp() - $this->cacheSeconds()) {
            return $this->toList($cached);
        }

        if ($cached === null && Cache::has(self::TICKETS_FAILED_KEY)) {
            throw new HelloAssoUnavailable('HelloAsso failed a moment ago: not asked again yet.');
        }

        try {
            $tickets = array_map(
                fn (ConcertTicket $ticket): array => $ticket->toArray(),
                array_values(array_filter(array_map(
                    fn (mixed $item): ?ConcertTicket => is_array($item) ? ConcertTicket::fromHelloAsso($item) : null,
                    $this->client->fetchEventItems((string) config('concert.form_slug')),
                ))),
            );
        } catch (HelloAssoUnavailable $exception) {
            if ($cached === null) {
                Cache::put(self::TICKETS_FAILED_KEY, true, $this->cacheSeconds());

                throw $exception;
            }

            report($exception);

            // The last list stays shown; HelloAsso is asked again after the cache delay.
            $cached = [...$cached, 'fetched_at' => now()->getTimestamp(), 'stale' => true];
            Cache::put(self::TICKETS_KEY, $cached, self::KEEP_SECONDS);

            return $this->toList($cached);
        }

        $cached = ['fetched_at' => now()->getTimestamp(), 'stale' => false, 'tickets' => $tickets];
        Cache::put(self::TICKETS_KEY, $cached, self::KEEP_SECONDS);

        return $this->toList($cached);
    }

    /**
     * @return array{title: string, startsAt: ?CarbonImmutable, place: string, tiers: list<array{label: string, priceCents: int}>}|null
     */
    public function event(): ?array
    {
        /** @var array{fetched_at: int, form: array<string, mixed>}|null $cached */
        $cached = Cache::get(self::EVENT_KEY);

        if (($cached === null || $cached['fetched_at'] <= now()->getTimestamp() - self::EVENT_SECONDS) && ! Cache::has(self::EVENT_FAILED_KEY)) {
            try {
                $cached = ['fetched_at' => now()->getTimestamp(), 'form' => $this->client->fetchEventForm((string) config('concert.form_slug'))];
                Cache::put(self::EVENT_KEY, $cached, self::KEEP_SECONDS);
            } catch (HelloAssoUnavailable $exception) {
                report($exception);
                Cache::put(self::EVENT_FAILED_KEY, true, $this->cacheSeconds());
            }
        }

        return $cached === null ? null : $this->toEvent($cached['form']);
    }

    public function forget(): void
    {
        Cache::forget(self::TICKETS_KEY);
        Cache::forget(self::TICKETS_FAILED_KEY);
        Cache::forget(self::EVENT_KEY);
        Cache::forget(self::EVENT_FAILED_KEY);
    }

    private function cacheSeconds(): int
    {
        return max(1, (int) config('concert.cache_seconds', 60));
    }

    /**
     * @param  array{stale: bool, tickets: list<array<string, mixed>>}  $cached
     */
    private function toList(array $cached): TicketList
    {
        return new TicketList(array_map(ConcertTicket::fromArray(...), $cached['tickets']), $cached['stale']);
    }

    /**
     * @param  array<string, mixed>  $form
     * @return array{title: string, startsAt: ?CarbonImmutable, place: string, tiers: list<array{label: string, priceCents: int}>}
     */
    private function toEvent(array $form): array
    {
        $place = is_array($form['place'] ?? null) ? $form['place'] : [];

        return [
            'title' => (string) ($form['title'] ?? ''),
            'startsAt' => filled($form['startDate'] ?? null) ? CarbonImmutable::parse($form['startDate']) : null,
            'place' => implode(', ', array_filter([$place['name'] ?? null, $place['address'] ?? null, $place['city'] ?? null], 'filled')),
            'tiers' => array_values(array_map(
                fn (array $tier): array => ['label' => (string) ($tier['label'] ?? ''), 'priceCents' => (int) ($tier['price'] ?? 0)],
                array_filter((array) ($form['tiers'] ?? []), 'is_array'),
            )),
        ];
    }
}
