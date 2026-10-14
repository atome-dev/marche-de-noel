# Commande de sapins de Noël — conception

Date : 14 octobre 2026. Source : `docs/Bon de Commande Sapin Nordmann 2025.pdf` et échanges avec le porteur du projet.

## Objectif

Remplacer le bon de commande papier des sapins Nordmann par une commande en ligne pour toutes les personnes connectées, avec deux modes de règlement : paiement en ligne via HelloAsso (API Checkout) ou bon de commande imprimé et chèque déposé à l'école. Les responsables du stand de type « Commande de sapins » pointent les chèques, saisissent les commandes papier, transmettent la synthèse au producteur et gèrent le retrait.

## Règles fonctionnelles

- Seules les personnes connectées commandent. Une personne peut passer **plusieurs commandes indépendantes**, chacune payée séparément ; une commande n'est jamais modifiée après sa création (seulement annulée tant qu'elle n'est pas payée).
- Catalogue : un produit (sapin Nordmann) en plusieurs tailles, chacune avec une clé, un nom, une hauteur et un prix, définis dans `.env`. Chaque ligne de commande recopie libellé, hauteur et prix au moment de la commande.
- Quantité par taille : entier de 0 à `TREE_MAX_QUANTITY` ; au moins un sapin par commande.
- Coordonnées : nom et courriel repris du profil (lecture seule) ; téléphone obligatoire, pré-rempli depuis le profil, sinon saisi et enregistré dans la commande uniquement.
- Statuts (calculés) :
  - **en attente de règlement** : ni payée ni annulée ;
  - **payée** : `paid_at` renseigné ;
  - **retirée** : `picked_up_at` renseigné ;
  - **annulée** : `cancelled_at` renseigné ;
  - **non prise en compte** : en attente après la date limite.
- Avant la date limite (`TREE_ORDER_DEADLINE`, fuseau `MARCHE_TIMEZONE`, incluse jusqu'à 23:59:59) : la famille crée des commandes, relance le paiement HelloAsso, télécharge le bon de commande et annule une commande en attente.
- Après la date limite : plus de création, d'annulation ni de paiement HelloAsso par les familles. Les commandes en attente sont exclues de la synthèse et du retrait, mais restent visibles (grisées) pour les responsables. Les responsables peuvent encore pointer un chèque (la commande est réintégrée) et saisir une commande pour un tiers.
- Un paiement HelloAsso n'est jamais modifiable à la main. Un remboursement se fait sur HelloAsso, puis le responsable annule la commande sur le site.

## Configuration (`config/marche.php` → `trees`, documentée dans `.env.example`)

| Variable | Exemple | Rôle |
| --- | --- | --- |
| `TREE_SIZES` | `lutins\|Le sapin des lutins\|80/100 cm\|27,moyen\|Le sapin moyen\|125/150 cm\|35,grand\|Le grand sapin\|150/175 cm\|45,chatelains\|Le sapin des châtelains\|200/250 cm\|65` | Tailles : clé, nom, hauteur, prix en euros |
| `TREE_MAX_QUANTITY` | `10` | Quantité maximale par taille |
| `TREE_ORDER_DEADLINE` | `2026-11-29` | Date limite des commandes |
| `TREE_PICKUP_DATE` | `2026-12-05` | Date du retrait |
| `TREE_PICKUP_HOURS` | `11h00 – 13h00` | Horaire du retrait |
| `TREE_PICKUP_PLACE` | `École Steiner, parking du haut, entrée 21 route de Bièvres` | Lieu du retrait |
| `TREE_CHEQUE_PAYEE` | `École Steiner de Verrières-le-Buisson` | Ordre du chèque |
| `TREE_CHEQUE_DROP_POINTS` | `dans l'urne prévue à cet effet à l'accueil,lors du Marché de Noël sur le stand couronnes - Sapins` | Lieux de dépôt du chèque |
| `TREE_PRODUCER` | `Cruard Boudinel` | Producteurs |
| `TREE_DESCRIPTION` | texte | Présentation du Nordmann |
| `HELLOASSO_CLIENT_ID` | vide | Identifiant API |
| `HELLOASSO_CLIENT_SECRET` | vide | Secret API (jamais versionné) |
| `HELLOASSO_ORGANIZATION_SLUG` | vide | Slug de l'association |
| `HELLOASSO_SANDBOX` | `true` | `true` : `api.helloasso-sandbox.com` ; `false` : `api.helloasso.com` |

Si les identifiants HelloAsso sont absents, le bouton « Payer en ligne » est masqué et seul le chèque est proposé.

## Données

Tables fonctionnelles, ajoutées à `SnapshotStore::TABLES`, effacées par la purge et décrites dans la page « Données personnelles ».

`tree_orders` :
- `id`, `number` (unique, lisible, ex. `S-0042`, dérivé de l'identifiant) ;
- `user_id` (nullable, `nullOnDelete`) : vide pour une saisie au nom d'un tiers ;
- `customer_name`, `customer_phone`, `customer_email` (nullable pour un tiers) ;
- `payment_method` (nullable : `helloasso` ou `cheque`) ;
- `total_cents` ;
- `paid_at`, `paid_by` (nullable : responsable qui a pointé le chèque) ;
- `helloasso_checkout_id`, `helloasso_order_id` (nullable) ;
- `picked_up_at`, `cancelled_at` (nullable) ;
- `created_by` (utilisateur qui a saisi la commande) ;
- horodatages.

`tree_order_lines` : `id`, `tree_order_id` (cascade), `size_key`, `label`, `height`, `unit_price_cents`, `quantity`.

Les montants sont toujours en centimes, calculés côté serveur depuis la configuration.

## Stand et droits

- `database/data/stands.php` : « Sapins du Morvan » passe en `StandType::TreeOrder`. Une migration de données met à jour le type de ce stand s'il existe déjà en base sous le type générique.
- Autorisation `manage-tree-orders` : responsables d'un stand de type `TreeOrder` et administrateur.
- Policy `TreeOrderPolicy` : une famille ne voit, ne paie, n'annule et ne télécharge que ses propres commandes ; `manage-tree-orders` voit tout.
- Le téléphone n'est visible que par les responsables du stand et l'administrateur.

## Écrans

### Familles — `/sapins` (`App\Livewire\TreeOrders\OrderForm`)

Entrée « Sapins de Noël » dans le menu et tuile sur l'accueil.

1. En-tête : présentation, producteur, date limite bien visible, retrait (date, horaire, lieu).
2. Une carte par taille : nom, hauteur, prix, sélecteur − / + ; total en direct.
3. Coordonnées.
4. Deux boutons : « Payer en ligne (HelloAsso) » (crée la commande puis redirige) et « Payer par chèque » (crée la commande et propose le bon de commande PDF).
5. Après la date limite : message « Les commandes sont closes ».

Bloc « Mes commandes » sous le formulaire : une carte par commande (numéro, lignes, total, badge de statut).
- En attente, avant la date limite : « Payer en ligne », « Bon de commande (PDF) », « Annuler » (confirmation).
- Payée : « Bon de retrait (PDF) ». La carte sert aussi de bon de retrait sur téléphone : numéro en grand, tailles, quantités, date, horaire et lieu du retrait.

### Responsables — `/sapins/gestion` (`App\Livewire\TreeOrders\Manage`, `can:manage-tree-orders`)

Trois onglets.

1. **Commandes** : recherche par nom ou numéro ; filtres (en attente, payées, à retirer, retirées, annulées, non prises en compte) ; cartes sur mobile, tableau sur ordinateur. Actions : « Chèque reçu » / « Annuler le pointage » (aussi après la date limite ; le pointage envoie le courriel de paiement), « Annuler la commande » (confirmation), bon de commande ou bon de retrait. Bouton « Nouvelle commande pour une personne sans compte » : nom, téléphone, courriel facultatif, quantités ; état « chèque reçu » ou « en attente » ; possible après la date limite.
2. **Synthèse producteur** : commandes payées uniquement. Lignes : tailles ; colonnes : nombre de sapins et montant ; ligne de totaux. Rappel du nombre et du montant des commandes en attente. Exports XLSX (openspout) et PDF (dompdf) : récapitulatif par taille et liste détaillée par famille.
3. **Retrait** : commandes payées triées par nom, recherche par nom ou numéro, bouton « Retiré » / « Annuler le retrait », compteur « n / total retirées », actualisation `wire:poll` (`PLANNING_REFRESH_SECONDS`, 0 désactive). Version imprimable : liste à cocher.

## HelloAsso (client maison, `Http` de Laravel)

Classe `App\TreeOrders\HelloAsso\Client` (aucune nouvelle dépendance) :
- `token()` : OAuth2 `client_credentials` sur `/oauth2/token`, mis en cache jusqu'à expiration.
- `createCheckout(TreeOrder)` : `POST /v5/organizations/{slug}/checkout-intents` avec `totalAmount` et `initialAmount` en centimes, `itemName` (« Sapins de Noël — commande S-0042 »), `backUrl`, `errorUrl`, `returnUrl`, `containsDonation=false`, `payer` (prénom, nom, courriel) et `metadata` (identifiant et numéro de commande). Retourne `id` et `redirectUrl` ; l'identifiant est enregistré dans `helloasso_checkout_id`.
- `fetchCheckout(id)` : `GET /v5/organizations/{slug}/checkout-intents/{id}`.

Routes (`App\Http\Controllers\TreeOrders\…`) :
- `GET /sapins/paiement/{order}/retour` : relit le *checkout intent* ; si la commande HelloAsso existe avec un paiement `Authorized` du bon montant, marque la commande payée (`payment_method=helloasso`, `helloasso_order_id`) et envoie le courriel de paiement ; redirige vers `/sapins` avec « Paiement reçu ».
- `GET /sapins/paiement/{order}/abandon` et `/erreur` : redirection vers `/sapins` avec « Paiement non effectué, vous pouvez réessayer ou payer par chèque ».
- `POST /sapins/paiement/notification` : sans authentification ni CSRF ; ne fait jamais confiance au contenu reçu ; retrouve la commande par les métadonnées et applique la même vérification via `fetchCheckout`. Idempotent : une commande déjà payée n'est pas retraitée et le courriel n'est envoyé qu'une fois.

La création d'un paiement est refusée après la date limite et pour une commande payée ou annulée. Si HelloAsso est indisponible, la commande reste en attente, un message propose de réessayer ou de payer par chèque, et l'erreur est journalisée.

## PDF et courriels

- **Bon de commande** (dompdf) : mise en page du bon 2025, pré-remplie (nom, téléphone, courriel, quantités, total), numéro de commande, ordre du chèque, lieux de dépôt, date limite, informations de retrait et bon de retrait détachable en pointillés.
- **Bon de retrait** : numéro, nom, tailles, quantités, date, horaire et lieu.
- **`TreeOrderPlaced`** (à la création, y compris pour un tiers avec courriel) : récapitulatif, total, lien vers `/sapins` pour payer en ligne, rappel du chèque, bon de commande en pièce jointe.
- **`TreeOrderPaid`** (au premier passage à « payée », HelloAsso ou chèque) : confirmation et informations de retrait, bon de retrait en pièce jointe.

Tous les textes dans `lang/fr/trees.php`. Polices sans serif (Raleway, ou police sans serif de dompdf), conformément à la charte.

## Documentation et contenus à mettre à jour

- `CLAUDE.md` : section « Sapins » ; règle « Pas de paiement » nuancée (HelloAsso pour les sapins uniquement) ; sapins retirés des points « à définir » et du hors périmètre ; la règle sur les courriels du planning reste inchangée.
- `.env.example`, page « Données personnelles » (`lang/fr/privacy.php`), purge, `SnapshotStore::TABLES`.
- Mode d'emploi : quelques lignes de texte ; nouvelles captures plus tard.

## Tests Pest

Unitaires :
- lecture et validation de `TREE_SIZES` ;
- calcul des totaux et des statuts ;
- client HelloAsso avec `Http::fake` (jeton mis en cache, création du *checkout intent*, lecture et vérification du montant et de l'état).

Fonctionnels :
- création d'une commande, validation (aucun sapin, quantité hors limites, téléphone manquant) ;
- date limite (création, annulation et paiement refusés ; pointage et saisie pour un tiers acceptés) ;
- annulation d'une commande en attente, refus pour une commande payée ;
- redirection vers HelloAsso ; HelloAsso indisponible ;
- retour et notification : paiement validé, montant incorrect, paiement non autorisé, idempotence et courriel unique ;
- pointage du chèque et annulation du pointage ;
- saisie pour un tiers ;
- droits : une famille ne voit pas les commandes des autres ; gestion réservée à `manage-tree-orders` ;
- synthèse (commandes payées uniquement) et exports XLSX et PDF ;
- retrait et compteur ;
- PDF du bon de commande et du bon de retrait ;
- courriels (`Mail::fake`) ;
- purge et instantanés incluant les nouvelles tables ;
- migration du type du stand « Sapins du Morvan ».

## Hors périmètre

- Remboursement automatique via HelloAsso.
- Modification d'une commande après création.
- Gestion du stock ou d'un plafond de sapins.
- Traductions autres que le français.
