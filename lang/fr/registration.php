<?php

return [
    'request' => [
        'title' => 'Créer un compte',
        'heading' => 'Créer un compte',
        'description' => 'Saisissez votre adresse électronique et le mot de passe commun transmis par l’école.',
        'temporary_password' => 'Mot de passe commun',
        'temporary_password_help' => 'Communiqué aux familles, aux professeurs et aux intervenants.',
        'submit' => 'Recevoir le lien de création',
        'wrong_password' => 'Ce mot de passe commun est incorrect.',
        'already_registered' => 'Un compte existe déjà avec cette adresse. Utilisez « Mot de passe oublié » si besoin.',
        'sent_heading' => 'Vérifiez votre boîte de réception',
        'sent_text' => 'Un lien de création de compte vient d’être envoyé à :email. Pensez à regarder dans les indésirables.',
        'retry' => 'Utiliser une autre adresse',
        'have_account' => 'Vous avez déjà un compte ?',
    ],

    'complete' => [
        'title' => 'Finaliser mon compte',
        'heading' => 'Finaliser mon compte',
        'description' => 'Votre adresse est confirmée. Complétez votre profil et choisissez votre mot de passe personnel.',
        'submit' => 'Créer mon compte',
        'already_registered' => 'Ce compte existe déjà : connectez-vous.',
    ],

    'mail' => [
        'subject' => ':name — création de votre compte',
        'greeting' => 'Bonjour,',
        'intro' => 'Vous avez demandé à créer un compte sur le site des bénévoles du :name. Cliquez sur le bouton ci-dessous pour confirmer votre adresse et finaliser votre inscription.',
        'action' => 'Finaliser mon compte',
        'validity' => '{0} Ce lien est valable moins d’une heure.|{1} Ce lien est valable une heure.|[2,*] Ce lien est valable :hours heures.',
        'ignore' => 'Si vous n’êtes pas à l’origine de cette demande, ignorez simplement ce message.',
        'salutation' => 'À bientôt,',
    ],
];
