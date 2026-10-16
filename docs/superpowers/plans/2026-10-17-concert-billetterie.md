# Concert : billetterie HelloAsso — plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** page Concert (billetterie HelloAsso en lien ou iframe, « Mes billets ») et suivi des ventes et des entrées pour les responsables du stand Concert, en lisant l'API HelloAsso avec un cache court.

**Architecture:** aucun stockage en base. `HelloAssoClient` lit le formulaire Event et ses billets ; `App\Concert\ConcertTickets` met les réponses brutes (tableaux) en cache, les transforme en `ConcertTicket` (lecture seule) et sert la dernière liste connue si HelloAsso ne répond pas. Deux composants Livewire classiques ; la notification `/helloasso` vide le cache.

**Tech Stack:** Laravel 13, Livewire 4 (composants classiques), Flux UI Pro, Pest, client HTTP Laravel.

**Spec:** `docs/superpowers/specs/2026-10-17-concert-billetterie-design.md`

## Global Constraints

- Livewire classique : `App\Livewire\Concert\X.php` + `resources/views/livewire/concert/x.blade.php`, jamais de SFC.
- Tout texte d'interface passe par `lang/fr/concert.php` (ou les fichiers existants) ; noms de classes et variables en anglais.
- Jamais `env()` hors de `config/` ; les paramètres annuels du concert sont dans `config/concert.php` (versionné).
- Cache : `serializable_classes` vaut `false` → ne mettre en cache **que des tableaux**, jamais d'objets.
- Mobile-first, police Raleway (rien à changer), vérification à 360 px.
- Tests Pest unitaires/fonctionnels pour tout code ; `vendor/bin/pint --dirty --format agent` avant chaque commit.
- Commits en français, terminés par `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.

## Review Focus

- Payeur dont l'adresse diffère par la casse ou des espaces (« Camille@Example.org ») → ses billets s'affichent quand même (test Task 3).
- Billet remboursé après coup (paiement `Refunded` ou `refundOperations` non vide) → disparaît de « Mes billets » et des totaux (test Task 3).
- Plus de 100 billets → toutes les pages HelloAsso sont lues (test Task 2).
- HelloAsso en panne pendant la soirée → la gestion montre la dernière liste avec un avertissement, sans erreur 500 (tests Task 3 et Task 6).
- Notification d'une autre billetterie ou d'un formulaire inconnu → ignorée avec 200, sapins inchangés (test Task 4).

---

### Task 1: Type de stand « Concert » et droit `manage-concert`

**Files:**
- Modify: `app/Enums/StandType.php`, `lang/fr/stands.php`, `database/data/stands.php:32`, `app/Providers/AppServiceProvider.php:74-78`
- Create: `database/migrations/2026_10_17_000001_set_concert_stand_type.php`
- Test: `tests/Feature/Concert/ConcertAccessTest.php`

**Interfaces:**
- Produces: `StandType::Concert` (`'concert'`), gate `manage-concert`.

- [ ] **Step 1: Write the failing test** — `tests/Feature/Concert/ConcertAccessTest.php`

```php
<?php

use App\Enums\StandType;
use App\Models\Stand;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

test('managers of a concert stand manage the concert', function () {
    $manager = User::factory()->create();
    Stand::factory()->ofType(StandType::Concert)->create()->managers()->attach($manager);

    expect(Gate::forUser($manager)->allows('manage-concert'))->toBeTrue()
        ->and(Gate::forUser(User::factory()->create())->allows('manage-concert'))->toBeFalse();
});

test('managers of another stand do not manage the concert', function () {
    $manager = User::factory()->create();
    Stand::factory()->ofType(StandType::TreeOrder)->create()->managers()->attach($manager);

    expect(Gate::forUser($manager)->allows('manage-concert'))->toBeFalse();
});

test('the reference concert stand becomes a concert stand', function () {
    $stand = Stand::factory()->ofType(StandType::Generic)->create(['name' => 'Concert du Grand Orchestre de l\'école']);

    (require database_path('migrations/2026_10_17_000001_set_concert_stand_type.php'))->up();

    expect($stand->fresh()->type)->toBe(StandType::Concert)
        ->and(DB::table('stands')->where('type', 'concert')->count())->toBe(1);
});
```

- [ ] **Step 2: Run it** — `php artisan test --compact tests/Feature/Concert/ConcertAccessTest.php` → FAIL (`Concert` undefined).

- [ ] **Step 3: Implement**

`app/Enums/StandType.php` : ajouter `case Concert = 'concert';` après `TreeOrder`.
`lang/fr/stands.php` → `'types'` : ajouter `'concert' => 'Concert',`.
`database/data/stands.php:32` : `'Concert du Grand Orchestre de l\'école' => StandType::Concert,`.
`app/Providers/AppServiceProvider.php`, après le gate `manage-tree-orders` :

```php
        // Concert: ticket sales and entries read from the HelloAsso ticketing.
        Gate::define('manage-concert', fn (User $user): bool => $user->managedStands()
            ->where('type', StandType::Concert)
            ->exists());
```

`database/migrations/2026_10_17_000001_set_concert_stand_type.php` :

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The reference concert stand was imported as a generic stand: it now unlocks
 * the ticket follow-up. marche:import-stands never updates existing stands.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('stands')
            ->where('name', "Concert du Grand Orchestre de l'école")
            ->where('type', 'generic')
            ->update(['type' => 'concert']);
    }

    public function down(): void
    {
        DB::table('stands')
            ->where('name', "Concert du Grand Orchestre de l'école")
            ->where('type', 'concert')
            ->update(['type' => 'generic']);
    }
};
```

- [ ] **Step 4: Run** the test file → PASS ; `php artisan migrate --no-interaction`.
- [ ] **Step 5: Commit** — `git add` des fichiers ci-dessus, message « Concert : type de stand et droit manage-concert ».

---

### Task 2: Lecture de la billetterie dans `HelloAssoClient`

**Files:**
- Modify: `app/TreeOrders/HelloAsso/HelloAssoClient.php`
- Test: `tests/Feature/TreeOrders/HelloAssoClientTest.php` (fin de fichier ; réutilise `fakeHelloAsso()` et le `beforeEach` existants, organisation `ecole-steiner`)

**Interfaces:**
- Produces:
  - `HelloAssoClient::fetchEventForm(string $formSlug): array<string, mixed>` (corps JSON du formulaire public)
  - `HelloAssoClient::fetchEventItems(string $formSlug): list<array<string, mixed>>` (tous les billets, toutes pages)
  - Les deux lèvent `App\Exceptions\HelloAssoUnavailable`.

- [ ] **Step 1: Write the failing tests** (ajout en fin de `HelloAssoClientTest.php`)

```php
test('the event form is read', function () {
    fakeHelloAsso([
        'api.helloasso-sandbox.com/v5/organizations/ecole-steiner/forms/Event/concert/public' => Http::response(['title' => 'Orchestre des élèves']),
    ]);

    expect($this->client->fetchEventForm('concert'))->toBe(['title' => 'Orchestre des élèves']);
});

test('every page of the event tickets is read', function () {
    fakeHelloAsso([
        'api.helloasso-sandbox.com/v5/organizations/ecole-steiner/forms/Event/concert/items*' => Http::sequence()
            ->push(['data' => [['id' => 1], ['id' => 2]], 'pagination' => ['totalPages' => 2, 'pageIndex' => 1]])
            ->push(['data' => [['id' => 3]], 'pagination' => ['totalPages' => 2, 'pageIndex' => 2]]),
    ]);

    expect(array_column($this->client->fetchEventItems('concert'), 'id'))->toBe([1, 2, 3]);

    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/items')
        && $request['withDetails'] === 'true' && (int) $request['pageSize'] === 100 && (int) $request['pageIndex'] === 2);
});

test('an unknown ticketing makes the event calls fail', function () {
    fakeHelloAsso(['api.helloasso-sandbox.com/v5/*' => Http::response([], 404)]);

    $this->client->fetchEventItems('inconnu');
})->throws(HelloAssoUnavailable::class);
```

- [ ] **Step 2: Run** — `php artisan test --compact --filter="event" tests/Feature/TreeOrders/HelloAssoClientTest.php` → FAIL.

- [ ] **Step 3: Implement** — dans `HelloAssoClient`, après `fetchCheckout()` ; mettre à jour le docblock de la classe (« …checkout intents, and the event ticketing of the concert »).

```php
    /**
     * Public description of an event ticketing (title, date, place, prices).
     *
     * @return array<string, mixed>
     *
     * @throws HelloAssoUnavailable
     */
    public function fetchEventForm(string $formSlug): array
    {
        return $this->send(fn (PendingRequest $request) => $request->get($this->organizationUrl('forms/Event/'.$formSlug.'/public')));
    }

    /**
     * Every ticket sold by an event ticketing, with its payer and payments.
     *
     * @return list<array<string, mixed>>
     *
     * @throws HelloAssoUnavailable
     */
    public function fetchEventItems(string $formSlug): array
    {
        $items = [];
        $page = 1;

        do {
            $response = $this->send(fn (PendingRequest $request) => $request->get($this->organizationUrl('forms/Event/'.$formSlug.'/items'), [
                'withDetails' => 'true',
                'pageSize' => 100,
                'pageIndex' => $page,
            ]));

            $items = [...$items, ...array_values((array) ($response['data'] ?? []))];
            $totalPages = (int) ($response['pagination']['totalPages'] ?? 1);
            $page++;
        } while ($page <= $totalPages);

        return $items;
    }
```

- [ ] **Step 4: Run** `php artisan test --compact tests/Feature/TreeOrders/HelloAssoClientTest.php` → PASS.
- [ ] **Step 5: Commit** — « Concert : lecture de la billetterie HelloAsso ».

---

### Task 3: Billets du concert (`ConcertTicket`, `ConcertTickets`, `config/concert.php`)

**Files:**
- Create: `config/concert.php`, `app/Concert/ConcertTicket.php`, `app/Concert/TicketList.php`, `app/Concert/ConcertTickets.php`
- Modify: `tests/Pest.php` (helper `helloAssoTicket()`)
- Test: `tests/Unit/Concert/ConcertTicketTest.php`, `tests/Feature/Concert/ConcertTicketsTest.php`

**Interfaces:**
- Consumes: `HelloAssoClient::isConfigured()`, `fetchEventForm()`, `fetchEventItems()`.
- Produces:
  - `ConcertTicket::fromHelloAsso(array $item): ?ConcertTicket` (null si non valide) ; propriétés publiques en lecture seule `int $id, string $tier, int $amountCents, string $holderName, string $payerName, string $payerEmail, CarbonImmutable $orderedAt, ?string $ticketUrl, bool $isScanned`.
  - `TicketList` : `list<ConcertTicket> $tickets`, `bool $isStale` ; méthodes `forEmail(string $email): list<ConcertTicket>`, `summary(): array{tiers: list<array{tier: string, count: int, amountCents: int}>, count: int, amountCents: int, scanned: int}`.
  - `ConcertTickets` : `isConnected(): bool`, `event(): ?array{title: string, startsAt: ?CarbonImmutable, place: string, tiers: list<array{label: string, priceCents: int}>}`, `tickets(): TicketList` (lève `HelloAssoUnavailable` s'il n'y a ni réponse ni liste connue), `forget(): void`, `ticketingUrl(): ?string`, `widgetUrl(): ?string`, `isFormSlug(?string $slug): bool`.

- [ ] **Step 1: Config** — `config/concert.php` :

```php
<?php

/*
|--------------------------------------------------------------------------
| Concert
|--------------------------------------------------------------------------
|
| Billetterie HelloAsso du concert, à mettre à jour chaque année dans ce
| fichier (versionné). Les clés de l'API sont celles des sapins
| (trees.helloasso) : sans elles, la page affiche seulement la billetterie.
|
*/

return [

    // Identifiant de la billetterie (formulaire HelloAsso de type « Event »).
    'form_slug' => 'orchestre-des-eleves',

    // Adresse publique de la billetterie (lien et widget).
    'url' => 'https://www.helloasso-sandbox.com/associations/association-fictive-pour-tester-l-api/evenements/orchestre-des-eleves',

    // « link » : bouton vers la billetterie (nouvel onglet) ; « iframe » : widget intégré.
    'display' => 'iframe',

    // Durée (secondes) pendant laquelle la liste des billets est réutilisée sans interroger HelloAsso.
    'cache_seconds' => 60,

];
```

- [ ] **Step 2: Test helper** — remplacer la fonction d'exemple `something()` de `tests/Pest.php` par :

```php
/**
 * A ticket as returned by the HelloAsso items endpoint (withDetails=true).
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function helloAssoTicket(array $overrides = []): array
{
    return array_replace_recursive([
        'id' => 110885,
        'name' => 'Adulte',
        'tierId' => 24279,
        'amount' => 1000,
        'state' => 'Processed',
        'type' => 'Registration',
        'user' => ['firstName' => 'Camille', 'lastName' => 'Martin'],
        'payer' => ['firstName' => 'Camille', 'lastName' => 'Martin', 'email' => 'camille@example.org'],
        'order' => ['id' => 100115, 'date' => '2026-10-16T23:32:36+02:00', 'formSlug' => 'orchestre-des-eleves', 'formType' => 'Event'],
        'payments' => [['id' => 72031, 'state' => 'Authorized', 'refundOperations' => []]],
        'ticketUrl' => 'https://www.helloasso-sandbox.com/ticket?ticketId=110885',
        'qrCode' => 'MTEwODg1',
        'isScanned' => false,
    ], $overrides);
}
```

- [ ] **Step 3: Write failing unit test** — `tests/Unit/Concert/ConcertTicketTest.php`

```php
<?php

use App\Concert\ConcertTicket;
use App\Concert\TicketList;

test('a processed ticket is read', function () {
    $ticket = ConcertTicket::fromHelloAsso(helloAssoTicket(['user' => ['firstName' => 'Léo', 'lastName' => 'Martin'], 'isScanned' => true]));

    expect($ticket)
        ->id->toBe(110885)
        ->tier->toBe('Adulte')
        ->amountCents->toBe(1000)
        ->holderName->toBe('Léo Martin')
        ->payerName->toBe('Camille Martin')
        ->payerEmail->toBe('camille@example.org')
        ->ticketUrl->toBe('https://www.helloasso-sandbox.com/ticket?ticketId=110885')
        ->isScanned->toBeTrue()
        ->and($ticket->orderedAt->toIso8601String())->toBe('2026-10-16T23:32:36+02:00');
});

test('unprocessed and refunded tickets are ignored', function (array $overrides) {
    expect(ConcertTicket::fromHelloAsso(helloAssoTicket($overrides)))->toBeNull();
})->with([
    'not processed' => [['state' => 'Canceled']],
    'refunded payment' => [['payments' => [['state' => 'Refunded']]]],
    'refund operation' => [['payments' => [['refundOperations' => [['id' => 1]]]]]],
]);

test('the tickets of a payer are found whatever the case and spaces', function () {
    $list = new TicketList([
        ConcertTicket::fromHelloAsso(helloAssoTicket(['id' => 1, 'payer' => ['email' => 'Camille@Example.org ']])),
        ConcertTicket::fromHelloAsso(helloAssoTicket(['id' => 2, 'payer' => ['email' => 'autre@example.org']])),
    ], isStale: false);

    expect(array_map(fn (ConcertTicket $ticket) => $ticket->id, $list->forEmail(' camille@example.ORG')))->toBe([1]);
});

test('the summary counts tickets, amounts and entries by tier', function () {
    $list = new TicketList([
        ConcertTicket::fromHelloAsso(helloAssoTicket(['id' => 1, 'name' => 'Adulte', 'amount' => 1000, 'isScanned' => true])),
        ConcertTicket::fromHelloAsso(helloAssoTicket(['id' => 2, 'name' => 'Adulte', 'amount' => 1000])),
        ConcertTicket::fromHelloAsso(helloAssoTicket(['id' => 3, 'name' => 'Enfant', 'amount' => 500, 'isScanned' => true])),
    ], isStale: false);

    expect($list->summary())->toBe([
        'tiers' => [
            ['tier' => 'Adulte', 'count' => 2, 'amountCents' => 2000],
            ['tier' => 'Enfant', 'count' => 1, 'amountCents' => 500],
        ],
        'count' => 3,
        'amountCents' => 2500,
        'scanned' => 2,
    ]);
});
```

- [ ] **Step 4: Run** `php artisan test --compact tests/Unit/Concert` → FAIL.

- [ ] **Step 5: Implement** `app/Concert/ConcertTicket.php`

```php
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
```

`app/Concert/TicketList.php`

```php
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
```

- [ ] **Step 6: Run** `php artisan test --compact tests/Unit/Concert` → PASS.

- [ ] **Step 7: Write failing feature test** — `tests/Feature/Concert/ConcertTicketsTest.php`

```php
<?php

use App\Concert\ConcertTickets;
use App\Exceptions\HelloAssoUnavailable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
    config([
        'trees.helloasso' => ['client_id' => 'id', 'client_secret' => 'secret', 'organization_slug' => 'ecole', 'sandbox' => true],
        'concert' => ['form_slug' => 'orchestre', 'url' => 'https://www.helloasso.com/associations/ecole/evenements/orchestre', 'display' => 'iframe', 'cache_seconds' => 60],
    ]);
    $this->travelTo('2026-11-27 18:00:00');
});

function fakeTicketing(array $items, int $status = 200): void
{
    Http::fake([
        '*/oauth2/token' => Http::response(['access_token' => 'token', 'expires_in' => 1800]),
        '*/forms/Event/orchestre/items*' => Http::response(['data' => $items, 'pagination' => ['totalPages' => 1]], $status),
        '*/forms/Event/orchestre/public' => Http::response([
            'title' => 'Orchestre des élèves', 'startDate' => '2026-11-27T20:00:00+01:00',
            'place' => ['name' => 'Salle Odilon', 'address' => '', 'city' => ''],
            'tiers' => [['label' => 'Adulte', 'price' => 1000], ['label' => 'Enfant', 'price' => 500]],
        ], $status),
    ]);
}

test('only valid tickets are listed', function () {
    fakeTicketing([helloAssoTicket(['id' => 1]), helloAssoTicket(['id' => 2, 'state' => 'Canceled'])]);

    $list = app(ConcertTickets::class)->tickets();

    expect($list->tickets)->toHaveCount(1)->and($list->isStale)->toBeFalse();
});

test('tickets are read again only after the cache delay or a forget', function () {
    fakeTicketing([helloAssoTicket()]);
    $tickets = app(ConcertTickets::class);

    $tickets->tickets();
    $tickets->tickets();
    Http::assertSentCount(2); // token + items

    $this->travel(61)->seconds();
    $tickets->tickets();
    Http::assertSentCount(3);

    $tickets->forget();
    $tickets->tickets();
    Http::assertSentCount(4);
});

test('the last known list is served, marked stale, when HelloAsso fails', function () {
    fakeTicketing([helloAssoTicket()]);
    $tickets = app(ConcertTickets::class);
    $tickets->tickets();

    $this->travel(61)->seconds();
    fakeTicketing([], 503);

    $list = $tickets->tickets();
    expect($list->tickets)->toHaveCount(1)->and($list->isStale)->toBeTrue();
});

test('without any known list a failure is reported to the caller', function () {
    fakeTicketing([], 503);

    app(ConcertTickets::class)->tickets();
})->throws(HelloAssoUnavailable::class);

test('the event is read with its date, place and prices', function () {
    fakeTicketing([]);

    $event = app(ConcertTickets::class)->event();

    expect($event['title'])->toBe('Orchestre des élèves')
        ->and($event['startsAt']->toIso8601String())->toBe('2026-11-27T20:00:00+01:00')
        ->and($event['place'])->toBe('Salle Odilon')
        ->and($event['tiers'])->toBe([['label' => 'Adulte', 'priceCents' => 1000], ['label' => 'Enfant', 'priceCents' => 500]]);
});

test('the event is null when HelloAsso fails', function () {
    fakeTicketing([], 503);

    expect(app(ConcertTickets::class)->event())->toBeNull();
});

test('the ticketing is connected only with API keys and a form', function () {
    $tickets = app(ConcertTickets::class);
    expect($tickets->isConnected())->toBeTrue()
        ->and($tickets->widgetUrl())->toBe('https://www.helloasso.com/associations/ecole/evenements/orchestre/widget');

    config(['trees.helloasso.client_id' => null]);
    expect($tickets->isConnected())->toBeFalse();

    config(['concert.display' => 'link']);
    expect($tickets->widgetUrl())->toBeNull()
        ->and($tickets->ticketingUrl())->toBe('https://www.helloasso.com/associations/ecole/evenements/orchestre');
});
```

- [ ] **Step 8: Run** `php artisan test --compact tests/Feature/Concert/ConcertTicketsTest.php` → FAIL.

- [ ] **Step 9: Implement** `app/Concert/ConcertTickets.php`

```php
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
```

- [ ] **Step 10: Run** `php artisan test --compact tests/Unit/Concert tests/Feature/Concert` → PASS.
- [ ] **Step 11: Commit** — « Concert : billets lus chez HelloAsso avec cache ».

---

### Task 4: La notification HelloAsso vide le cache du concert

**Files:**
- Modify: `app/Http/Controllers/TreeOrders/PaymentNotification.php`
- Test: `tests/Feature/TreeOrders/HelloAssoPaymentTest.php` (fin de fichier ; réutilise `fakeCheckout`, `paidCheckout`, `$this->order`)

**Interfaces:**
- Consumes: `ConcertTickets::isFormSlug()`, `ConcertTickets::forget()`.

- [ ] **Step 1: Write the failing tests**

```php
test('a notification of the concert ticketing refreshes the tickets', function () {
    config(['concert.form_slug' => 'orchestre']);
    Cache::put('concert.tickets', ['fetched_at' => now()->getTimestamp(), 'items' => []]);

    $this->postJson('/helloasso', ['eventType' => 'Order', 'data' => ['formType' => 'Event', 'formSlug' => 'orchestre']])->assertOk();

    expect(Cache::has('concert.tickets'))->toBeFalse()
        ->and($this->order->fresh()->isPaid())->toBeFalse();
});

test('a notification of another event is ignored', function () {
    config(['concert.form_slug' => 'orchestre']);
    Cache::put('concert.tickets', ['fetched_at' => now()->getTimestamp(), 'items' => []]);

    $this->postJson('/helloasso', ['eventType' => 'Order', 'data' => ['formType' => 'Event', 'formSlug' => 'autre']])->assertOk();

    expect(Cache::has('concert.tickets'))->toBeTrue();
    Http::assertNothingSent();
});
```

(Ajouter `use Illuminate\Support\Facades\Cache;` en tête si absent.)

- [ ] **Step 2: Run** `php artisan test --compact --filter="concert ticketing|another event" tests/Feature/TreeOrders/HelloAssoPaymentTest.php` → FAIL.

- [ ] **Step 3: Implement** — `PaymentNotification::__invoke` reçoit `ConcertTickets $concertTickets` et commence par :

```php
        // Event ticketing (the concert): the tickets are read again on next display.
        if ($request->input('data.formType') === 'Event') {
            if ($concertTickets->isFormSlug($request->input('data.formSlug'))) {
                $concertTickets->forget();
            }

            return response()->noContent(200);
        }
```

Docblock de la classe : ajouter « A notification of the concert ticketing only refreshes its tickets. »

- [ ] **Step 4: Run** `php artisan test --compact tests/Feature/TreeOrders tests/Feature/Concert` → PASS.
- [ ] **Step 5: Commit** — « Concert : la notification HelloAsso actualise les billets ».

---

### Task 5: Page `/concert` (billetterie et « Mes billets »)

**Files:**
- Create: `app/Livewire/Concert/Show.php`, `resources/views/livewire/concert/show.blade.php`, `lang/fr/concert.php`
- Modify: `routes/web.php` (après `tombola`), `resources/views/layouts/app/sidebar.blade.php` (après « Sapins »), `lang/fr/navigation.php`
- Test: `tests/Feature/Concert/ShowTest.php`

**Interfaces:**
- Consumes: `ConcertTickets` (Task 3), gate `manage-concert` (Task 1).
- Produces: routes `concert.index` (`/concert`) ; `concert.manage` est créée en Task 6 — le bouton « Gérer les billets » n'est rendu que si la route existe : `@can('manage-concert') @if (Route::has('concert.manage'))`.

- [ ] **Step 1: Translations** — `lang/fr/concert.php`

```php
<?php

return [
    'title' => 'Concert',
    'heading' => 'Concert',
    'when' => ':date à :time',
    'prices' => 'Tarifs',
    'book' => 'Réserver mes billets',
    'widget_title' => 'Billetterie HelloAsso du concert',
    'not_open' => 'La billetterie n’est pas encore ouverte.',
    'unavailable' => 'Les billets sont momentanément indisponibles. Réessayez dans quelques minutes.',
    'stale' => 'HelloAsso ne répond pas : la liste affichée est la dernière connue.',

    'mine' => [
        'heading' => 'Mes billets',
        'empty' => 'Aucun billet acheté avec l’adresse :email. Pour les retrouver ici, utilisez la même adresse dans la billetterie.',
        'holder' => 'Au nom de :name',
        'ordered' => 'Acheté le :date',
        'open' => 'Ouvrir mon billet',
        'scanned' => 'Contrôlé',
    ],

    'manage' => [
        'title' => 'Billets du concert',
        'link' => 'Gérer les billets',
        'refresh' => 'Actualiser',
        'not_connected' => 'Sans les clés de l’API HelloAsso, le suivi des billets se fait dans le back-office HelloAsso.',
        'tier' => 'Tarif',
        'count' => 'Billets',
        'amount' => 'Recette',
        'total' => 'Total',
        'entries' => 'Entrées contrôlées : :scanned / :count',
        'search' => 'Nom ou adresse électronique',
        'spectators' => '{0} Aucun spectateur|{1} 1 spectateur|[2,*] :count spectateurs',
        'paid_by' => 'Payé par :name',
        'empty' => 'Aucun billet.',
    ],
];
```

`lang/fr/navigation.php` : ajouter `'concert' => 'Concert',` après `'trees'`.

- [ ] **Step 2: Write the failing test** — `tests/Feature/Concert/ShowTest.php`

```php
<?php

use App\Livewire\Concert\Show;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    Cache::flush();
    config([
        'marche.timezone' => 'Europe/Paris',
        'trees.helloasso' => ['client_id' => 'id', 'client_secret' => 'secret', 'organization_slug' => 'ecole', 'sandbox' => true],
        'concert' => ['form_slug' => 'orchestre', 'url' => 'https://www.helloasso.com/associations/ecole/evenements/orchestre', 'display' => 'iframe', 'cache_seconds' => 60],
    ]);
    Http::fake([
        '*/oauth2/token' => Http::response(['access_token' => 'token', 'expires_in' => 1800]),
        '*/forms/Event/orchestre/public' => Http::response(['title' => 'Orchestre des élèves', 'startDate' => '2026-11-27T20:00:00+01:00', 'place' => ['name' => 'Salle Odilon'], 'tiers' => [['label' => 'Adulte', 'price' => 1000]]]),
        '*/forms/Event/orchestre/items*' => Http::response(['data' => [
            helloAssoTicket(['id' => 1, 'user' => ['firstName' => 'Léo', 'lastName' => 'Martin'], 'payer' => ['email' => 'Camille@example.org'], 'isScanned' => true]),
            helloAssoTicket(['id' => 2, 'user' => ['firstName' => 'Sacha', 'lastName' => 'Autre'], 'payer' => ['email' => 'autre@example.org'], 'ticketUrl' => 'https://ticket/autre']),
        ], 'pagination' => ['totalPages' => 1]]),
    ]);

    $this->user = User::factory()->create(['email' => 'camille@example.org']);
    $this->actingAs($this->user);
});

test('guests are sent to the login page', function () {
    auth()->logout();

    $this->get(route('concert.index'))->assertRedirect(route('login'));
});

test('the page shows the event and the ticketing widget', function () {
    $this->get(route('concert.index'))
        ->assertOk()
        ->assertSee('Orchestre des élèves')
        ->assertSee('Salle Odilon')
        ->assertSee("10\u{00A0}€", false)
        ->assertSee('src="https://www.helloasso.com/associations/ecole/evenements/orchestre/widget"', false);
});

test('the ticketing opens with a link in link mode', function () {
    config(['concert.display' => 'link']);

    Livewire::test(Show::class)
        ->assertSee(__('concert.book'))
        ->assertDontSee('/widget');
});

test('a family sees its own tickets only', function () {
    Livewire::test(Show::class)
        ->assertSee('Léo Martin')
        ->assertSee(__('concert.mine.scanned'))
        ->assertSee('https://www.helloasso-sandbox.com/ticket?ticketId=110885')
        ->assertDontSee('Sacha Autre')
        ->assertDontSee('https://ticket/autre');
});

test('a family without tickets is told to use the same address', function () {
    $this->actingAs(User::factory()->create(['email' => 'nouveau@example.org']));

    Livewire::test(Show::class)->assertSee(__('concert.mine.empty', ['email' => 'nouveau@example.org']));
});

test('without API keys only the ticketing is shown', function () {
    config(['trees.helloasso.client_id' => null]);

    Livewire::test(Show::class)
        ->assertSee('/widget')
        ->assertDontSee(__('concert.mine.heading'));

    Http::assertNothingSent();
});

test('HelloAsso being down keeps the ticketing and shows a message', function () {
    Http::fake(['*' => Http::response([], 503)]);

    Livewire::test(Show::class)
        ->assertSee(__('concert.unavailable'))
        ->assertSee('/widget');
});
```

- [ ] **Step 3: Run** → FAIL.

- [ ] **Step 4: Implement** `app/Livewire/Concert/Show.php`

```php
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
```

`resources/views/livewire/concert/show.blade.php`

```blade
@php
    use App\Support\Money;

    $timezone = config('marche.timezone');
@endphp

<section class="mx-auto w-full max-w-3xl space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <flux:heading size="xl" level="1" class="font-display">{{ $this->event['title'] ?? __('concert.heading') }}</flux:heading>
            @if ($this->event && $this->event['startsAt'])
                @php $startsAt = $this->event['startsAt']->setTimezone($timezone)->locale(app()->getLocale()); @endphp
                <flux:subheading>{{ __('concert.when', ['date' => $startsAt->translatedFormat('l j F Y'), 'time' => $startsAt->format('G\hi')]) }}</flux:subheading>
            @endif
            @if (filled($this->event['place'] ?? null))
                <p class="mt-1 text-sm text-zinc-600">{{ $this->event['place'] }}</p>
            @endif
        </div>

        @can('manage-concert')
            @if (Route::has('concert.manage'))
                <flux:button size="sm" icon="ticket" :href="route('concert.manage')" wire:navigate>{{ __('concert.manage.link') }}</flux:button>
            @endif
        @endcan
    </div>

    @if (filled($this->event['tiers'] ?? []))
        <div class="flex flex-wrap gap-2 rounded-2xl border border-brand-line bg-brand-sand/60 p-4 text-sm">
            <span class="font-semibold">{{ __('concert.prices') }} :</span>
            @foreach ($this->event['tiers'] as $tier)
                <span>{{ $tier['label'] }} <span class="font-semibold tabular-nums">{{ Money::format($tier['priceCents']) }}</span>@if (! $loop->last) ·@endif</span>
            @endforeach
        </div>
    @endif

    @if ($isConnected)
        <div class="space-y-3">
            <flux:heading size="lg" level="2">{{ __('concert.mine.heading') }}</flux:heading>

            @if ($this->ticketList === null)
                <flux:callout variant="warning" icon="exclamation-triangle" :heading="__('concert.unavailable')" />
            @else
                @if ($this->ticketList->isStale)
                    <flux:callout variant="warning" icon="exclamation-triangle" :heading="__('concert.stale')" />
                @endif

                @forelse ($this->myTickets as $ticket)
                    <article class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-brand-line bg-white p-4" wire:key="ticket-{{ $ticket->id }}">
                        <div class="min-w-0">
                            <p class="font-semibold">{{ $ticket->tier }} <span class="font-normal tabular-nums text-zinc-600">{{ Money::format($ticket->amountCents) }}</span></p>
                            <p class="text-sm text-zinc-600">{{ __('concert.mine.holder', ['name' => $ticket->holderName]) }}</p>
                            <p class="text-sm text-zinc-500">{{ __('concert.mine.ordered', ['date' => $ticket->orderedAt->setTimezone($timezone)->locale(app()->getLocale())->translatedFormat('j F Y')]) }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if ($ticket->isScanned)
                                <flux:badge color="green" size="sm" icon="check">{{ __('concert.mine.scanned') }}</flux:badge>
                            @endif
                            @if ($ticket->ticketUrl)
                                <flux:button size="sm" variant="primary" icon="ticket" :href="$ticket->ticketUrl" target="_blank" rel="noopener">{{ __('concert.mine.open') }}</flux:button>
                            @endif
                        </div>
                    </article>
                @empty
                    <p class="text-sm text-zinc-600">{{ __('concert.mine.empty', ['email' => auth()->user()->email]) }}</p>
                @endforelse
            @endif
        </div>
    @endif

    @if ($widgetUrl)
        {{-- The widget sends its height: the iframe grows with it, no inner scroll bar. --}}
        <div class="-mx-4 sm:mx-0"
            x-data="{ height: 750 }"
            x-on:message.window="if (/(^|\.)helloasso(-sandbox)?\.com$/.test(new URL($event.origin).hostname) && Number($event.data?.height) > height) { height = Number($event.data.height) }">
            <iframe src="{{ $widgetUrl }}" title="{{ __('concert.widget_title') }}"
                allow="payment 'self' https://paymenthub.helloassopay.com https://helloasso.com" allowtransparency="true"
                class="block w-full border-0" x-bind:style="{ height: height + 'px' }" style="height: 750px"></iframe>
        </div>
    @elseif ($ticketingUrl)
        <flux:button variant="primary" icon:trailing="arrow-top-right-on-square" class="w-full" :href="$ticketingUrl" target="_blank" rel="noopener">{{ __('concert.book') }}</flux:button>
    @else
        <flux:callout icon="information-circle" :heading="__('concert.not_open')" />
    @endif
</section>
```

`routes/web.php` : `use App\Livewire\Concert\Show as ConcertShow;` puis, après `Route::livewire('tombola', …)` :

```php
    Route::livewire('concert', ConcertShow::class)->name('concert.index');
```

`resources/views/layouts/app/sidebar.blade.php`, après l'entrée « Sapins » :

```blade
                <flux:sidebar.item icon="musical-note" :href="route('concert.index')" :current="request()->routeIs('concert.*')" wire:navigate>
                    {{ __('navigation.concert') }}
                </flux:sidebar.item>
```

- [ ] **Step 5: Run** `php artisan test --compact tests/Feature/Concert` → PASS.
- [ ] **Step 6: Mobile check** — `npm run build`, capture 360 px de `/concert` (méthode de la mémoire « tutorial-screenshots-howto », personne fictive, données supprimées ensuite).
- [ ] **Step 7: Commit** — « Concert : page de la billetterie et Mes billets ».

---

### Task 6: Page `/concert/gestion` (ventes, entrées, spectateurs)

**Files:**
- Create: `app/Livewire/Concert/Manage.php`, `resources/views/livewire/concert/manage.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Concert/ManageTest.php`

**Interfaces:**
- Consumes: `ConcertTickets`, `TicketList::summary()`, gate `manage-concert`, traductions `concert.manage.*` (Task 5).
- Produces: route `concert.manage` (`/concert/gestion`, `can:manage-concert`).

- [ ] **Step 1: Write the failing test** — `tests/Feature/Concert/ManageTest.php`

```php
<?php

use App\Enums\StandType;
use App\Livewire\Concert\Manage;
use App\Models\Stand;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    Cache::flush();
    config([
        'trees.helloasso' => ['client_id' => 'id', 'client_secret' => 'secret', 'organization_slug' => 'ecole', 'sandbox' => true],
        'concert' => ['form_slug' => 'orchestre', 'url' => 'https://www.helloasso.com/associations/ecole/evenements/orchestre', 'display' => 'iframe', 'cache_seconds' => 60],
    ]);
    Http::fake([
        '*/oauth2/token' => Http::response(['access_token' => 'token', 'expires_in' => 1800]),
        '*/forms/Event/orchestre/items*' => Http::response(['data' => [
            helloAssoTicket(['id' => 1, 'name' => 'Adulte', 'amount' => 1000, 'user' => ['firstName' => 'Léo', 'lastName' => 'Martin'], 'isScanned' => true]),
            helloAssoTicket(['id' => 2, 'name' => 'Enfant', 'amount' => 500, 'user' => ['firstName' => 'Sacha', 'lastName' => 'Dubois'], 'payer' => ['firstName' => 'Alex', 'lastName' => 'Dubois', 'email' => 'alex@example.org']]),
        ], 'pagination' => ['totalPages' => 1]]),
    ]);

    $this->manager = User::factory()->create();
    Stand::factory()->ofType(StandType::Concert)->create()->managers()->attach($this->manager);
    $this->actingAs($this->manager);
});

test('only concert managers reach the page', function () {
    $this->get(route('concert.manage'))->assertOk();

    $this->actingAs(User::factory()->create())->get(route('concert.manage'))->assertForbidden();
});

test('sales are summed up by tier with the entries', function () {
    Livewire::test(Manage::class)
        ->assertSeeInOrder(['Adulte', '1', "10\u{00A0}€", 'Enfant', '1', "5\u{00A0}€", __('concert.manage.total'), '2', "15\u{00A0}€"], false)
        ->assertSee(__('concert.manage.entries', ['scanned' => 1, 'count' => 2]));
});

test('spectators are listed and searched by name or address', function () {
    Livewire::test(Manage::class)
        ->assertSee('Léo Martin')
        ->assertSee('Sacha Dubois')
        ->set('search', 'ALEX@example')
        ->assertSee('Sacha Dubois')
        ->assertDontSee('Léo Martin')
        ->set('search', 'léo')
        ->assertSee('Léo Martin')
        ->assertDontSee('Sacha Dubois');
});

test('refreshing reads the tickets again', function () {
    Livewire::test(Manage::class)->call('refresh');

    Http::assertSentCount(3); // token, items, items again
});

test('the last known list is shown with a warning when HelloAsso fails', function () {
    Livewire::test(Manage::class);
    $this->travel(61)->seconds();
    Http::fake(['*' => Http::response([], 503)]);

    Livewire::test(Manage::class)
        ->assertSee(__('concert.stale'))
        ->assertSee('Léo Martin');
});

test('without API keys the managers are sent to the HelloAsso back office', function () {
    config(['trees.helloasso.client_id' => null]);

    Livewire::test(Manage::class)->assertSee(__('concert.manage.not_connected'));
    Http::assertNothingSent();
});
```

- [ ] **Step 2: Run** → FAIL.

- [ ] **Step 3: Implement** `app/Livewire/Concert/Manage.php`

```php
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
```

`resources/views/livewire/concert/manage.blade.php`

```blade
@php
    use App\Support\Money;

    $timezone = config('marche.timezone');
@endphp

<section class="mx-auto w-full max-w-3xl space-y-6" wire:poll.30s>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:button size="sm" variant="ghost" icon="arrow-left" :href="route('concert.index')" wire:navigate>{{ __('concert.title') }}</flux:button>
            <flux:heading size="xl" level="1" class="mt-2 font-display">{{ __('concert.manage.title') }}</flux:heading>
        </div>
        @if ($isConnected)
            <flux:button size="sm" icon="arrow-path" wire:click="refresh">{{ __('concert.manage.refresh') }}</flux:button>
        @endif
    </div>

    @if (! $isConnected)
        <flux:callout icon="information-circle" :heading="__('concert.manage.not_connected')" />
    @elseif ($this->ticketList === null)
        <flux:callout variant="warning" icon="exclamation-triangle" :heading="__('concert.unavailable')" />
    @else
        @if ($this->ticketList->isStale)
            <flux:callout variant="warning" icon="exclamation-triangle" :heading="__('concert.stale')" />
        @endif

        @php $summary = $this->ticketList->summary(); @endphp

        <div class="overflow-hidden rounded-2xl border border-brand-line bg-white">
            <table class="w-full text-sm">
                <thead class="bg-brand-sand/60 text-start">
                    <tr>
                        <th class="px-4 py-2 text-start">{{ __('concert.manage.tier') }}</th>
                        <th class="px-4 py-2 text-end">{{ __('concert.manage.count') }}</th>
                        <th class="px-4 py-2 text-end">{{ __('concert.manage.amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($summary['tiers'] as $tier)
                        <tr class="border-t border-brand-line">
                            <td class="px-4 py-2">{{ $tier['tier'] }}</td>
                            <td class="px-4 py-2 text-end tabular-nums">{{ $tier['count'] }}</td>
                            <td class="px-4 py-2 text-end tabular-nums">{{ Money::format($tier['amountCents']) }}</td>
                        </tr>
                    @endforeach
                    <tr class="border-t border-brand-line font-semibold">
                        <td class="px-4 py-2">{{ __('concert.manage.total') }}</td>
                        <td class="px-4 py-2 text-end tabular-nums">{{ $summary['count'] }}</td>
                        <td class="px-4 py-2 text-end tabular-nums">{{ Money::format($summary['amountCents']) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="flex items-center gap-2 rounded-2xl bg-brand-ink px-4 py-3 font-semibold text-white">
            <flux:icon name="qr-code" variant="mini" />
            {{ __('concert.manage.entries', ['scanned' => $summary['scanned'], 'count' => $summary['count']]) }}
        </p>

        <div class="space-y-3">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('concert.manage.search')" :aria-label="__('concert.manage.search')" clearable />
            <p class="text-sm text-zinc-600">{{ trans_choice('concert.manage.spectators', count($this->spectators), ['count' => count($this->spectators)]) }}</p>

            @forelse ($this->spectators as $ticket)
                <article class="flex flex-wrap items-start justify-between gap-2 rounded-2xl border border-brand-line bg-white p-4" wire:key="spectator-{{ $ticket->id }}">
                    <div class="min-w-0">
                        <p class="font-semibold">{{ $ticket->holderName }}</p>
                        <p class="text-sm">{{ $ticket->tier }} · <span class="tabular-nums">{{ Money::format($ticket->amountCents) }}</span></p>
                        <p class="text-sm text-zinc-600">{{ __('concert.manage.paid_by', ['name' => $ticket->payerName]) }} · <span class="break-all">{{ $ticket->payerEmail }}</span></p>
                        <p class="text-sm text-zinc-500">{{ __('concert.mine.ordered', ['date' => $ticket->orderedAt->setTimezone($timezone)->locale(app()->getLocale())->translatedFormat('j F Y')]) }}</p>
                    </div>
                    @if ($ticket->isScanned)
                        <flux:badge color="green" size="sm" icon="check">{{ __('concert.mine.scanned') }}</flux:badge>
                    @endif
                </article>
            @empty
                <p class="text-sm text-zinc-600">{{ __('concert.manage.empty') }}</p>
            @endforelse
        </div>
    @endif
</section>
```

`routes/web.php` : `use App\Livewire\Concert\Manage as ConcertManage;` puis, après la route `concert.index` :

```php
    Route::livewire('concert/gestion', ConcertManage::class)->middleware('can:manage-concert')->name('concert.manage');
```

- [ ] **Step 4: Run** `php artisan test --compact tests/Feature/Concert` → PASS (si `assertSeeInOrder` bute sur des chiffres répétés ailleurs dans la page, restreindre aux libellés et montants : `['Adulte', "10\u{00A0}€", 'Enfant', "5\u{00A0}€", __('concert.manage.total'), "15\u{00A0}€"]`).
- [ ] **Step 5: Mobile check** 360 px de `/concert/gestion`.
- [ ] **Step 6: Commit** — « Concert : suivi des billets et des entrées pour les responsables ».

---

### Task 7: RGPD, purge et documentation

**Files:**
- Modify: `lang/fr/privacy.php` (liste `items` de la section des données collectées, après « Les sapins de Noël »), `app/Console/Commands/Purge.php`, `CLAUDE.md` (nouvelle section « Concert » après « Sapins de Noël »)
- Test: `tests/Feature/PrivacyTest.php`, test de purge existant (`grep -rln "marche:purge" tests`)

- [ ] **Step 1: Write the failing tests**

Dans `tests/Feature/PrivacyTest.php` :

```php
test('the privacy page explains the concert tickets', function () {
    $this->get(route('privacy'))->assertOk()->assertSee('Le concert');
});
```

Dans le fichier de test de la purge (après une purge confirmée existante, même procédure de confirmation) :

```php
test('the purge forgets the concert tickets', function () {
    Cache::put('concert.tickets', ['fetched_at' => 1, 'items' => []]);

    $this->artisan('marche:purge')
        ->expectsQuestion(__('commands.purge.confirm', ['word' => __('commands.purge.word')]), __('commands.purge.word'))
        ->assertSuccessful();

    expect(Cache::has('concert.tickets'))->toBeFalse();
});
```

- [ ] **Step 2: Run** → FAIL.

- [ ] **Step 3: Implement**

`lang/fr/privacy.php`, après l'entrée « Les sapins de Noël » :

```php
                'Le concert' => 'Les billets du concert sont achetés dans la billetterie HelloAsso. Le site les lit chez HelloAsso pour vous montrer vos billets (retrouvés grâce à votre adresse électronique) et pour permettre aux responsables du concert et à l’administrateur de suivre les ventes et les entrées : nom des porteurs de billets, nom et adresse électronique du payeur, tarif, contrôle à l’entrée. Ces informations ne sont pas enregistrées par le site : elles sont seulement gardées quelques minutes en mémoire.',
```

`app/Console/Commands/Purge.php`, après la suppression des instantanés :

```php
        // Concert tickets are only cached, whatever the cache store.
        app(ConcertTickets::class)->forget();
```

(+ `use App\Concert\ConcertTickets;`)

`CLAUDE.md`, nouvelle section après « Sapins de Noël » :

```markdown
## Concert

- Billets vendus dans la billetterie HelloAsso de l'année (formulaire « Event ») : billets à QR code et contrôle à l'entrée avec l'application HelloAsso. Paramètres dans `config/concert.php` (versionné : `form_slug`, `url`, `display` `link`/`iframe`, `cache_seconds`) ; clés API partagées avec les sapins.
- Page `/concert` (connectés) : informations, billetterie, « Mes billets » (rapprochés par l'adresse électronique du payeur, lien vers le billet HelloAsso, badge « Contrôlé »). Sans clés API : billetterie seule.
- Stand de type « Concert » et droit `manage-concert` : `/concert/gestion` (billets et recette par tarif, entrées contrôlées, spectateurs avec recherche, actualisation toutes les 30 s).
- Lecture en direct avec cache (`App\Concert\ConcertTickets`, tableaux seulement) : aucune donnée de billet en base, rien dans les instantanés ; la dernière liste est servie si HelloAsso ne répond pas. Une notification `/helloasso` de la billetterie vide le cache ; la purge aussi.
```

- [ ] **Step 4: Run** `php artisan test --compact tests/Feature/PrivacyTest.php tests/Feature/Concert` + le test de purge → PASS ; `vendor/bin/pint --dirty --format agent`.
- [ ] **Step 5: Commit** — « Concert : données personnelles, purge et documentation ».

---

## Fin

- Lancer `php artisan test --compact tests/Feature/Concert tests/Unit/Concert tests/Feature/TreeOrders`, puis demander à l'utilisateur la suite complète.
- Rappeler : pousser, déployer, et remplacer `config/concert.php` (`url`, `form_slug`) par la billetterie réelle de l'école avant l'ouverture.
