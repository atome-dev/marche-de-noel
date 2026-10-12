<?php

return [
    'users' => [
        'title' => 'Utilisateurs',
        'heading' => 'Utilisateurs',
        'count' => '{0} Aucun compte|{1} :count compte|[2,*] :count comptes',
        'search' => 'Rechercher…',
        'empty' => 'Aucun utilisateur ne correspond à cette recherche.',
        'delete' => 'Supprimer',
        'delete_confirm' => 'Supprimer définitivement le compte de :name ?',
        'deleted' => 'Le compte de :name a été supprimé.',
        'role_updated' => 'Le rôle de :name a été modifié.',
    ],

    'snapshots' => [
        'title' => 'Sauvegardes',
        'heading' => 'Sauvegardes',
        'description' => 'Un instantané des données (comptes, stands, planning, recettes, commandes, tombola, documents) est pris toutes les 15 minutes quand elles ont changé, et conservé :days jours. Les fichiers téléversés n’en font pas partie.',
        'create' => 'Sauvegarder maintenant',
        'created' => 'Instantané enregistré.',
        'unchanged' => 'Rien n’a changé depuis le dernier instantané.',
        'empty' => 'Aucun instantané pour le moment.',
        'latest' => 'Le plus récent',
        'download' => 'Télécharger',
        'restore' => 'Restaurer',
        'restore_word' => 'RESTAURER',
        'restore_confirm' => 'Remplacer TOUTES les données actuelles par celles du :date ? Ce qui a été saisi depuis sera perdu (l’état actuel est d’abord sauvegardé). Tapez :word pour confirmer.',
        'restored' => 'Données du :date restaurées.',
        'not_found' => 'Cet instantané n’existe plus.',
        'count' => '{1} :count instantané|[2,*] :count instantanés',
        'more' => 'Seuls les :shown plus récents sont affichés.',
    ],
];
