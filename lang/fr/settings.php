<?php

return [
    'heading' => 'Mon compte',
    'description' => 'Gérez votre profil et votre mot de passe.',

    'profile' => [
        'title' => 'Mon profil',
        'heading' => 'Profil',
        'description' => 'Vos coordonnées et les classes de vos enfants.',
        'updated' => 'Profil enregistré.',
    ],

    'email_change' => [
        'help' => 'Si vous la modifiez, un lien de confirmation sera envoyé à la nouvelle adresse.',
        'sent' => 'Pour changer d’adresse, cliquez sur le lien envoyé à :email.',
        'confirmed' => 'Votre nouvelle adresse électronique est enregistrée.',
        'taken' => 'Cette adresse est déjà utilisée par un autre compte : le changement est annulé.',
        'mail' => [
            'subject' => ':name — confirmation de votre nouvelle adresse',
            'intro' => 'Vous avez demandé à utiliser cette adresse pour votre compte du :name. Cliquez sur le bouton ci-dessous pour la confirmer.',
            'action' => 'Confirmer cette adresse',
        ],
    ],

    'password' => [
        'title' => 'Mot de passe',
        'heading' => 'Mot de passe',
        'description' => 'Modifiez votre mot de passe personnel.',
        'updated' => 'Mot de passe modifié.',
    ],

    'delete' => [
        'heading' => 'Supprimer mon compte',
        'description' => 'Votre compte et toutes vos données seront définitivement effacés.',
        'action' => 'Supprimer mon compte',
        'confirm_heading' => 'Supprimer définitivement votre compte ?',
        'confirm_text' => 'Toutes vos données seront effacées. Saisissez votre mot de passe pour confirmer.',
    ],
];
