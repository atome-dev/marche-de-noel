# Projet : Site du Marché de Noël de l'école (v2)

Refonte complète du site v1. Le site est **privé** (parents, professeurs et intervenants externes), sans objectif SEO : `noindex` et `robots disallow`. Livraison prévue dans environ deux semaines (date de référence : 9 octobre 2026).

École Steiner de Verrières. Caler la charte graphique sur le site de l'école : https://ecole-steiner-verrieres.org/. Uniquement des polices sans serif sur tout le site : Raleway (texte et titres). Jamais de police à empattements ni de police manuscrite de type « Comic » (Pangolin, Comic Sans…).

Priorité absolue au mobile-first : la plupart des bénévoles utilisent un téléphone.

## Stack et conventions

- Laravel 13, Blade, Livewire 4, Flux UI Pro, Tailwind, Pest, MySQL.
- Déploiement avec Forge sur un serveur personnel.
- Pas d'API pour le moment.
- Pas de paiement.
- Courriels via Mailjet, déjà configuré dans `.env`.
- Les tests Pest unitaires **et** fonctionnels sont obligatoires pour tout code.
- Livewire 4 en mode **classique uniquement** : pas de single-file component (SFC). Chaque composant comporte deux fichiers :
  - `App\Livewire\MonComposant.php`
  - `resources/views/livewire/mon-composant.blade.php`
- Prévoir un maximum de paramètres dans `.env`, exposés via `config/marche.php`. Ne jamais appeler `env()` directement en dehors des fichiers de configuration.
- Interface en français uniquement pour l'instant. Tout texte doit passer par les helpers de traduction (`lang/fr/...`). Langues prévues : `fr`, `en`, `de`, `es`, `pt`, `it`; seul le français est traduit pour le moment.
- Choisir une convention cohérente pour les noms de classes et de variables (anglais ou français) et s'y tenir. Les textes de l'interface sont en français.
- Utiliser Pest et Pint.
- Développer et tester chaque fonctionnalité avant de passer à la suivante.
- Valider chaque fonctionnalité sur mobile.
- Proposer un fichier `.env.example` complet couvrant tous les paramètres nécessaires.

## RGPD et cycle de vie

- Il n'existe aucune notion d'année en base de données.
- Chaque année, **toutes les données sont effacées**.
- Prévoir une commande Artisan dédiée à la purge totale, avec confirmation obligatoire et tests.
- Le site reste actif environ deux semaines après le marché pour la tombola.
- Aucune conservation des données au-delà de ce cycle.
- La purge conserve uniquement les données de référence : les classes et la liste des stands (avec leurs classes associées). Créneaux, inscriptions, responsables et comptes sont effacés.
- Chaque année, redéfinir dans `.env` le mot de passe temporaire, les dates, les heures et les autres paramètres annuels.

## Variables `.env` à prévoir

Prévoir notamment les variables suivantes, avec des valeurs d'exemple adaptées dans `.env.example` :

- `MARCHE_TEMP_PASSWORD` (mot de passe commun de l'année ; laissé vide dans `.env.example`, jamais versionné).
- `MARCHE_NAME`.
- `MARCHE_DATES` (liste de dates).
- `PLANNING_HOUR_MIN`.
- `PLANNING_HOUR_MAX`.
- `PLANNING_SLOT_DURATIONS` (durées possibles d'un créneau, en minutes).
- `ORDER_DEADLINE` (date butoir des commandes).
- Liste configurable des ingrédients commandables, par exemple : poudre d'amande, poudre de noisette, noix de coco râpée; l'année précédente, les amandes entières et les noisettes entières étaient aussi possibles.
- Clé YouTube/Google optionnelle.
- Paramètres Mailjet et autres paramètres de messagerie nécessaires.
- Tous les autres paramètres nécessaires au fonctionnement, documentés et exposés via `config/marche.php`.

## Authentification et rôles

- Login classique par courriel et mot de passe.
- Mot de passe oublié par courriel.
- Inscription en deux temps : la personne saisit son courriel et le mot de passe commun (`MARCHE_TEMP_PASSWORD`). S'il est correct, un lien signé et temporaire est envoyé par courriel ; il valide l'adresse et ouvre la création du compte (mot de passe personnel + profil). Toute personne qui connaît le mot de passe commun peut créer un compte.
- Pas de 2FA ni de passkeys.
- Mot de passe : au moins 6 caractères, avec lettres et chiffres (site privé et temporaire).
- Changement d'adresse électronique : appliqué seulement après clic sur un lien de confirmation envoyé à la nouvelle adresse.
- Rôles :
  - **Administrateur** : tous les droits partout.
  - **Responsable** : responsable d'un stand.
  - **Référent** : parent référent d'une classe; l'impact fonctionnel reste à déterminer.
  - Professeurs et intervenants externes : rôle fonctionnel à déterminer.
- Profil : nom, prénom, courriel, téléphone (facultatif), langue et classes des enfants (plusieurs classes possibles, facultatif). On ne gère ni les enfants ni leur nombre.
- Le téléphone n'est visible que par l'administrateur et les responsables du stand concerné, jamais par les autres bénévoles.
- Classes : « JE Pommier », « JE Parc », « JE Balcon », « 1ère Classe », « 2ème Classe », jusqu'à « 13ème Classe ».
- Les notions de parent et de famille sont identiques.

## Stands

- La liste est quasi fixe (environ 40 stands), avec la possibilité d'ajouter ou de désactiver un ou deux stands par an.
- Prévoir un Enum ou un type de stand avec des fonctionnalités propres à chaque type, notamment :
  - « Petits gâteaux » : les responsables éditent les recettes.
  - « Tombola » : les responsables éditent les lots et saisissent les gagnants.
  - « Commande de sapins ».
  - Autres stands génériques.
- Un stand peut ne pas apparaître dans le planning si son nombre de bénévoles est nul.
- L'administrateur crée le stand et désigne un ou plusieurs responsables.
- Les responsables peuvent ensuite modifier librement leur stand.
- Un stand peut être associé à une ou plusieurs classes.
- Cette association restreint les inscriptions aux personnes de ces classes. Le responsable peut lever la restriction et ouvrir le stand à tout le monde à tout moment.

## Planning

Le planning est la page la plus importante.

- La plage horaire minimale et maximale vient de `.env`.
- Pour chaque stand, définir des créneaux libres (par exemple `10h00–11h30` et `14h00–16h00), environ quatre créneaux par jour.
- Création des créneaux : pour chaque jour du marché (`MARCHE_DATES`) et dans la plage horaire (`PLANNING_HOUR_MIN`, `PLANNING_HOUR_MAX`), le responsable ajoute, modifie ou supprime des créneaux en précisant l'heure de début et la durée parmi 0:30, 1:00, 1:30 et 2:00 (`PLANNING_SLOT_DURATIONS`). L'heure de fin est calculée.
- Le nombre de places est défini par stand et par créneau.
- Un créneau peut être réservé à une liste de personnes, via une liste de courriels ou de classes.
- Pour les autres utilisateurs, le créneau s'affiche « Réservé » avec un cadenas.
- Le responsable ou l'administrateur peut ouvrir un créneau à tout le monde.
- Un bénévole peut s'inscrire sur tout créneau non plein et non réservé, sauf s'il appartient à la liste réservée.
- Le responsable peut remplir un créneau avec un texte libre pour une personne sans compte.
- Prévoir des filtres par stand et/ou par date.
- Prévoir une version imprimable par stand ou globale.
- Ne pas envoyer de notification par courriel pour le moment.
- Chevauchement : un bénévole peut s'inscrire sur deux créneaux qui se chevauchent, mais une alerte le lui signale (pas de blocage).
- Noms des inscrits : visibles par tous les connectés sous la forme « Camille M. » ; nom complet, téléphone et message réservés aux responsables du stand et à l'administrateur.
- Message au responsable : facultatif à l'inscription.
- La liste de référence des stands est dans `database/data/stands.php` ; `php artisan marche:import-stands` crée les stands manquants sans toucher aux stands existants. L'administrateur peut aussi en ajouter à la main.
- Par défaut, tous les stands sont créés avec les mêmes créneaux de 2 places, chaque jour du marché : 12:30–14:00, 14:30–16:00, 16:00–17:30, 17:30–19:00 et 19:00–20:00 (`PLANNING_DEFAULT_SLOTS`, `PLANNING_DEFAULT_CAPACITY`). Les responsables les ajustent ensuite.

### Maquette de référence

Le fichier `planning.html` sera fourni par l'utilisateur à la racine du projet ou dans `docs/maquettes/`.

La maquette présente une grille dense : stands en lignes, créneaux en colonnes groupés par jour, colonne stand figée, couleurs rouge/orange/vert selon le remplissage, vue liste sur mobile et impression paysage.

Règles d'affichage du planning (elles remplacent la grille de la maquette et l'ancienne règle des colonnes `jour + début + fin`) :

- Un tableau par jour, les journées étant séparées ; chaque tableau reprend tous les stands ouverts ce jour-là.
- En colonnes, on affiche toujours la plage de `PLANNING_HOUR_MIN` à `PLANNING_HOUR_MAX`, avec un quadrillage par tranches de 30 minutes.
- Un créneau apparaît à partir de la colonne de son heure de début, et sa largeur dépend de sa durée.
- L'heure de début d'un créneau tombe sur une demi-heure.
- Deux créneaux d'un même stand ne peuvent pas se chevaucher sur une même journée.

Dans la maquette, le libellé « Montage » signifie l'installation du stand avant l'ouverture. Il est indicatif et non structurant.

## Petits gâteaux : recettes et commandes d'ingrédients

- Répartir les recettes par familles (types de biscuits) afin de garantir la diversité.
- Associer chaque recette à une ou plusieurs classes.
- Une fiche recette contient :
  - ingrédients avec quantité et unité distinctes (exemples : `Oeufs — 3`, `Farine — 500 g`, `Levure — 1/2 sachet`, `Sel — 1 pincée`);
  - instructions;
  - photos;
  - lien vers un PDF;
  - vidéo YouTube intégrée via `embed`, ou lien; ne jamais téléverser de vidéo.
- Les responsables du stand « Petits gâteaux » et l'administrateur peuvent éditer les recettes.
- Page « Les petits gâteaux » :
  - bloc 1 : vignettes des recettes associées aux classes de l'utilisateur;
  - bloc 2 : autres recettes, ou toutes les recettes si le bloc 1 est vide.
- Tout le monde peut voir toutes les recettes.
- Production libre : chaque famille peut réaliser autant de gâteaux qu'elle le souhaite, pour n'importe quelle recette. Encourager toutefois la réalisation des recettes associées à sa famille.
- Aucun minimum de production : la saisie est libre.
- Dans la fiche recette, proposer : « Je prévois de réaliser » `[1 | 2 | 3 kg]`, avec 1 kg par défaut, puis un bouton « Valider ».
- Après validation, proposer un bouton par ingrédient commandable présent dans la recette :
  - « Commander la poudre d'amande »;
  - « Commander la poudre de noisette »;
  - « Commander la noix de coco râpée ».
- Chaque recette possède ses propres proportions (par exemple 200 g de poudre d'amande pour une quantité donnée de production).
- Chaque ingrédient commandable est indépendant et peut être annulé individuellement.
- Si la quantité à produire est modifiée, annuler les commandes liées. La personne doit recommander après validation.
- La liste des ingrédients commandables reste dans `.env` (`ORDER_INGREDIENTS`) ; chaque recette indique, pour chacun, les grammes à commander par kilo produit.
- Les recettes des PDF sont prévues pour produire 1 kg de gâteaux : la quantité d'un ingrédient commandable dans le PDF est donc la quantité à commander par kilo.
- Les recettes de référence (titre, famille « Recette facile » ou « Recette élaborée », classes, ingrédients et préparation retranscrits des PDF) sont dans `database/data/recipes.php` ; `php artisan marche:import-recipes` crée les recettes manquantes, sans toucher aux recettes existantes, et copie le PDF et la photo depuis `resources/recettes` (dossier versionné, donc déployé avec le code) vers le stockage public.
- Le « responsable des achats » : les responsables du stand de type « Petits gâteaux » et l'administrateur (synthèse et exports).
- PDF d'une recette : fichier téléversé ou lien, les deux sont possibles.
- Après la date butoir : plus de commande ni d'annulation, et un engagement qui a des commandes ne peut plus changer ; s'engager sans commander reste possible.
- La purge conserve les recettes, leurs photos et les familles de biscuits ; elle efface les engagements et les commandes.
- Les ingrédients commandables sont configurables en base de données ou dans `.env`, car ils varient chaque année.
- Après la date butoir définie dans `.env`, les commandes sont verrouillées.
- Prévoir un tableau de synthèse pour le responsable des achats :
  - lignes : familles (parents);
  - colonnes : ingrédients;
  - sous-total par famille;
  - ligne de totaux par colonne.
- Prévoir les exports XLSX et PDF.
- Le responsable des achats gère lui-même la commande fournisseur et la répartition des ingrédients.

## Tombola

- La page est rustique dans la v1 et doit être modernisée.
- Les responsables du stand « Tombola » éditent la liste des lots et saisissent les gagnants.
- Les détails restent à définir : ne rien inventer.

## Visuel de l'année (flyer)

- Le dessin du flyer habille la page de connexion (affiche entière à côté du formulaire sur ordinateur, bandeau sur mobile) et le haut de la page d'accueil.
- Deux fichiers à régénérer chaque année depuis le flyer : `public/images/flyer.webp` (affiche entière, 1000 px de large) et `public/images/flyer-banner.webp` (bandeau recadré sur le dessin, sans texte). S'ils sont absents, les pages s'affichent sans visuel.

## Autres contenus

- Journal PDF et bons de commande : nouveaux documents à téléverser chaque année par l'administrateur.
- Commande de sapins : fonctionnalité à définir plus tard, hors périmètre de la première livraison.

## Périmètre de livraison et ordre de développement

1. **Socle** : authentification, inscription avec mot de passe temporaire, profil, rôles, classes, layout mobile-first avec Flux, charte de l'école et commande de purge.
2. **Stands et planning** : maquette de référence, inscriptions et impression.
3. **Petits gâteaux** : recettes, engagements, commandes, tableau fournisseur et exports.
4. **Tombola**.
5. **Documents et finitions**.

Les sapins et tous les points « à définir » sont hors de la première livraison.

Pour chaque étape, écrire les tests Pest avant de passer à la suivante et valider l'interface sur téléphone.

## Points encore à définir

Ne pas inventer les réponses; poser la question lorsque nécessaire :

- détail de la tombola;
- commande de sapins;
- impact fonctionnel des rôles référent, professeur et intervenant externe;
- mise en avant des recettes peu utilisées;
- traductions dans les autres langues.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.4. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== livewire/core rules ===

# Livewire

- Livewire allows you to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.
- After the feature tests pass, ask the user to run the complete suite with `php artisan test --compact`.

</laravel-boost-guidelines>
