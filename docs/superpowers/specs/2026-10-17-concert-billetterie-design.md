# Concert : billetterie HelloAsso — conception

Date : 17 octobre 2026. Source : échanges avec le porteur du projet et lecture de la billetterie de test
`https://www.helloasso-sandbox.com/associations/association-fictive-pour-tester-l-api/evenements/orchestre-des-eleves`
par l'API HelloAsso v5.

## Objectif

Les billets du concert (« Concert du Grand Orchestre de l'école ») sont vendus par une billetterie HelloAsso,
qui émet les billets à QR code et permet leur contrôle à l'entrée avec l'application mobile HelloAsso. Le site :

1. présente le concert et donne accès à la billetterie (lien ou widget en iframe) ;
2. montre à chaque famille ses billets (« Mes billets ») ;
3. donne aux responsables du stand Concert le suivi des ventes et des entrées ;
4. reste à jour grâce aux notifications HelloAsso et à un cache court.

L'API Checkout utilisée pour les sapins ne crée pas de billet : la vente se fait toujours dans la billetterie
HelloAsso, le site ne fait que lire.

## Choix

- **Une seule billetterie**, définie dans `config/concert.php` (versionné, à mettre à jour chaque année).
- **Lecture en direct avec cache (approche A)** : aucune table, aucune donnée de spectateur stockée sur le serveur
  hors cache. Rien à purger ni à ajouter aux instantanés.
- **Billet présenté par un lien** vers le billet HelloAsso (`ticketUrl`, PDF avec QR code) ; pas de bibliothèque
  de QR code.

## Données HelloAsso utilisées

- Formulaire : `GET /v5/organizations/{org}/forms/Event/{formSlug}/public` → `title`, `startDate`, `endDate`,
  `place.name`, `place.address`, `place.city`, `url`, `tiers[]` (`id`, `label`, `price` en centimes).
- Billets : `GET /v5/organizations/{org}/forms/Event/{formSlug}/items?withDetails=true&pageSize=100&pageIndex=n`
  (pagination jusqu'à épuisement de `pagination.totalPages`). Par billet : `id`, `name` (tarif), `tierId`, `amount`,
  `state`, `user.firstName|lastName` (porteur), `payer.firstName|lastName|email`, `order.id`, `order.date`,
  `ticketUrl`, `isScanned`, `payments[].state`, `payments[].refundOperations`.
- Les clés API sont celles des sapins (`trees.helloasso.*`) ; elles doivent avoir le droit FormAdmin ou
  OrganizationAdmin (vérifié en sandbox).

Un billet est **valide** si `state` vaut `Processed` et qu'aucun de ses paiements n'est remboursé
(`state` `Refunded` ou `refundOperations` non vide). Les billets non valides sont ignorés partout.

## Configuration (`config/concert.php`)

| Clé | Exemple | Rôle |
| --- | --- | --- |
| `form_slug` | `orchestre-des-eleves` | Identifiant de la billetterie (formulaire de type Event) |
| `url` | adresse publique de la billetterie | Lien et widget, y compris sans clés API |
| `display` | `link` ou `iframe` | Billetterie ouverte dans un nouvel onglet ou intégrée (`{url}/widget`) |
| `cache_seconds` | `60` | Durée de fraîcheur des billets en cache |

Sans `form_slug` ni `url`, la page Concert indique que la billetterie n'est pas encore ouverte.

## Composants

- `App\Enums\StandType::Concert` (`concert`), libellé « Concert ». Migration : le stand de référence
  « Concert du Grand Orchestre de l'école » passe de `generic` à `concert` (comme pour les sapins) ;
  `database/data/stands.php` est mis à jour.
- Gate `manage-concert` : responsables d'un stand de type Concert (l'administrateur par `Gate::before`).
- `HelloAssoClient` : `fetchEventForm(string $slug)` et `fetchEventItems(string $slug)` (toutes les pages).
- `App\Concert\ConcertTickets` (service) :
  - `isConnected()` : clés API configurées et `form_slug` renseigné ;
  - `event()` : informations du formulaire (cache 1 h) ;
  - `tickets()` : billets valides, en cache `cache_seconds` avec `Cache::flexible` (une valeur périmée reste servie
    pendant le recalcul ; si HelloAsso ne répond pas, la dernière liste connue est servie et signalée) ;
  - `forEmail(string $email)` : billets dont le payeur a cette adresse (insensible à la casse et aux espaces) ;
  - `summary()` : par tarif, nombre et recette ; total ; entrées contrôlées / billets ;
  - `forget()` : vide le cache.
  - Les billets sont des objets en lecture seule (`App\Concert\ConcertTicket`).
- Notification `/helloasso` (et `/sapins/paiement/notification`) : si `data.formType` vaut `Event` et
  `data.formSlug` est celui du concert, `ConcertTickets::forget()` puis réponse 200 ; le traitement des sapins est
  inchangé dans les autres cas.
- Livewire classique :
  - `App\Livewire\Concert\Show` + `resources/views/livewire/concert/show.blade.php` (`/concert`, connectés) ;
  - `App\Livewire\Concert\Manage` + `resources/views/livewire/concert/manage.blade.php` (`/concert/gestion`,
    `manage-concert`).
- Menu : entrée « Concert » pour toutes les personnes connectées.

## Pages

### `/concert`

- Titre, date et heure (fuseau `MARCHE_TIMEZONE`), lieu, tarifs (depuis HelloAsso ; sans clés API, seulement le
  titre générique et la billetterie).
- Billetterie : bouton « Réserver mes billets » (nouvel onglet) en mode `link`, widget pleine largeur avec hauteur
  ajustée par les messages de `helloasso.com` en mode `iframe` (même mécanisme que les sapins).
- « Mes billets » (si connecté à l'API) : pour chaque billet du payeur, tarif, porteur, date d'achat, bouton
  « Ouvrir mon billet » (`ticketUrl`, nouvel onglet) et badge « Contrôlé » si `isScanned`. Message si aucun billet :
  « Aucun billet acheté avec l'adresse … » (rappel : utiliser la même adresse que sur le site).
- Bouton « Gérer les billets » pour `manage-concert`.

### `/concert/gestion`

- Synthèse : par tarif, billets et recette ; total ; « Entrées contrôlées : 42 / 118 ».
- Liste des spectateurs : porteur, tarif, payeur et son courriel, date d'achat, état du contrôle ; recherche par
  nom ou courriel ; tri par nom du porteur.
- Actualisation automatique toutes les 30 s (`wire:poll`), bouton « Actualiser » (vide le cache).
- Avertissement si la liste affichée est la dernière connue faute de réponse de HelloAsso.
- Sans clés API : message expliquant que le suivi se fait dans le back-office HelloAsso.

## Erreurs

- HelloAsso indisponible sans liste en cache : message « Billets momentanément indisponibles » sur les deux pages,
  la billetterie reste accessible ; l'erreur est rapportée (`report`).
- Billetterie introuvable (404, mauvais `form_slug`) : même message, erreur rapportée.

## RGPD

- Aucune donnée de billet n'est enregistrée en base ; seul le cache applicatif la garde quelques minutes.
- Page « Données personnelles » : les billets du concert sont lus chez HelloAsso ; nom des porteurs, nom et courriel
  du payeur visibles par les responsables du Concert et l'administrateur ; chaque famille voit ses propres billets.
- La purge annuelle vide le cache du concert.

## Tests (Pest, API simulée avec `Http::fake`)

- Client : lecture du formulaire, pagination des billets.
- Service : billets valides seulement (remboursés et non traités exclus), cache, `forget`, liste périmée servie si
  HelloAsso échoue, `forEmail` insensible à la casse, synthèse par tarif et entrées contrôlées.
- Notification : billetterie du concert → cache vidé, 200 ; autre formulaire → ignoré ; sapins inchangés.
- Pages : accès connectés / `manage-concert` (403 sinon), « Mes billets » limité au payeur, mode `link` / `iframe`,
  mode sans clés, message d'indisponibilité.
- Migration du type de stand, gate, purge.

## Hors périmètre

- Liste imprimable des spectateurs, écrans du mode d'emploi, plusieurs billetteries, QR code affiché sur le site.
