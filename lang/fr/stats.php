<?php

return [
    'title' => 'Statistiques',
    'tabs' => [
        'planning' => 'Planning',
        'petits-gateaux' => 'Petits gâteaux',
    ],
    'open_places' => [
        'heading' => 'Places à pourvoir',
        'help' => 'Places encore libres sur le planning, par stand et par jour (places libres / places au total). Les jours passés ne sont pas comptés.',
        'stand' => 'Stand',
        'total' => 'Total',
        'none' => '—',
        'empty' => 'Aucun créneau à venir sur le planning.',
        'cell' => ':open / :capacity',
        'legend' => [
            'full' => 'Complet',
            'partial' => 'Moins de la moitié des places libres',
            'empty' => 'Plus de la moitié des places libres',
        ],
    ],
    'cookies' => [
        'heading' => 'Kilos prévus par recette',
        'help' => 'Engagements « Je prévois de réaliser » saisis par les familles, recette par recette. Les recettes que personne n’a choisies sont en rouge.',
        'empty' => 'Aucune recette pour le moment.',
        'no_category' => 'Sans famille',
        'recipe' => 'Recette',
        'families' => 'Familles',
        'kilos' => 'Kilos prévus',
        'kilos_value' => ':kilos kg',
        'total' => 'Total',
        'kilos_planned' => 'kg prévus',
        'families_engaged' => 'famille engagée|familles engagées',
        'recipes_unchosen' => 'recette non choisie|recettes non choisies',
        'families_note' => 'Une famille qui prévoit plusieurs recettes n’est comptée qu’une fois dans le total.',
    ],
];
