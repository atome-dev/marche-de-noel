<?php

return [
    'title' => 'Planning',
    'heading' => 'Planning des bénévoles',
    'description' => 'Touchez une case pour vous inscrire ou vous désinscrire.',
    'print' => 'Imprimer',
    'reserved' => 'Réservé',
    'to_fill' => 'À pourvoir',
    'no_slot' => 'Pas de créneau',
    'stand_column' => 'Stand',
    'manage_stand' => 'Gérer le stand',
    'empty' => 'Aucun créneau ne correspond à ces filtres.',

    'filters' => [
        'stand' => 'Stand',
        'all_stands' => 'Tous les stands',
        'day' => 'Jour',
        'all_days' => 'Tous les jours',
        'all_days_short' => 'Tous',
        'availability' => 'Afficher',
        'all_slots' => 'Tous les créneaux',
        'free' => 'Places libres pour moi',
        'mine' => 'Mes inscriptions',
    ],

    'legend' => [
        'empty' => 'À pourvoir',
        'partial' => 'Partiel',
        'full' => 'Complet',
        'no_slot' => 'Pas de créneau',
        'reserved' => 'Réservé',
    ],

    'modal' => [
        'places' => ':taken / :capacity places',
        'volunteers' => 'Inscrits',
        'nobody' => 'Personne pour le moment.',
        'guest' => 'sans compte',
        'registered' => 'Vous êtes inscrit·e sur ce créneau. Merci !',
        'register' => 'Je m’inscris',
        'unregister' => 'Me désinscrire',
        'unregister_confirm' => 'Vous désinscrire de ce créneau ?',
        'message' => 'Message au responsable (facultatif)',
        'message_placeholder' => 'Je peux apporter…',
        'overlap_warning' => 'Attention : vous êtes déjà inscrit·e sur un créneau au même moment.',
        'manager_heading' => 'Responsable du stand',
        'guest_name' => 'Inscrire une personne sans compte',
        'guest_placeholder' => 'Prénom et nom',
        'add_guest' => 'Ajouter',
        'remove' => 'Retirer',
        'remove_confirm' => 'Retirer :name de ce créneau ?',
    ],

    'toasts' => [
        'registered' => 'Inscription enregistrée. Merci !',
        'unregistered' => 'Inscription retirée.',
    ],

    'print_page' => [
        'title' => 'Planning des bénévoles',
        'printed_on' => 'Imprimé le :date',
        'action' => 'Imprimer',
        'back' => 'Retour au planning',
        'managers' => 'Responsables : :names',
        'slot' => 'Créneau',
        'volunteers' => 'Bénévoles',
        'phone' => 'Téléphone',
        'missing' => '{1} :count place à pourvoir|[2,*] :count places à pourvoir',
    ],

    'refusals' => [
        'inactive' => 'Ce stand n’est pas ouvert cette année.',
        'already_registered' => 'Vous êtes déjà inscrit·e sur ce créneau.',
        'full' => 'Ce créneau est complet.',
        'reserved' => 'Ce créneau est réservé à d’autres personnes.',
        'other_classes' => 'Ce stand est réservé aux familles de certaines classes.',
    ],
];
