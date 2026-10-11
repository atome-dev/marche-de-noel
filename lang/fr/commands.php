<?php

return [
    'make_admin' => [
        'invalid_email' => 'Adresse électronique invalide.',
        'first_name' => 'Prénom',
        'last_name' => 'Nom',
        'password' => 'Mot de passe',
        'done' => ':email est maintenant administrateur.',
    ],

    'import_stands' => [
        'done' => '{0} Aucun nouveau stand : la liste est déjà importée.|{1} :count stand créé avec ses créneaux par défaut.|[2,*] :count stands créés avec leurs créneaux par défaut.',
    ],

    'import_recipes' => [
        'done' => '{0} Aucune nouvelle recette : la liste est déjà importée.|{1} :count recette créée.|[2,*] :count recettes créées.',
        'unknown_class' => 'Classe inconnue « :class » pour la recette « :recipe » : non associée.',
        'missing_file' => 'Fichier introuvable « :file » pour la recette « :recipe ».',
    ],

    'purge' => [
        'warning' => 'ATTENTION : tous les comptes et toutes les données de l’édition vont être définitivement effacés (seules les classes, la liste des stands et les recettes sont conservées).',
        'word' => 'PURGER',
        'confirm' => 'Tapez :word pour confirmer',
        'aborted' => 'Purge annulée : aucune donnée n’a été effacée.',
        'done' => 'Purge terminée : toutes les données ont été effacées.',
        'next_steps' => 'Pensez à mettre à jour les paramètres annuels du .env, puis à recréer un administrateur avec marche:make-admin.',
    ],
];
