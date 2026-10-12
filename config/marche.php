<?php

$splitList = static fn (mixed $value): array => array_values(array_filter(
    array_map('trim', explode(',', is_string($value) ? $value : '')),
    static fn (string $item): bool => $item !== '',
));

return [

    /*
    |--------------------------------------------------------------------------
    | Marché de Noël
    |--------------------------------------------------------------------------
    |
    | Paramètres annuels du marché. Ils sont à redéfinir chaque année dans le
    | fichier .env, après la purge complète des données (marche:purge).
    |
    */

    'name' => env('MARCHE_NAME', 'Marché de Noël'),

    'school_name' => env('MARCHE_SCHOOL_NAME', 'École Steiner Waldorf de Verrières-le-Buisson'),

    'school_url' => env('MARCHE_SCHOOL_URL', 'https://ecole-steiner-verrieres.org/'),

    // Adresse de contact affichée sur la page « Données personnelles ».
    'privacy' => [
        'contact_email' => env('MARCHE_PRIVACY_CONTACT_EMAIL', 'accueil@ecole-steiner-verrieres.org'),
    ],

    // Taille maximale (en Mo) d'un document téléversé par l'administrateur.
    'documents' => [
        'max_size_mb' => (int) env('DOCUMENTS_MAX_SIZE_MB', 20),
    ],

    // Fuseau horaire d'affichage des dates techniques (instantanés).
    'timezone' => env('MARCHE_TIMEZONE', 'Europe/Paris'),

    // Instantanés des données (marche:snapshot, toutes les 15 minutes) : durée de conservation en jours.
    'snapshots' => [
        'retention_days' => (int) env('SNAPSHOT_RETENTION_DAYS', 30),

        // Adresse appelée après chaque instantané réussi (heartbeat Forge), facultative.
        'heartbeat_url' => env('SNAPSHOT_HEARTBEAT_URL'),
    ],

    // Durée de validité (en minutes) du lien d'invitation envoyé par un administrateur.
    'invitation_link_ttl' => (int) env('MARCHE_INVITATION_LINK_TTL', 10080),

    // Mot de passe commun permettant de demander la création d'un compte.
    'temp_password' => env('MARCHE_TEMP_PASSWORD'),

    // Durée de validité (en minutes) du lien de création de compte envoyé par courriel.
    'registration_link_ttl' => (int) env('MARCHE_REGISTRATION_LINK_TTL', 1440),

    // Dates du marché, au format AAAA-MM-JJ, séparées par des virgules.
    'dates' => $splitList(env('MARCHE_DATES', '')),

    /*
    |--------------------------------------------------------------------------
    | Planning
    |--------------------------------------------------------------------------
    */

    'planning' => [
        'hour_min' => env('PLANNING_HOUR_MIN', '08:00'),
        'hour_max' => env('PLANNING_HOUR_MAX', '20:00'),

        // Durées possibles d'un créneau, en minutes, séparées par des virgules.
        'durations' => array_map('intval', $splitList(env('PLANNING_SLOT_DURATIONS', '30,60,90,120'))),

        // Créneaux créés par défaut pour chaque nouveau stand, chaque jour du marché (HH:MM-HH:MM).
        'default_slots' => $splitList(env('PLANNING_DEFAULT_SLOTS', '12:30-14:00,14:30-16:00,16:00-17:30,17:30-19:00,19:00-20:00')),

        // Nombre de places de ces créneaux par défaut.
        'default_capacity' => (int) env('PLANNING_DEFAULT_CAPACITY', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Petits gâteaux
    |--------------------------------------------------------------------------
    */

    'orders' => [
        // Date butoir des commandes d'ingrédients (AAAA-MM-JJ HH:MM).
        'deadline' => env('ORDER_DEADLINE'),

        // Ingrédients commandables, séparés par des virgules.
        'ingredients' => $splitList(env('ORDER_INGREDIENTS', '')),

        // Quantités proposées pour « Je prévois de réaliser », en kilos.
        'quantities' => array_map('intval', $splitList(env('ORDER_QUANTITIES', '1,2,3'))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Développement
    |--------------------------------------------------------------------------
    |
    | Compte administrateur créé par le seeder de développement à chaque
    | réinitialisation de la base. Laisser vide pour ne pas le créer. Ces
    | valeurs restent dans .env : elles ne doivent jamais être versionnées.
    |
    */

    'seed_admin' => [
        'email' => env('SEED_ADMIN_EMAIL'),
        'password' => env('SEED_ADMIN_PASSWORD'),
        'first_name' => env('SEED_ADMIN_FIRST_NAME', 'Admin'),
        'last_name' => env('SEED_ADMIN_LAST_NAME', 'Développeur'),
    ],

    // Dossier versionné des PDF et photos lus par « marche:import-recipes ».
    'recipes_source' => resource_path('recettes'),

    /*
    |--------------------------------------------------------------------------
    | Services externes
    |--------------------------------------------------------------------------
    */

    'youtube_api_key' => env('YOUTUBE_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Langues
    |--------------------------------------------------------------------------
    |
    | Langues proposées dans le profil. Langues prévues : fr, en, de, es, pt,
    | it. N'activer une langue qu'une fois ses fichiers lang/ traduits.
    |
    */

    'locales' => $splitList(env('MARCHE_LOCALES', 'fr')),

];
