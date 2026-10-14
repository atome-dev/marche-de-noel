# Commande de sapins — plan d'implémentation

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** commande en ligne des sapins Nordmann pour les personnes connectées, payée par HelloAsso (API Checkout) ou par chèque, avec gestion, synthèse producteur et retrait pour les responsables du stand de type `TreeOrder`.

**Architecture:** un espace de noms `App\TreeOrders` porte la logique (catalogue lu dans `config('marche.trees')`, service de commande, synthèse, PDF, client HelloAsso). Les écrans sont des composants Livewire classiques (`App\Livewire\TreeOrders\*` + `resources/views/livewire/tree-orders/*`). Les retours et notifications HelloAsso sont des contrôleurs invocables qui relisent toujours le paiement chez HelloAsso avant de marquer une commande payée.

**Tech Stack:** Laravel 13, Livewire 4 (classique), Flux UI Pro, Tailwind, Pest, dompdf (`barryvdh/laravel-dompdf`), openspout, client HTTP Laravel.

**Spec:** `docs/superpowers/specs/2026-10-14-commande-sapins-design.md`

## Global Constraints

- Livewire classique uniquement : classe `App\Livewire\…` + vue `resources/views/livewire/…`, jamais de SFC.
- Aucun `env()` hors de `config/` ; tout paramètre dans `config/marche.php` et `.env.example`.
- Tous les textes via `lang/fr/trees.php` (et `navigation.php`, `dashboard.php`, `privacy.php`).
- Polices sans serif uniquement (Raleway à l'écran, DejaVu Sans dans les PDF).
- Mobile d'abord : cartes sur mobile, tableau à partir de `sm`/`md`.
- Montants en centimes, calculés côté serveur depuis la configuration.
- Aucune nouvelle dépendance Composer ou npm.
- Nouvelles tables dans `SnapshotStore::TABLES` et `Purge::TABLES`.
- Pest pour tout code ; `vendor/bin/pint --dirty --format agent` avant chaque commit.
- Courriels envoyés de façon synchrone (`Mail::to()->send()`), comme le reste du site.

## Review Focus

1. Notification HelloAsso reçue deux fois (ou retour navigateur + notification) : une seule mise à « payée », un seul courriel.
2. Montant HelloAsso différent du total de la commande, ou paiement non « Authorized » : la commande reste en attente.
3. `TREE_SIZES` mal formé (ligne à 3 champs, prix non numérique) : la ligne est ignorée sans faire tomber la page.
4. Une famille qui manipule l'identifiant d'une autre commande (paiement, annulation, PDF) : 403.
5. Date limite : le jour même jusqu'à 23:59:59 heure de Paris, fermé à 00:00 le lendemain.

Chaque point a son test dans la tâche qui possède le code (tâches 1, 4, 6, 7, 3).

---

## File Structure

- `config/marche.php` — sections `trees` et `helloasso`.
- `app/TreeOrders/TreeSize.php` — objet valeur d'une taille.
- `app/TreeOrders/TreeCatalog.php` — tailles, date limite, retrait.
- `app/TreeOrders/TreeOrdering.php` — création, annulation, chèque, paiement HelloAsso, retrait, courriels.
- `app/TreeOrders/TreeOrdersSummary.php` — synthèse producteur.
- `app/TreeOrders/TreeOrderDocuments.php` — PDF du bon de commande et du bon de retrait.
- `app/TreeOrders/HelloAsso/HelloAssoClient.php` — jeton, création et lecture d'un *checkout intent*.
- `app/TreeOrders/HelloAsso/HelloAssoCheckout.php` — démarrer un paiement, confirmer un paiement.
- `app/Enums/TreeOrderStatus.php`, `app/Enums/TreePaymentMethod.php`.
- `app/Exceptions/TreeOrdersClosed.php`, `app/Exceptions/HelloAssoUnavailable.php`.
- `app/Models/TreeOrder.php`, `app/Models/TreeOrderLine.php` + factories.
- `app/Policies/TreeOrderPolicy.php`.
- `app/Mail/TreeOrderPlaced.php`, `app/Mail/TreeOrderPaid.php` + `resources/views/mail/tree-order-*.blade.php`.
- `app/Http/Controllers/TreeOrders/` — `DownloadDocument`, `PaymentReturn`, `PaymentAborted`, `PaymentNotification`, `ExportTreeOrders`, `PrintPickupList`.
- `app/Livewire/TreeOrders/` — `OrderForm`, `Manage`, `Summary`, `Pickup` + vues `resources/views/livewire/tree-orders/`.
- `resources/views/tree-orders/` — `order-form-pdf`, `pickup-slip-pdf`, `summary-pdf`, `pickup-print`, `partials/manage-tabs`.
- `lang/fr/trees.php`.
- Migrations `2026_10_14_000001_create_tree_orders_tables.php`, `2026_10_14_000002_set_tree_order_stand_type.php`.

---

### Task 1: Configuration et catalogue

**Files:**
- Modify: `config/marche.php`, `.env.example`
- Create: `app/TreeOrders/TreeSize.php`, `app/TreeOrders/TreeCatalog.php`, `lang/fr/trees.php`
- Test: `tests/Unit/TreeCatalogTest.php`

**Interfaces:**
- Produces:
  - `TreeSize(string $key, string $name, string $height, int $priceCents)` (readonly).
  - `TreeCatalog::sizes(): array<string, TreeSize>`, `find(string $key): ?TreeSize`, `deadline(): ?CarbonImmutable` (fin de journée, `marche.timezone`), `isClosed(): bool`, `pickupDate(): ?CarbonImmutable`, `maxQuantity(): int`.
  - `config('marche.trees.*')` : `sizes` (list<string>), `max_quantity`, `deadline`, `pickup.date|hours|place`, `cheque.payee|drop_points`, `producer`, `description`, `refresh_seconds` réutilise `marche.planning.refresh_seconds`.
  - `config('marche.helloasso.*')` : `client_id`, `client_secret`, `organization_slug`, `sandbox` (bool).

- [ ] Step 1 : tests unitaires (parse des tailles « clé|nom|hauteur|prix », prix décimal `27.5` → 2750, ligne mal formée ignorée, `find`, date limite incluse jusqu'à 23:59:59 Paris, fermée le lendemain à 00:00, pas de date limite → jamais fermé).
- [ ] Step 2 : vérifier l'échec.
- [ ] Step 3 : config, `.env.example` (valeurs 2026 d'exemple, secrets vides), classes, fichier de langue complet.
- [ ] Step 4 : tests verts, Pint, commit.

### Task 2: Données, droits, purge et instantanés

**Files:**
- Create: migrations, `app/Models/TreeOrder.php`, `app/Models/TreeOrderLine.php`, factories, `app/Enums/TreeOrderStatus.php`, `app/Enums/TreePaymentMethod.php`, `app/Policies/TreeOrderPolicy.php`
- Modify: `app/Providers/AppServiceProvider.php` (gate `manage-tree-orders`), `app/Console/Commands/Purge.php`, `app/Snapshots/SnapshotStore.php`, `database/data/stands.php`, `app/Models/User.php` (`treeOrders()`)
- Test: `tests/Feature/TreeOrders/TreeOrderModelTest.php`, mise à jour de `PurgeCommandTest` et `SnapshotCommandTest`

**Interfaces:**
- Produces:
  - `TreeOrder` : `number` (accessor `S-0042`), `status(): TreeOrderStatus`, `isPaid()`, `isCancelled()`, `isPickedUp()`, `lines()`, `user()`, `scopeWithStatus(Builder, TreeOrderStatus)`, `scopeSearch(Builder, string)`.
  - `TreeOrderStatus` : `Pending`, `Paid`, `PickedUp`, `Cancelled`, `Expired` avec `label()` et `color()`.
  - `TreePaymentMethod` : `HelloAsso = 'helloasso'`, `Cheque = 'cheque'` avec `label()`.
  - Factory `TreeOrderFactory` : états `paid(TreePaymentMethod)`, `cancelled()`, `pickedUp()`, `guest()`, `withLine(string $key, int $quantity, int $unitPriceCents)`.
  - Gate `manage-tree-orders` ; policy `view`, `update` (propriétaire).

- [ ] Steps : tests (statuts, recherche par nom et numéro, gate, policy, purge vide les tables, instantané les contient, migration du type de stand), implémentation, tests verts, Pint, commit.

### Task 3: PDF du bon de commande et du bon de retrait

**Files:**
- Create: `app/TreeOrders/TreeOrderDocuments.php`, `resources/views/tree-orders/order-form-pdf.blade.php`, `resources/views/tree-orders/pickup-slip-pdf.blade.php`, `app/Http/Controllers/TreeOrders/DownloadDocument.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/TreeOrders/TreeOrderDocumentsTest.php`

**Interfaces:**
- Produces: `TreeOrderDocuments::orderForm(TreeOrder): \Barryvdh\DomPDF\PDF`, `pickupSlip(TreeOrder): PDF`, `filename(TreeOrder, string $document): string`. Routes `trees.documents.order-form` et `trees.documents.pickup-slip` (`sapins/commandes/{treeOrder}/bon-de-commande.pdf`, `…/bon-de-retrait.pdf`).

- [ ] Steps : tests (propriétaire télécharge, autre famille 403, responsable autorisé, bon de retrait 404 si non payée), implémentation, Pint, commit.

### Task 4: Service de commande et courriels

**Files:**
- Create: `app/TreeOrders/TreeOrdering.php`, `app/Exceptions/TreeOrdersClosed.php`, `app/Mail/TreeOrderPlaced.php`, `app/Mail/TreeOrderPaid.php`, vues `mail/tree-order-placed`, `mail/tree-order-paid`
- Test: `tests/Feature/TreeOrders/TreeOrderingTest.php`

**Interfaces:**
- Produces (`TreeOrdering`) :
  - `placeForUser(User $user, array<string,int> $quantities, string $phone): TreeOrder` — refusé après la date limite (`TreeOrdersClosed`).
  - `placeForGuest(User $manager, array $quantities, string $name, string $phone, ?string $email, bool $chequeReceived): TreeOrder` — permis après la date limite.
  - `cancel(TreeOrder $order, bool $byManager = false): void`.
  - `markChequeReceived(TreeOrder, User $manager): void`, `unmarkCheque(TreeOrder): void`.
  - `markPaidByHelloAsso(TreeOrder, int $helloAssoOrderId): bool` — idempotent, verrou en base.
  - `setPickedUp(TreeOrder, bool $pickedUp): void`.
  - Quantités invalides → `InvalidArgumentException`.
- Courriels : `TreeOrderPlaced` à la création non payée (si courriel), `TreeOrderPaid` à chaque passage non payée → payée, PDF en pièce jointe ; les erreurs d'envoi sont rapportées (`report()`) sans bloquer.

- [ ] Steps : tests (création, lignes recopiées, total, refus après la date limite, tiers après la date limite, annulation, refus d'annuler une commande payée côté famille, chèque et courriel, double paiement HelloAsso → un seul courriel), implémentation, Pint, commit.

### Task 5: Client HelloAsso

**Files:**
- Create: `app/TreeOrders/HelloAsso/HelloAssoClient.php`, `app/Exceptions/HelloAssoUnavailable.php`
- Test: `tests/Feature/TreeOrders/HelloAssoClientTest.php` (`Http::fake`)

**Interfaces:**
- Produces: `isConfigured(): bool`, `createCheckout(TreeOrder, string $returnUrl, string $backUrl, string $errorUrl): array{id:int, redirectUrl:string}`, `fetchCheckout(int $id): array`. Jeton `client_credentials` sur `/oauth2/token`, mis en cache (`helloasso.token`) jusqu'à `expires_in - 60` s. Bases : `https://api.helloasso-sandbox.com` / `https://api.helloasso.com`. Erreur réseau ou HTTP → `HelloAssoUnavailable`.

- [ ] Steps : tests (jeton demandé une seule fois pour deux appels, corps du *checkout intent* en centimes avec métadonnées, URL sandbox/production, erreur → exception), implémentation, Pint, commit.

### Task 6: Parcours de paiement HelloAsso

**Files:**
- Create: `app/TreeOrders/HelloAsso/HelloAssoCheckout.php`, `app/Http/Controllers/TreeOrders/PaymentReturn.php`, `PaymentAborted.php`, `PaymentNotification.php`
- Modify: `routes/web.php`, `bootstrap/app.php` (exception CSRF de la notification)
- Test: `tests/Feature/TreeOrders/HelloAssoPaymentTest.php`

**Interfaces:**
- Produces: `HelloAssoCheckout::start(TreeOrder): string` (URL de redirection ; refuse commande payée, annulée ou date limite passée) ; `confirm(TreeOrder): bool` (relit le paiement, vérifie métadonnées, montant total et état `Authorized`, puis `markPaidByHelloAsso`). Routes `trees.payment.return|back|error` (auth) et `trees.payment.notification` (POST, sans auth ni CSRF).

- [ ] Steps : tests (retour validé → payée + courriel, montant faux → en attente, paiement non autorisé, notification idempotente, notification d'une commande inconnue → 200 sans effet, autre famille sur le retour → 403), implémentation, Pint, commit.

### Task 7: Page `/sapins` des familles

**Files:**
- Create: `app/Livewire/TreeOrders/OrderForm.php`, `resources/views/livewire/tree-orders/order-form.blade.php`
- Modify: `routes/web.php`, `resources/views/layouts/app/sidebar.blade.php`, `resources/views/dashboard.blade.php`, `lang/fr/navigation.php`, `lang/fr/dashboard.php`
- Test: `tests/Feature/TreeOrders/OrderFormTest.php`

**Interfaces:**
- Consumes: `TreeCatalog`, `TreeOrdering`, `HelloAssoCheckout`, `HelloAssoClient::isConfigured()`.
- Produces: route `trees.index`. Actions Livewire `increment(key)`, `decrement(key)`, `orderByCheque()`, `orderAndPay()`, `pay(int $orderId)`, `cancel(int $orderId)`.

- [ ] Steps : tests (affichage des tailles et prix, commande par chèque, validation aucun sapin / téléphone manquant, paiement en ligne redirige, bouton masqué sans identifiants, commandes fermées après la date limite, annulation, impossible d'agir sur la commande d'un autre), implémentation, Pint, commit.

### Task 8: Gestion des commandes (responsables)

**Files:**
- Create: `app/Livewire/TreeOrders/Manage.php`, vue, `resources/views/tree-orders/partials/manage-tabs.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/TreeOrders/ManageTest.php`

**Interfaces:**
- Produces: route `trees.manage`. Actions `markCheque`, `unmarkCheque`, `cancelOrder`, `createGuestOrder` ; filtres `status`, `search` (`#[Url]`).

- [ ] Steps : tests (accès réservé, filtre, recherche, pointage chèque + courriel, annulation, saisie pour un tiers avant et après la date limite), implémentation, Pint, commit.

### Task 9: Synthèse producteur et exports

**Files:**
- Create: `app/TreeOrders/TreeOrdersSummary.php`, `app/Livewire/TreeOrders/Summary.php`, vue, `app/Http/Controllers/TreeOrders/ExportTreeOrders.php`, `resources/views/tree-orders/summary-pdf.blade.php`
- Test: `tests/Feature/TreeOrders/SummaryTest.php`

**Interfaces:**
- Produces: `TreeOrdersSummary::rows(): list<array{key:string,label:string,height:string,quantity:int,amount_cents:int}>`, `totalQuantity()`, `totalAmountCents()`, `pendingCount()`, `pendingAmountCents()`, `paidOrders(): Collection<TreeOrder>`. Routes `trees.summary`, `trees.summary.xlsx`, `trees.summary.pdf`.

- [ ] Steps : tests (seules les commandes payées non annulées comptent, totaux, exports), implémentation, Pint, commit.

### Task 10: Retrait (jour J)

**Files:**
- Create: `app/Livewire/TreeOrders/Pickup.php`, vue, `app/Http/Controllers/TreeOrders/PrintPickupList.php`, `resources/views/tree-orders/pickup-print.blade.php`
- Test: `tests/Feature/TreeOrders/PickupTest.php`

**Interfaces:**
- Produces: routes `trees.pickup`, `trees.pickup.print`. Action `togglePickup(int $orderId)` ; compteur ; `wire:poll` selon `marche.planning.refresh_seconds`.

- [ ] Steps : tests (seules les payées, pointage et annulation du retrait, compteur, impression), implémentation, Pint, commit.

### Task 11: Documentation et données personnelles

**Files:**
- Modify: `CLAUDE.md`, `lang/fr/privacy.php`
- Test: `tests/Feature/PrivacyTest.php` (la rubrique sapins et HelloAsso apparaît)

- [ ] Steps : CLAUDE.md (section « Sapins », exception HelloAsso à « Pas de paiement », retrait des points à définir), page RGPD (données des commandes, HelloAsso), test, commit.

## Notes de déploiement

- HelloAsso n'accepte que des URL de retour en HTTPS : le parcours réel se vérifie sur le serveur (sandbox), pas en local.
- L'URL de notification (`https://…/sapins/paiement/notification`) se déclare dans le back-office HelloAsso de l'association ; il n'y en a qu'une par organisation.
- Passer `HELLOASSO_SANDBOX=false` et les identifiants de production avant l'ouverture des commandes.
