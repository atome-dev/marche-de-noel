<?php

return [
    'title' => 'Tombola',
    'description' => 'Les lots de la tombola. Le tirage commence par le dernier numéro et se termine par le lot n° 1.',
    'empty' => 'Aucun lot pour le moment.',
    'number' => 'Lot n° :number',
    'winning_ticket' => 'Ticket gagnant',
    'ticket' => 'Ticket :ticket',
    'ticket_placeholder' => 'N° du ticket',
    'not_drawn' => 'Pas encore tiré',

    'draft' => [
        'badge' => 'Liste en préparation',
        'help' => 'Le numéro d’un lot est sa position dans la liste : classez les lots du plus important (n° 1) au moins important. Une fois la liste validée, les numéros sont figés.',
        'validate' => 'Valider la liste',
        'confirm' => 'Valider la liste ? Les numéros des lots seront figés et ne pourront plus être modifiés. Les lots ajoutés ensuite prendront les numéros suivants.',
    ],

    'validated' => [
        'badge' => 'Liste validée',
        'help' => 'Les numéros sont figés. Tout nouveau lot prend le numéro suivant. Le jour du tirage, saisissez le numéro du ticket gagnant de chaque lot.',
    ],

    'form' => [
        'new' => 'Nouveau lot',
        'placeholder' => 'Description du lot',
        'add' => 'Ajouter',
        'label' => 'Description du lot',
        'move_up' => 'Monter',
        'move_down' => 'Descendre',
        'drag' => 'Déplacer',
        'delete_confirm' => 'Supprimer le lot « :label » ?',
    ],

    'toasts' => [
        'saved' => 'Enregistré.',
        'validated' => 'La liste est validée : les numéros sont figés.',
    ],
];
