<?php

return [
    'title' => 'Documents',
    'description' => 'Le journal du marché, les bons de commande et les autres documents utiles.',
    'empty' => 'Aucun document pour le moment.',
    'open' => 'Ouvrir',

    'form' => [
        'heading' => 'Ajouter un document',
        'title' => 'Titre',
        'title_placeholder' => 'Journal du marché',
        'file' => 'Fichier',
        'file_help' => 'PDF, Word, Excel, OpenDocument ou image, :max Mo au maximum.',
        'server_limit_heading' => 'Limite d’envoi du serveur trop basse',
        'server_limit' => 'Le serveur refuse les fichiers de plus de :server Mo, alors que :configured Mo sont prévus (DOCUMENTS_MAX_SIZE_MB). Augmentez upload_max_filesize et post_max_size dans la configuration PHP (sur Forge : PHP → Max File Upload Size).',
        'uploading' => 'Envoi du fichier…',
        'add' => 'Ajouter',
        'move_up' => 'Monter',
        'move_down' => 'Descendre',
        'delete_confirm' => 'Supprimer le document « :title » ?',
    ],

    'toasts' => [
        'added' => 'Le document est en ligne.',
    ],
];
