<?php

/*
|--------------------------------------------------------------------------
| Sapins de Noël
|--------------------------------------------------------------------------
|
| Paramètres annuels de la commande de sapins, à mettre à jour chaque année
| dans ce fichier (versionné). Seuls les identifiants HelloAsso, secrets,
| restent dans .env.
|
*/

return [

    /*
    | Tailles proposées et tarif de chaque sapin, en euros. La clé (« key »)
    | reste stable pendant l'année : elle identifie la taille dans les
    | commandes. Les commandes gardent le libellé et le prix du moment où
    | elles ont été passées.
    */

    'sizes' => [
        ['key' => 'lutins', 'name' => 'Le sapin des lutins', 'height' => '80/100 cm', 'price' => 27.00],
        ['key' => 'moyen', 'name' => 'Le sapin moyen', 'height' => '125/150 cm', 'price' => 35.00],
        ['key' => 'grand', 'name' => 'Le grand sapin', 'height' => '150/175 cm', 'price' => 45.00],
        ['key' => 'chatelains', 'name' => 'Le sapin des châtelains', 'height' => '200/250 cm', 'price' => 65.00],
    ],

    // Quantité maximale par taille et par commande.
    'max_quantity' => 10,

    // Date limite des commandes (AAAA-MM-JJ), incluse jusqu'à minuit (MARCHE_TIMEZONE).
    'deadline' => '2026-11-29',

    // Retrait des sapins : date (AAAA-MM-JJ), horaire et lieu, affichés tels quels.
    'pickup' => [
        'date' => '2026-12-05',
        'hours' => '11h00 – 13h00',
        'place' => 'École Steiner de Verrières-le-Buisson, parking du haut, entrée 21 route de Bièvres',
    ],

    // Paiement par chèque : ordre du chèque et lieux de dépôt.
    'cheque' => [
        'payee' => 'École Steiner de Verrières-le-Buisson',
        'drop_points' => [
            'dans l’urne prévue à cet effet à l’accueil',
            'lors du Marché de Noël sur le stand couronnes - Sapins (Chalet sans souci)',
        ],
    ],

    'producer' => 'Cruard Boudinel',

    'description' => 'Sapins Nordmann de 1er choix, fraîchement coupés, en provenance du Morvan. Livrés bûchés et sous filet. Exposé à la chaleur dans les logements, le Nordmann est le sapin qui garde le mieux ses aiguilles.',

    /*
    | Paiement en ligne avec HelloAsso. Avec les identifiants de l'API (.env),
    | le paiement est confirmé automatiquement (API Checkout). Sinon, avec
    | l'adresse de la boutique HelloAsso de l'année, la famille paie dans la
    | boutique et un responsable pointe « Payé sur HelloAsso ». Sans l'un ni
    | l'autre, seul le paiement par chèque est proposé.
    */

    'helloasso' => [
        'client_id' => env('HELLOASSO_CLIENT_ID'),
        'client_secret' => env('HELLOASSO_CLIENT_SECRET'),
        'organization_slug' => env('HELLOASSO_ORGANIZATION_SLUG'),
        // true : environnement de test (api.helloasso-sandbox.com).
        'sandbox' => (bool) env('HELLOASSO_SANDBOX', true),
        // Boutique de l'année, à recréer chaque année sur HelloAsso.
        'shop_url' => 'https://www.helloasso.com/associations/association-libre-ecole-rudolf-steiner/boutiques/sapins-de-noel-2026',
        // « link » : fenêtre récapitulative et lien vers la boutique (nouvel onglet) ;
        // « iframe » : page de paiement du site avec le widget de la boutique intégré.
        'shop_display' => 'iframe',
    ],

];
