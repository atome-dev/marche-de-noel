<?php

return [
    'mail' => [
        'subject' => ':name — votre invitation sur le site des bénévoles',
        'greeting' => 'Bonjour :first_name,',
        'intro' => 'Vous êtes invité(e) à rejoindre le site des bénévoles du :market.',
        'intro_manager' => '{1} Vous êtes responsable du stand :stands du :market.|[2,*] Vous êtes responsable des stands :stands du :market.',
        'and' => 'et',
        'purpose' => 'Nous vous invitons à vous inscrire sur le site : vous y trouverez le planning des bénévoles, les recettes des petits gâteaux et toutes les informations utiles.',
        'purpose_manager' => 'Nous vous invitons à vous inscrire sur le site : il vous permet de définir les créneaux de votre stand, de suivre les inscriptions des bénévoles et de les contacter.',
        'action' => 'Activer mon compte',
        'explain' => 'Ce lien confirme votre adresse et vous permet de choisir votre mot de passe personnel.',
        'validity' => '{1} Il est valable un jour.|[2,*] Il est valable :days jours.',
        'ignore' => 'Si vous pensez avoir reçu ce message par erreur, ignorez-le simplement : le compte ne sera pas activé.',
    ],

    'accept' => [
        'title' => 'Activer mon compte',
        'heading' => 'Bienvenue !',
        'description' => 'Votre adresse est confirmée. Vérifiez votre profil et choisissez votre mot de passe personnel.',
        'submit' => 'Activer mon compte',
        'already_accepted' => 'Ce compte est déjà activé : connectez-vous.',
    ],

    'admin' => [
        'invite' => 'Inviter une personne',
        'heading' => 'Inviter une personne',
        'description' => 'Son compte est créé tout de suite, mais reste inutilisable tant qu’elle n’a pas suivi le lien reçu par courriel pour choisir son mot de passe.',
        'stands' => 'Responsable des stands (facultatif)',
        'stands_placeholder' => 'Aucun stand',
        'stands_search' => 'Rechercher un stand…',
        'message' => 'Message personnel (facultatif)',
        'message_help' => 'Ajouté au courriel d’invitation, sous le texte habituel.',
        'send' => 'Envoyer l’invitation',
        'sent' => 'Invitation envoyée à :email.',
        'pending' => 'Invitation en attente',
        'resend' => 'Renvoyer l’invitation',
        'email_taken' => 'Un compte existe déjà avec cette adresse : désignez cette personne comme responsable depuis la fiche du stand.',
    ],
];
