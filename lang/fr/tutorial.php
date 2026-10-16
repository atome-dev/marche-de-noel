<?php

return [
    'title' => 'Mode d’emploi',
    'guides_label' => 'Choisir un mode d’emploi',
    'link' => 'Mode d’emploi',
    'step' => 'Étape :current sur :total',
    'previous' => 'Étape précédente',
    'next' => 'Étape suivante',
    'pause' => 'Mettre en pause',
    'play' => 'Lire',
    'screen' => 'Écran du site : :title',
    'tap' => 'Touchez ici',
    'back' => 'Retour au site',
    'cta_register' => 'Créer mon compte',
    'cta_login' => 'Me connecter',
    'cta_planning' => 'Ouvrir le planning',
    'cta_stands' => 'Ouvrir mes stands',
    'cta_users' => 'Ouvrir les utilisateurs',

    'guides' => [
        'benevoles' => [
            'name' => 'Bénévoles',
            'intro' => 'Le site en neuf étapes, de la création de votre compte à la commande de votre sapin.',
            'steps' => [
                'compte' => [
                    'title' => 'Créez votre compte',
                    'text' => 'Saisissez votre adresse et le mot de passe commun transmis par l’école. Vous recevez un courriel : son lien confirme votre adresse et vous fait choisir votre mot de passe personnel.',
                ],
                'planning' => [
                    'title' => 'Trouvez un créneau',
                    'text' => 'Le planning liste les stands, jour par jour. Rouge : personne n’est inscrit. Jaune : il reste des places. Vert : c’est complet. Le filtre « Places libres pour moi » ne garde que les créneaux où vous pouvez encore vous inscrire.',
                ],
                'inscription' => [
                    'title' => 'Inscrivez-vous',
                    'text' => 'Touchez un créneau, puis « Je m’inscris ». Vous pouvez laisser un message au responsable du stand. Les autres bénévoles voient votre prénom et l’initiale de votre nom, jamais votre téléphone.',
                ],
                'mes-creneaux' => [
                    'title' => 'Retrouvez vos créneaux',
                    'text' => 'Le filtre « Mes inscriptions » affiche uniquement vos créneaux. Pour vous désinscrire, touchez le créneau puis « Me désinscrire ».',
                ],
                'gateaux' => [
                    'title' => 'Choisissez une recette',
                    'text' => 'Dans « Les petits gâteaux », les recettes confiées aux classes de vos enfants apparaissent en premier. Vous pouvez réaliser n’importe quelle recette, mais celles de vos classes garantissent la diversité des gâteaux.',
                ],
                'je-prevois' => [
                    'title' => 'Annoncez votre production',
                    'text' => 'Sur la fiche de la recette, indiquez combien de kilos vous prévoyez de réaliser, puis touchez « Valider ». La fiche donne aussi les ingrédients, la préparation et le PDF.',
                ],
                'commande' => [
                    'title' => 'Commandez les ingrédients',
                    'text' => 'Après validation, un bouton apparaît pour chaque ingrédient de l’achat groupé. La quantité est calculée d’après vos kilos. Vous pouvez annuler jusqu’à la date butoir indiquée.',
                ],
                'sapins' => [
                    'title' => 'Commandez un sapin',
                    'text' => 'Dans « Sapins de Noël », choisissez vos sapins avec + et −, puis payez en ligne avec HelloAsso, ou imprimez le bon de commande et déposez-le avec un chèque. Vos commandes s’affichent en dessous ; le jour du retrait, présentez le bon de retrait ou votre téléphone.',
                ],
                'concert' => [
                    'title' => 'Vos billets du concert',
                    'text' => 'Dans « Concert », réservez vos billets dans la billetterie HelloAsso, avec la même adresse électronique que sur le site. Ils s’affichent ensuite dans « Mes billets » : touchez « Ouvrir mon billet » pour présenter son QR code à l’entrée.',
                ],
                'accueil' => [
                    'title' => 'Et aussi…',
                    'text' => 'L’accueil mène à tout le reste : la tombola avec ses lots et ses tickets gagnants, et les documents de l’école. « Mon compte », en haut à droite, sert à modifier votre profil ou votre mot de passe.',
                ],
            ],
        ],

        'responsables' => [
            'name' => 'Responsables de stand',
            'intro' => 'Vous êtes responsable d’un stand : voici comment préparer ses créneaux et suivre vos bénévoles.',
            'steps' => [
                'mes-stands' => [
                    'title' => 'Ouvrez « Mes stands »',
                    'text' => 'Le menu affiche « Mes stands » dès que l’administrateur vous a désigné responsable. Touchez un stand pour le gérer. Vous pouvez être plusieurs responsables sur un même stand.',
                ],
                'fiche' => [
                    'title' => 'Complétez la fiche du stand',
                    'text' => 'Modifiez le nom et la description. Les classes cochées réservent le stand aux familles de ces classes ; l’interrupteur en bas de la fiche l’ouvre à tout le monde à tout moment.',
                ],
                'creneaux' => [
                    'title' => 'Ajustez les créneaux',
                    'text' => 'Le stand démarre avec les créneaux par défaut, deux places chacun. Le crayon modifie un créneau, la corbeille le supprime, et « Ajouter un créneau » en crée un nouveau.',
                ],
                'nouveau-creneau' => [
                    'title' => 'Créez un créneau',
                    'text' => 'Choisissez le jour, l’heure de début, la durée et le nombre de places. Le jour est libre : prenez une date avant le marché pour le montage ou les préparatifs. Un créneau peut aussi être réservé à des personnes ou à des classes.',
                ],
                'planning' => [
                    'title' => 'Suivez le remplissage',
                    'text' => 'Dans le planning, filtrez sur votre stand pour voir d’un coup d’œil les créneaux à pourvoir (rouge), partiels (jaune) et complets (vert). Touchez un créneau pour voir qui est inscrit.',
                ],
                'inscrits' => [
                    'title' => 'Contactez vos bénévoles',
                    'text' => 'Vous seul voyez le nom complet, le téléphone et le message des inscrits de votre stand. Vous pouvez inscrire une personne qui n’a pas de compte en tapant son nom, ou retirer une inscription avec la croix.',
                ],
                'impression' => [
                    'title' => 'Imprimez la feuille du stand',
                    'text' => 'Le bouton « Imprimer » du planning produit la feuille de votre stand, créneau par créneau, avec les noms et les téléphones. Pratique à afficher le jour du marché.',
                ],
                'stands-particuliers' => [
                    'title' => 'Stands particuliers',
                    'text' => 'Quatre stands donnent des droits en plus. Les responsables des petits gâteaux gèrent les recettes et le tableau des commandes d’ingrédients, avec ses exports. Ceux de la tombola saisissent les lots, puis les tickets gagnants. Ceux des sapins et du concert ont leur propre écran (étapes suivantes).',
                ],
                'sapins' => [
                    'title' => 'Commandes de sapins',
                    'text' => 'Les responsables du stand des sapins touchent « Gérer les commandes » : ils pointent les chèques reçus et les paiements visibles dans HelloAsso, saisissent les commandes papier, envoient la synthèse au producteur et cochent les sapins retirés le jour J.',
                ],
                'concert' => [
                    'title' => 'Billets du concert',
                    'text' => 'Les responsables du concert touchent « Gérer les billets » sur la page Concert : billets et recette par tarif, liste des spectateurs, et le soir du concert, les entrées contrôlées par l’application HelloAsso, actualisées toutes les 30 secondes.',
                ],
            ],
        ],

        'administrateurs' => [
            'name' => 'Administrateurs',
            'intro' => 'Les outils réservés aux administrateurs : comptes, stands, tombola, documents et sauvegardes.',
            'steps' => [
                'utilisateurs' => [
                    'title' => 'Gérez les comptes',
                    'text' => 'La page « Utilisateurs » liste tous les comptes, avec leurs classes et les stands dont ils sont responsables. Vous y changez un rôle ou supprimez un compte.',
                ],
                'invitation' => [
                    'title' => 'Invitez une personne',
                    'text' => '« Inviter une personne » crée son compte et lui envoie un courriel personnalisé. Choisissez ses stands pour la désigner responsable du même coup. Le compte reste « en attente » jusqu’à ce qu’elle choisisse son mot de passe.',
                ],
                'agir' => [
                    'title' => 'Vérifiez ce que voit quelqu’un',
                    'text' => '« Agir en tant que » vous connecte à la place d’une personne. Un bandeau jaune vous le rappelle sur chaque page et vous ramène à votre compte. Attention : ce que vous faites alors est enregistré à son nom.',
                ],
                'stands' => [
                    'title' => 'Organisez les stands',
                    'text' => '« Nouveau stand » en ajoute un, avec ses créneaux par défaut. Faites glisser un stand par sa poignée pour changer l’ordre du planning.',
                ],
                'responsables' => [
                    'title' => 'Désignez les responsables',
                    'text' => 'Sur la fiche d’un stand, vous choisissez son type, ses responsables et s’il est actif cette année. Un stand désactivé disparaît du planning sans être supprimé.',
                ],
                'tombola' => [
                    'title' => 'Préparez la tombola',
                    'text' => 'Classez les lots du plus important (n° 1) au moins important, puis validez la liste : les numéros sont alors figés. Le jour du tirage, saisissez le ticket gagnant de chaque lot.',
                ],
                'documents' => [
                    'title' => 'Publiez les documents',
                    'text' => 'Ajoutez le journal du marché et les bons de commande : un titre, un fichier. Tous les bénévoles connectés peuvent les ouvrir.',
                ],
                'sauvegardes' => [
                    'title' => 'Gardez un œil sur les sauvegardes',
                    'text' => 'Un instantané des données est pris toutes les 15 minutes. « Sauvegarder maintenant » en prend un avant une manipulation délicate ; « Restaurer » revient à un état antérieur en cas d’erreur.',
                ],
            ],
        ],
    ],
];
