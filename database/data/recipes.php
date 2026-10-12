<?php

/*
|--------------------------------------------------------------------------
| Recettes des petits gâteaux
|--------------------------------------------------------------------------
|
| Liste de référence importée par « php artisan marche:import-recipes ».
| Les fichiers (PDF et photo) sont lus dans resources/recettes.
|
| Chaque ingrédient s'écrit « quantité | unité | nom » ; la quantité et
| l'unité peuvent être vides. Un quatrième champ facultatif donne la clé de
| l'ingrédient commandable (voir ORDER_INGREDIENTS) : les PDF étant prévus
| pour 1 kg de gâteaux, la quantité en grammes est aussi la quantité à
| commander par kilo produit. Les ingrédients et la préparation sont
| retranscrits depuis les PDF, avec quelques corrections validées : poudre
| de noisette (chapeau des hussards), poudre d'amande (étoiles à la
| cannelle), four à 180 °C (sablés spirale ou damier) et pas d'œuf dans
| les croissants Mocca.
|
*/

return [
    [
        'title' => 'Biscuits pommes, cannelle et avoine',
        'category' => 'Recette facile',
        'pdf' => 'flocons-d-avoine-pomme-cannelle.pdf',
        'photo' => 'flocons-d-avoine-pomme-cannelle.source.png',
        'classes' => ['JE Pommier'],
        'ingredients' => [
            '150 | g | Pommes séchées hachées',
            '100 | g | Flocons d’avoine',
            '180 | ml | Eau bouillante (pour hydrater les pommes séchées)',
            '220 | g | Sucre',
            '190 | g | Beurre doux ramolli',
            '2 | | Œufs',
            '75 | g | Noix de coco râpée | noix-de-coco-rapee',
            '350 | g | Farine',
            '1 | sachet | Levure',
            ' | | Sucre à la cannelle : 1 c. à soupe de cannelle pour 2 c. à soupe de sucre semoule',
        ],
        'instructions' => '<ul><li>Préchauffer le four à 180 °C.</li><li>Préparer une ou plusieurs plaques recouvertes de papier sulfurisé.</li><li>Hydrater les pommes séchées 5 min dans l’eau bouillante, puis égoutter si besoin.</li></ul>'
            .'<ol><li>Battre le beurre ramolli avec le sucre jusqu’à obtenir une texture crémeuse. Ajouter les œufs et battre vigoureusement.</li>'
            .'<li>Mélanger tous les ingrédients secs et les pommes réhydratées jusqu’à obtenir une pâte homogène.</li>'
            .'<li>Pour chaque biscuit, prélever l’équivalent d’une bonne cuillère à café de pâte et la déposer à l’aide d’une petite cuillère sur la feuille de papier cuisson, en prenant soin de les espacer afin qu’ils ne se touchent pas à la cuisson (ils vont s’élargir). Saupoudrer de sucre à la cannelle (en refaire autant que nécessaire).</li>'
            .'<li>Cuire pendant 10 à 15 min ou jusqu’à ce qu’ils soient bien dorés. Les laisser refroidir sur une grille pour un refroidissement complet et une évacuation maximale de l’humidité.</li>'
            .'<li>Ranger les biscuits refroidis dans une boîte hermétique et conserver au sec.</li></ol>',
    ],
    [
        'title' => 'Biscuits granola',
        'category' => 'Recette élaborée',
        'pdf' => 'biscuits-granola.pdf',
        'photo' => 'biscuit-granola.source.png',
        'classes' => ['JE Pommier'],
        'ingredients' => [
            '240 | g | Farine de châtaigne',
            '210 | g | Farine de sarrasin',
            '150 | g | Farine blanche',
            '240 | g | Beurre coupé en petits dés',
            '150 | g | Sucre roux',
            '3 | | Œufs',
            '3 | c. à café rases | Bicarbonate alimentaire',
            '2 | pincées | Sel',
            '300 | g | Chocolat noir ou au lait (glaçage)',
            '50 | g | Beurre (glaçage)',
            '50 | g | Sucre glace (glaçage, sauf si chocolat au lait)',
        ],
        'instructions' => '<ul><li>Préchauffer le four à 180 °C.</li><li>Préparer une ou plusieurs plaques recouvertes de papier sulfurisé.</li></ul>'
            .'<ol><li>Dans un saladier (ou le bol d’un mixeur), tamiser les farines, le sucre, le bicarbonate et le sel.</li>'
            .'<li>Ajouter les œufs et le beurre en dés, puis pétrir (ou mixer) jusqu’à ce que la pâte soit bien homogène et forme une boule.</li>'
            .'<li>Partager la boule en 3 parts, façonner 3 boudins d’une vingtaine de centimètres de long et de 3 cm de diamètre, et les envelopper de film alimentaire.</li>'
            .'<li>Placer le tout 1 h au congélateur ou 2 h au réfrigérateur.</li>'
            .'<li>Au bout de ce temps, couper chaque boudin en 20 rondelles d’1/2 cm d’épaisseur avec un couteau bien tranchant.</li>'
            .'<li>Les déposer sur une plaque de cuisson préalablement couverte de papier sulfurisé.</li>'
            .'<li>Enfourner pour 12 à 15 minutes (plus ou moins selon les fours) à 180 °C. À la sortie du four, les laisser refroidir sur une grille.</li>'
            .'<li>Faire fondre le chocolat, le beurre et le sucre glace doucement au bain-marie : attendre que le chocolat et le beurre aient fondu avant de mélanger ! Retirer du bain-marie et mélanger de façon à obtenir une texture bien fluide et lisse.</li>'
            .'<li>L’étaler avec un pinceau à pâtisserie (ou à défaut une petite cuillère ou un couteau à beurre) sur la surface des biscuits.</li>'
            .'<li>Laisser le chocolat durcir avant de déguster.</li></ol>',
    ],
    [
        'title' => 'Petits sablés sans gluten',
        'category' => 'Recette facile',
        'pdf' => 'petits-sables-sans-gluten.pdf',
        'photo' => 'petit-sable.source.png',
        'classes' => ['JE Parc'],
        'ingredients' => [
            '500 | g | Farine (mix pâtisserie sans gluten)',
            '200 | g | Beurre ramolli',
            '250 | g | Sucre',
            '100 | g | Amandes en poudre | poudre-damande',
            '2 | | Œufs',
            '2 | c. à café | Levure',
            ' | | Épices au choix : cannelle, gingembre, muscade, girofle, mélange pour pain d’épices, ou zestes de citron ou d’orange',
            '1 | | Jaune d’œuf mélangé à un peu d’eau (pour dorer)',
        ],
        'instructions' => '<ol><li>Préchauffer le four à 200 °C (thermostat 6-7).</li>'
            .'<li>Mélanger dans une grande jatte la farine, la levure, le sucre, les épices et la poudre d’amandes.</li>'
            .'<li>Ajouter le beurre et l’œuf, puis travailler avec une fourchette si vous le faites à la main, sinon utiliser l’ustensile « feuille » de votre robot. Pétrir ensuite légèrement afin d’obtenir une boule. Ajouter éventuellement un peu d’eau si la pâte est trop sèche.</li>'
            .'<li>Étaler la pâte (faire 2 ou 3 boules) avec un rouleau et découper des formes à l’emporte-pièce (sapins, cœurs, étoiles…). Les disposer sur une plaque garnie de papier sulfurisé.</li>'
            .'<li>À l’aide d’un pinceau à pâtisserie, étaler un peu de jaune d’œuf avec de la cannelle sur les biscuits, pour qu’ils soient bien dorés, et enfourner 7 à 10 min (à surveiller en fonction du four). Laisser refroidir.</li>'
            .'<li>Glaçage royal : mélanger le sucre glace tamisé avec le blanc d’œuf et quelques gouttes de jus de citron jusqu’à obtention d’un mélange lisse, épais et homogène. Verser ce mélange dans une poche à douille ou un sac de congélation, nouer l’extrémité et faire un tout petit trou au bout. Décorer selon votre inspiration.</li>'
            .'<li>Laisser sécher, puis ranger dans une boîte hermétique en prenant soin d’interposer du papier sulfurisé entre chaque étage de sablés.</li></ol>',
    ],
    [
        'title' => 'Macarons aux deux chocolats',
        'category' => 'Recette élaborée',
        'pdf' => 'macarons-aux-deux-chocolats.pdf',
        'photo' => 'macarons-aux-deux-chocolats.source.png',
        'classes' => ['JE Parc', '3ème Classe', '11ème Classe'],
        'ingredients' => [
            '8 | | Blancs d’œufs',
            '400 | g | Sucre semoule fin',
            '500 | g | Poudre d’amandes | poudre-damande',
            '100 | g | Chocolat noir',
            '100 | g | Chocolat blanc',
        ],
        'instructions' => '<p>Râper finement les deux chocolats dans deux saladiers séparés. Mélanger chaque chocolat avec 250 g de poudre d’amandes.</p>'
            .'<p>Battre les blancs en neige ferme. Ajouter peu à peu le sucre en continuant à battre à vitesse moyenne. Incorporer délicatement, avec une spatule, la moitié de la meringue dans chaque masse chocolat/amandes.</p>'
            .'<p>À l’aide de deux cuillères à café, déposer des petits tas de la grosseur d’une petite noix sur une tôle beurrée et farinée ou recouverte de papier sulfurisé, en les espaçant suffisamment. Dresser deux plaques, une noire, une blanche.</p>'
            .'<p>Faire cuire à 130/150 °C (th. 4-5) pendant 20 à 25 min. Surveiller la cuisson : les macarons doivent sécher doucement mais ne pas brunir.</p>'
            .'<p>Après refroidissement, assembler un macaron noir et un blanc avec une touche de chocolat fondu. Laisser durcir avant de ranger.</p>'
            .'<p>Il est possible de ne pas assembler les deux macarons et de les laisser tout noirs et tout blancs.</p>',
    ],
    [
        'title' => 'Petits sablés à l’orange',
        'category' => 'Recette facile',
        'pdf' => 'petits-sables-a-l-orange.pdf',
        'photo' => 'petit-sable-orange.source.png',
        'classes' => ['JE Balcon'],
        'ingredients' => [
            '500 | g | Farine',
            '200 | g | Sucre',
            '3 | | Œufs',
            '150 | g | Beurre',
            '1 à 2 | | Zestes d’orange (ou mélange orange/citron)',
            '1 | | Jaune d’œuf + un peu de lait (pour dorer les sablés)',
        ],
        'instructions' => '<ol><li>Émietter d’abord le beurre dans le mélange farine/sucre, ajouter les zestes puis les œufs. Former une boule compacte. Aller vite, ne pas trop pétrir pour garder l’aspect sablé.</li>'
            .'<li>Étaler la pâte sur un plan de travail fariné, ou entre deux feuilles de papier cuisson, sur une épaisseur de 5 mm.</li>'
            .'<li>Détailler des biscuits avec un ou plusieurs emporte-pièces de votre choix.</li>'
            .'<li>Mélanger 1 jaune d’œuf avec un peu de lait et badigeonner les sablés avec ce mélange à l’aide d’un pinceau.</li>'
            .'<li>Placer 15 minutes au réfrigérateur, puis enfourner 15 minutes dans un four préchauffé à 200 °C.</li></ol>',
    ],
    [
        'title' => 'Pain d’épices de Nuremberg',
        'category' => 'Recette élaborée',
        'pdf' => 'pain-d-epices-de-nuremberg.pdf',
        'photo' => 'pain-d-epices-de-nuremberg.source.png',
        'classes' => ['JE Balcon', '13ème Classe'],
        'ingredients' => [
            '200 | g | Farine',
            '150 | g | Sucre',
            '100 | g | Beurre',
            '1 | | Zeste de citron',
            '1 | c. à café | Mélange pour pain d’épices',
            '50 | g | Orangeat finement haché',
            '140 | g | Citronat finement haché',
            '3 | | Œufs',
            '140 | g | Amandes râpées',
            '140 | g | Noisettes râpées',
            '200 | g | Sucre glace (glaçage au citron)',
            '1 | c. à soupe | Jus de citron (glaçage au citron)',
            '200 | g | Chocolat pâtissier (ou glaçage au chocolat)',
            '1/2 | c. à café | Huile (glaçage au chocolat)',
        ],
        'instructions' => '<p>Battre les œufs avec le sucre en crème. Ajouter les épices et bien mélanger. Ajouter les autres ingrédients.</p>'
            .'<p>Former de petits tas (de la grosseur d’une bonne noix) sur du papier sulfurisé. Les aplatir un peu avec le dos d’une cuillère.</p>'
            .'<p>Cuire au four à 200 °C (th. 6-7) pendant 25 min.</p>'
            .'<p>Après refroidissement :</p><ul><li>glacer chaque pain d’épices avec le mélange sucre glace/jus de citron,</li><li>ou le recouvrir avec le chocolat fondu additionné d’huile.</li></ul>'
            .'<p>Bien laisser sécher avant de ranger dans une boîte en fer, en séparant chaque rangée par une feuille de papier sulfurisé découpée aux dimensions de la boîte.</p>',
    ],
    [
        'title' => 'Baisers diamantés au chocolat',
        'category' => 'Recette facile',
        'pdf' => 'baisers-diamantes-au-chocolat.pdf',
        'photo' => 'baisers-chocolat.source.png',
        'classes' => ['1ère Classe'],
        'ingredients' => [
            '150 | g | Cacao non sucré',
            '150 | g | Poudre d’amande | poudre-damande',
            '240 | g | Beurre mou',
            '250 | g | Sucre',
            '300 | g | Farine',
            '3 | | Jaunes d’œufs',
        ],
        'instructions' => '<ol><li>Mélanger le cacao, la poudre d’amande, le sucre et la farine.</li>'
            .'<li>Travailler ce mélange avec le beurre à la main. Lorsque le beurre est bien incorporé, ajouter le jaune d’œuf. Ne pas trop travailler la pâte et former une boule. L’envelopper d’un film alimentaire et réserver 1 heure au frais.</li>'
            .'<li>Préchauffer le four à 170 °C. Prélever des noix de pâte et former des petites boules. Les rouler dans une assiette de sucre semoule. Les déposer sur une plaque garnie de papier sulfurisé.</li>'
            .'<li>Enfourner pour 15 min. À la sortie du four, les gâteaux sont encore mous, comme des cookies, mais ils deviennent fermes en refroidissant.</li></ol>'
            .'<p>Ranger les biscuits refroidis dans une boîte hermétique et conserver au sec.</p>',
    ],
    [
        'title' => 'Macarons aux noisettes',
        'category' => 'Recette élaborée',
        'pdf' => 'macarons-aux-noisettes.pdf',
        'photo' => 'macarons-aux-noisettes.source.png',
        'classes' => ['1ère Classe'],
        'ingredients' => [
            '8 | | Blancs d’œufs',
            '500 | g | Sucre semoule fin',
            '750 | g | Noisettes en poudre | poudre-de-noisette',
            '125 | g | Noisettes entières',
            '5 | paquets | Sucre vanillé',
        ],
        'instructions' => '<p>Battre les blancs en neige ferme. Ajouter peu à peu le sucre en continuant à battre à vitesse moyenne. Ajouter ensuite les autres ingrédients pour obtenir une pâte, à laisser reposer un peu au froid.</p>'
            .'<p>Poser des petits tas sur une tôle beurrée et farinée ou recouverte de papier sulfurisé, en les espaçant suffisamment.</p>'
            .'<p>Placer une noisette entière sur chaque macaron en l’enfonçant légèrement.</p>'
            .'<p>Faire cuire à 130/150 °C (th. 4-5) pendant 20 à 25 min. Surveiller la cuisson : les macarons doivent sécher mais ne pas brunir.</p>',
    ],
    [
        'title' => 'Palets aux noisettes',
        'category' => 'Recette facile',
        'pdf' => 'palets-aux-noisettes.pdf',
        'photo' => 'palet-noisette.source.png',
        'classes' => ['2ème Classe', '7ème Classe'],
        'ingredients' => [
            '250 | g | Beurre mou',
            '125 | g | Sucre roux',
            '1 | c. à café | Vanille liquide',
            '375 | g | Farine complète de petit épeautre',
            '125 | g | Poudre de noisettes complète | poudre-de-noisette',
            '1 | pincée | Sel',
            '1 | | Œuf',
            '150 | g | Chocolat pâtissier assez fort, plus de 60 % (glaçage)',
        ],
        'instructions' => '<h3>La pâte sablée</h3><ol><li>Réduire le beurre en pommade à la fourchette.</li><li>Rajouter le sucre,</li><li>puis l’œuf,</li><li>la pincée de sel,</li><li>la cuillère à café de vanille liquide.</li><li>Mettre la farine et pétrir d’une main pour former une boule bien homogène.</li></ol>'
            .'<h3>Les rouleaux aux noisettes</h3><ol><li>Rajouter la poudre de noisettes.</li><li>Former 1 à 4 rouleaux de 4 cm de diamètre (la taille d’un bouchon de bouteille de lait) et lisser les extrémités.</li><li>Les emballer dans de la cellophane ou équivalent.</li><li>Les mettre au frigo une nuit.</li></ol>'
            .'<p>Astuce : pour éviter que le fond du rouleau ne s’aplatisse et que les palets soient déformés, découper le tube en carton d’un rouleau d’essuie-tout et mettre les rouleaux de pâte dedans.</p>'
            .'<h3>Les palets</h3><ol><li>Déballer les rouleaux et les couper en tranches de 8 mm maximum avec un couteau bien aiguisé.</li><li>Mettre sur une tôle beurrée et farinée ou recouverte de papier sulfurisé.</li><li>Faire cuire à 170 °C (th. 5-6) pendant 10 à 15 minutes : il faut que les palets soient cuits au milieu (couleur homogène).</li><li>Enlever les gâteaux de la plaque et bien laisser refroidir sur une grille (au moins 2 h).</li></ol>'
            .'<h3>Le chocolat</h3><ol><li>Faire chauffer le chocolat au bain-marie, puis tremper les palets à moitié dans le chocolat fondu.</li><li>Faire sécher sur du papier sulfurisé environ 2 h.</li><li>Lorsque l’ensemble est bien sec, mettre les palets dans des boîtes en métal hermétiques en séparant les couches avec des feuilles de papier sulfurisé.</li></ol>',
    ],
    [
        'title' => 'Biscuits italiens aux oranges confites',
        'category' => 'Recette élaborée',
        'pdf' => 'biscuits-italiens-aux-oranges-confites.pdf',
        'photo' => 'biscuits-italiens-aux-oranges-confites.source.png',
        'classes' => ['2ème Classe', '8ème Classe'],
        'ingredients' => [
            '430 | g | Farine',
            '1 | sachet | Levure chimique',
            '320 | g | Sucre en poudre',
            '120 | g | Beurre salé',
            '4 | | Œufs',
            '6 | c. à soupe | Café lyophilisé',
            '150 | g | Oranges confites',
        ],
        'instructions' => '<ul><li>Préchauffer le four à 180 °C.</li><li>Préparer une ou plusieurs plaques recouvertes de papier sulfurisé.</li></ul>'
            .'<ol><li>Mélanger la farine, la levure et le sucre, puis incorporer le beurre coupé en morceaux jusqu’à l’obtention d’une pâte sableuse type crumble. Incorporer les œufs et mélanger le tout jusqu’à ce que la pâte soit lisse.</li>'
            .'<li>Ajouter les petits morceaux d’oranges confites et le café. Mélanger, puis transférer la pâte sur un plan de travail fariné. La diviser en 4 parts égales et former 4 bûches d’environ 20 cm aux bords aplatis.</li>'
            .'<li>Faire cuire 30 min jusqu’à ce que la pâte soit légèrement dorée. Sortir les plaques du four et laisser refroidir 20 min. Baisser la température du four à 160 °C (chaleur tournante) ou 170 °C (four traditionnel).</li>'
            .'<li>Couper les bûches en biais en tranches d’environ 1 cm d’épaisseur. Les disposer sur la plaque en les espaçant. Remettre au four pendant 7 min, les retourner et les remettre 7 min au four pour qu’elles soient dorées des deux côtés.</li>'
            .'<li>Laisser refroidir, puis casser en petits morceaux de 3-4 cm de côté.</li>'
            .'<li>Ranger les biscuits refroidis dans une boîte hermétique et conserver au sec.</li></ol>',
    ],
    [
        'title' => 'Nids de guêpes',
        'category' => 'Recette facile',
        'pdf' => 'nids-de-guepes.pdf',
        'photo' => 'nids-de-guepes.source.png',
        'classes' => ['3ème Classe'],
        'ingredients' => [
            '5 | | Blancs d’œufs de gros calibre',
            '300 | g | Sucre semoule fin',
            '350 | g | Amandes entières',
            '180 | g | Chocolat râpé',
            '2 | sachets | Sucre vanillé',
            '2 | pointes de couteau | Cannelle',
        ],
        'instructions' => '<p>La préparation de ces biscuits est similaire à celle des meringues. Les ingrédients doivent être délicatement incorporés dans les blancs d’œufs battus en neige très ferme avec le sucre. On peut remplacer 1 pointe de couteau de cannelle par une pointe de couteau de poudre de clous de girofle.</p>'
            .'<ol><li>Passer les amandes dans un mixeur (ne pas les réduire en poudre, c’est meilleur avec de petits morceaux !).</li>'
            .'<li>Battre les blancs en neige ferme.</li>'
            .'<li>Ajouter peu à peu le sucre en continuant à battre à vitesse moyenne.</li>'
            .'<li>Ajouter ensuite délicatement les autres ingrédients pour obtenir une pâte type meringue.</li>'
            .'<li>Poser des petits tas (pas plus de 3 cm de diamètre) sur une tôle beurrée et farinée ou recouverte de papier sulfurisé, en les espaçant suffisamment.</li>'
            .'<li>Faire cuire à 130/150 °C (th. 4-5) pendant 25 à 30 min (surveiller la cuisson : les nids de guêpes doivent sécher mais ne pas brunir).</li></ol>'
            .'<p>Ranger les biscuits refroidis dans une boîte hermétique et conserver au sec.</p>',
    ],
    [
        'title' => 'Chapeau des hussards',
        'category' => 'Recette facile',
        'pdf' => 'chapeau-des-hussards.pdf',
        'photo' => 'chapeau-hussard.source.png',
        'classes' => ['4ème Classe'],
        'ingredients' => [
            '400 | g | Farine',
            '320 | g | Beurre',
            '120 | g | Sucre',
            '160 | g | Poudre de noisette | poudre-de-noisette',
            '1 | | Jaune d’œuf',
            '1 | sachet | Sucre vanillé',
            ' | | Confiture ou gelée de fruits rouges',
        ],
        'instructions' => '<ol><li>Mélanger dans un saladier la farine, la poudre de noisette et le beurre coupé en tout petits morceaux.</li>'
            .'<li>Mélanger le jaune d’œuf et les sucres. Incorporer le tout au mélange farine/beurre.</li>'
            .'<li>Mélanger avec une cuillère en bois, puis finir de pétrir sur un plan de travail. Assembler le tout afin d’obtenir une boule homogène. Mettre au frais au moins 2 heures.</li>'
            .'<li>Partager la pâte en 2 parties et former 2 rouleaux de 30 cm de long et d’environ 3 cm de diamètre. Remettre au frais 1 heure.</li>'
            .'<li>Couper les rouleaux en tronçons d’1 cm d’épaisseur et façonner des billes avec des mains froides. Former des petites boules et faire un trou au centre (pour y mettre de la confiture après la cuisson) avec le bout fin d’une cuillère en bois ou le manche d’un pinceau pâtissier. Attention : faire des boules régulières et pas trop grosses (2 cm), car elles grossissent à la cuisson. Les disposer sur une tôle recouverte d’une feuille de papier sulfurisé en les espaçant suffisamment.</li>'
            .'<li>Faire cuire à 160 °C (th. 5-6) pendant 15 à 20 min.</li>'
            .'<li>Bien remuer à la fourchette la confiture ou la gelée pour la fluidifier. Poser très délicatement assez de confiture pour remplir le trou. Saupoudrer de sucre glace.</li></ol>'
            .'<p>Faire sécher une nuit avant de les ranger dans une boîte en fer.</p><p>Alternative : garnir les gâteaux de confiture avant la cuisson.</p>',
    ],
    [
        'title' => 'Sablés spirale ou damier',
        'category' => 'Recette élaborée',
        'pdf' => 'sables-spirale-ou-damier.pdf',
        'photo' => 'sables-spirale-ou-damier.source.png',
        'classes' => ['4ème Classe', '12ème Classe'],
        'ingredients' => [
            '500 | g | Farine',
            '2 | c. à café | Levure chimique',
            '200 | g | Sucre semoule',
            '2 | sachets | Sucre vanillé',
            '300 | g | Beurre mou',
            '2 | c. à soupe | Eau',
            '60 | g | Cacao',
        ],
        'instructions' => '<ol><li>Dans un récipient, mélanger la farine, la levure chimique, le sucre, le sucre vanillé et les cuillères à soupe d’eau. Mélanger, puis ajouter le beurre mou coupé en morceaux. Mélanger le tout jusqu’à obtention d’une pâte homogène.</li>'
            .'<li>Pour des spirales, prélever 1/3 de la pâte et y ajouter le cacao. Pour des damiers, prélever la moitié de la pâte et y ajouter le cacao. Mélanger. Si la pâte chocolatée est trop friable, ajouter une cuillère à soupe d’eau.</li>'
            .'<li>Filmer les boules de pâte et laisser reposer au réfrigérateur.</li>'
            .'<li>Pour faire des spirales : étaler la pâte blanche sur un plan de travail fariné. Étaler ensuite la pâte chocolatée et la placer sur la pâte blanche (et non l’inverse, la quantité de pâte au chocolat étant moins importante). Les deux pâtes doivent avoir une épaisseur bien homogène. Une fois les pâtes superposées, rouler le tout et placer le rouleau dans du film alimentaire, puis laisser reposer au frais pendant au moins 30 min.<br>Pour faire des damiers : faire deux rouleaux de pâte chocolatée et deux rouleaux de pâte blanche, de même longueur et de même épaisseur. Les superposer deux à deux en alternant pâte blanche et pâte chocolatée. Mettre dans du film alimentaire et réserver au frais pendant au moins 30 min.</li>'
            .'<li>Préchauffer le four à 180 °C (th. 6).</li>'
            .'<li>Une fois la pâte bien durcie, couper des rondelles ou tranches de 2 à 3 mm (les tranches gonflent à la cuisson). Pour avoir des biscuits bien ronds (spirales) ou bien carrés (damiers), tourner le boudin d’1/4 de tour à la découpe de chaque tranche.</li>'
            .'<li>Placer les biscuits sur une plaque munie de papier sulfurisé et enfourner durant 8 minutes environ (surveiller la cuisson, cela dépend beaucoup du four). Laisser bien refroidir (ils durcissent à froid).</li>'
            .'<li>On peut amalgamer les restes de pâtes (ne pas les mélanger, juste en faire une boule compacte), étaler l’ensemble et découper à l’emporte-pièce en forme d’étoile, de cœur… Cela fait des biscuits marbrés tout aussi bons !</li></ol>'
            .'<p>Ranger les biscuits refroidis dans une boîte hermétique et conserver au sec.</p>',
    ],
    [
        'title' => 'Croissants à la vanille',
        'category' => 'Recette facile',
        'pdf' => 'croissants-a-la-vanille.pdf',
        'photo' => 'croissant-vanille.source.png',
        'classes' => ['5ème Classe', '9ème Classe'],
        'ingredients' => [
            '350 | g | Farine',
            '150 | g | Sucre',
            '1 | flacon | Vanille liquide',
            '150 | g | Poudre d’amandes | poudre-damande',
            '300 | g | Beurre froid',
            '3 | | Jaunes d’œufs',
            '150 | g | Mélange sucre glace/sucre vanillé (pour saupoudrer après cuisson)',
        ],
        'instructions' => '<p>Faire une pâte rapidement. Laisser reposer 2 heures au frais.</p>'
            .'<p>Former des rouleaux de 4 cm de diamètre. Remettre au frais pour qu’ils durcissent.</p>'
            .'<p>Couper en rondelles de 2 cm à peine, puis chaque rondelle en 2 pour obtenir des demi-lunes. Former à la main des croissants de lune.</p>'
            .'<p>Faire cuire sur une tôle recouverte de papier sulfurisé à 160/170 °C (th. 5-6) pendant 15 min.</p>'
            .'<p>Attendre 5 à 10 min après la sortie du four que les gâteaux refroidissent un peu, puis les rouler dans le mélange sucre glace/sucre vanillé.</p>'
            .'<p>Attention :</p><ul><li>les croissants doivent rester jaune clair : bien surveiller la cuisson ;</li><li>les gâteaux sont très fragiles à la sortie du four : attendre un peu avant de les manipuler.</li></ul>',
    ],
    [
        'title' => 'Amaretti',
        'category' => 'Recette élaborée',
        'pdf' => 'amaretti.pdf',
        'photo' => 'amaretti.source.png',
        'classes' => ['5ème Classe', '6ème Classe'],
        'ingredients' => [
            '3 | c. à soupe | Farine à pâtisserie',
            '3 | c. à soupe | Fécule de maïs',
            '3 | c. à café | Cannelle moulue',
            '480 | g | Sucre fin',
            '3 | c. à café | Zestes de citron coupés fin',
            '360 | g | Poudre d’amandes | poudre-damande',
            '6 | | Blancs d’œufs',
            '160 | g | Sucre glace',
        ],
        'instructions' => '<ul><li>Préparer une ou plusieurs plaques recouvertes de papier sulfurisé.</li></ul>'
            .'<ol><li>Mélanger la farine, la fécule de maïs, la cannelle et la moitié du sucre dans un saladier. Ajouter les zestes de citron et la poudre d’amande.</li>'
            .'<li>Battre les blancs en neige presque ferme et continuer à battre en ajoutant progressivement le reste de sucre. Le mélange devient brillant.</li>'
            .'<li>Ajouter les blancs en neige à la préparation d’ingrédients secs et mélanger progressivement (à l’aide d’une fourchette tout en métal) jusqu’à ce que l’ensemble forme une pâte molle.</li>'
            .'<li>Pour façonner les amaretti, prélever l’équivalent d’une grosse noisette de pâte et la rouler en boule (sans écraser !) avec les mains mouillées d’eau froide ou recouvertes d’huile (pour que la pâte n’attache pas aux doigts). Les disposer sur la plaque de cuisson relativement espacés, car ils vont grossir. Laisser sécher à l’air libre 1 heure (si possible dans un endroit sec).</li>'
            .'<li>Préchauffer le four à 180 °C. Saupoudrer les biscuits de sucre glace au travers d’un tamis et cuire pendant 15 à 20 min, jusqu’à ce que les amaretti soient secs et légèrement dorés. Les transférer sur une grille pour refroidir. On peut ainsi préparer de nouvelles plaques pendant le refroidissement des biscuits cuits.</li>'
            .'<li>Placer les biscuits refroidis dans une boîte hermétique. Séparer les différents étages avec du papier sulfurisé. Garder au sec.</li></ol>',
    ],
    [
        'title' => 'Croissants Mocca',
        'category' => 'Recette facile',
        'pdf' => 'croissants-mocca.pdf',
        'photo' => 'croissant-moka.source.png',
        'classes' => ['6ème Classe', '13ème Classe'],
        'ingredients' => [
            '450 | g | Farine',
            '1 | sachet | Levure',
            '160 | g | Sucre',
            '300 | g | Beurre doux ramolli',
            '4 | c. à café | Poudre de café instantané',
            '250 | g | Chocolat noir à fondre (couverture)',
            '100 | g | Beurre doux (couverture)',
            ' | | Poudre de noisette (couverture)',
        ],
        'instructions' => '<ul><li>Préchauffer le four à 180 °C.</li><li>Préparer une ou plusieurs plaques recouvertes de papier sulfurisé.</li></ul>'
            .'<ol><li>Battre le beurre et le sucre jusqu’à ce que le mélange soit lisse et crémeux. Ajouter la farine, le café lyophilisé et la levure. Mélanger le tout jusqu’à former une pâte bien lisse et souple.</li>'
            .'<li>Pour chaque biscuit, prélever 1 bonne cuillère à café de pâte pour former un petit croissant d’environ 3 cm. Placer les biscuits sur la plaque de cuisson.</li>'
            .'<li>Cuire pendant 10 min et transférer sur une grille pour refroidir. On peut ainsi préparer de nouvelles plaques pendant le refroidissement des biscuits cuits.</li>'
            .'<li>Faire fondre le chocolat et le beurre au bain-marie.</li>'
            .'<li>Quand le chocolat est bien lisse, plonger les croissants refroidis dans le chocolat fondu à l’aide d’une cuillère et les ressortir avec une fourchette pour enlever l’excédent de chocolat. Les placer sur une feuille de papier sulfurisé et recouvrir la moitié du croissant de poudre de noisettes.</li>'
            .'<li>Dès que le chocolat est sec, placer les biscuits dans une boîte hermétique. Séparer les différents étages avec du papier sulfurisé. Garder au sec.</li></ol>',
    ],
    [
        'title' => 'Sablés viennois (Sprits)',
        'category' => 'Recette élaborée',
        'pdf' => 'sables-viennois-sprits.pdf',
        'photo' => 'sables-viennois-sprits.source.png',
        'classes' => ['7ème Classe', '10ème Classe'],
        'ingredients' => [
            '250 | g | Beurre mou',
            '55 | g | Sucre glace',
            '225 | g | Farine',
            '75 | g | Maïzena',
            '300 | g | Chocolat au lait ou noir',
        ],
        'instructions' => '<ol><li>Mélanger les ingrédients secs (farine, maïzena, sucre glace).</li>'
            .'<li>Ajouter le beurre mou.</li>'
            .'<li>À l’aide d’une cuillère en bois ou d’un batteur électrique, incorporer le beurre : mélanger juste assez pour obtenir une pâte, puis s’arrêter (afin d’éviter d’incorporer trop d’air).</li>'
            .'<li>Placer la pâte dans une poche à douille cannelée.</li>'
            .'<li>Former des zigzags sur une plaque recouverte de papier sulfurisé en veillant à les espacer.</li>'
            .'<li>Enfourner dans un four préchauffé à 170 °C pendant 15 min ou jusqu’à ce qu’ils soient légèrement dorés.</li>'
            .'<li>Retirer du four, déposer les sablés viennois sur une grille et laisser refroidir.</li>'
            .'<li>Faire fondre le chocolat au bain-marie et y tremper la moitié de chaque sablé viennois. Déposer sur une grille pour laisser sécher le chocolat.</li></ol>',
    ],
    [
        'title' => 'Étoiles à la cannelle',
        'category' => 'Recette facile',
        'pdf' => 'etoiles-a-la-cannelle.pdf',
        'photo' => 'etoile-cannelle.source.png',
        'classes' => ['8ème Classe', '11ème Classe'],
        'ingredients' => [
            '550 | g | Poudre d’amande | poudre-damande',
            '500 | g | Sucre en poudre',
            '4 | | Blancs d’œufs',
            '30 | g | Cannelle',
            '2 | c. à soupe | Jus de citron',
        ],
        'instructions' => '<ul><li>Préparer une ou plusieurs plaques recouvertes de papier sulfurisé.</li></ul>'
            .'<ol><li>Battre les blancs en neige ferme. Ajouter peu à peu le sucre, tout en continuant à battre encore pendant quelques minutes. Prélever 1 tasse de cette neige, qui servira pour le glaçage (on peut aussi réaliser un glaçage au citron, voir plus loin).</li>'
            .'<li>Incorporer dans le reste de neige la poudre d’amande, la cannelle et le jus de citron. Ramener en boule. Abaisser la pâte sur <strong>1 cm maximum</strong> (ils doivent être épais, mais pas trop !). Sur un plan de travail saupoudré de sucre cristallisé ou en poudre, découper à l’emporte-pièce en forme d’étoiles, en trempant souvent la forme dans l’eau afin d’éviter de coller.</li>'
            .'<li>Dresser sur la plaque de cuisson et glacer avec la neige prévue à cet effet. Laisser sécher plusieurs heures.</li>'
            .'<li>Préchauffer le four à 240 °C. Enfourner et faire cuire 5 minutes.</li>'
            .'<li>Placer les biscuits dans une boîte hermétique. Séparer les différents étages avec du papier sulfurisé. Garder au sec.</li></ol>'
            .'<p>Remarque : il est possible de réaliser un glaçage au citron. Le glaçage se fait dans ce cas après cuisson : mélanger 250 g de sucre glace à 2 c. à soupe de jus de citron.</p>',
    ],
    [
        'title' => 'Délices aux noisettes',
        'category' => 'Recette élaborée',
        'pdf' => 'delices-aux-noisettes.pdf',
        'photo' => 'delices-aux-noisettes.source.png',
        'classes' => ['9ème Classe'],
        'ingredients' => [
            '200 | g | Farine de riz',
            '140 | g | Fécule de maïs',
            '70 | g | Fécule de pommes de terre',
            '1,5 | sachet | Sucre vanillé',
            '140 | g | Sucre en poudre',
            '140 | g | Noisettes en poudre | poudre-de-noisette',
            '200 | g | Beurre végétal (ou 100 ml d’huile neutre)',
            '3 | | Petits œufs',
            '100 | g | Chocolat noir (couverture)',
            '50 | g | Beurre (couverture)',
            '50 | g | Sucre glace (couverture)',
            ' | | Noisettes râpées (couverture, facultatif)',
        ],
        'instructions' => '<p>Recette sans gluten et sans lactose.</p>'
            .'<ul><li>Préchauffer le four à 180 °C.</li><li>Préparer une ou plusieurs plaques recouvertes de papier sulfurisé.</li></ul>'
            .'<ol><li>Mélanger la farine, les fécules, les sucres et la poudre de noisettes.</li>'
            .'<li>Ajouter le beurre et les œufs et mélanger le tout jusqu’à obtenir une pâte lisse. Former une boule, l’envelopper dans du cellophane et laisser reposer au frais 1 heure.</li>'
            .'<li>Pour chaque biscuit, prélever l’équivalent d’une cuillère à café de pâte et rouler de petites boules avec les mains mouillées ou recouvertes d’huile, afin d’éviter que la pâte colle aux doigts. Si la pâte casse, ne pas hésiter à l’écraser avec les doigts pour l’amalgamer et recommencer à former une boule. Placer et espacer les boules sur la plaque de papier cuisson.</li>'
            .'<li>Cuire pendant 10 min ou jusqu’à ce qu’ils soient bien dorés. Les laisser refroidir sur une grille pour un refroidissement complet et une évacuation maximale de l’humidité.</li>'
            .'<li>Faire fondre le chocolat avec le beurre et le sucre glace au bain-marie. Ne pas mélanger tant que le chocolat et le beurre ne sont pas fondus. Retirer du feu et mélanger : l’ensemble doit avoir une texture fluide et bien lisse. Il est possible à ce stade d’ajouter un peu de noisettes râpées, rapidement torréfiées à la poêle. Plonger la moitié de chaque petite boule dans le chocolat fondu et laisser refroidir (le côté sans chocolat posé sur la plaque ou la grille).</li>'
            .'<li>Ranger les biscuits refroidis dans une boîte hermétique et conserver au sec.</li></ol>',
    ],
    [
        'title' => 'Gâteaux de gingembre au miel',
        'category' => 'Recette facile',
        'pdf' => 'gateaux-de-gingembre-au-miel.pdf',
        'photo' => 'gingembre-miel.source.png',
        'classes' => ['10ème Classe'],
        'ingredients' => [
            '600 | g | Farine',
            '250 | g | Beurre mou',
            '250 | g | Sucre',
            '250 | g | Miel liquide ou onctueux, au goût pas trop fort',
            '1 | | Zeste de citron râpé (ou 8 gouttes d’huile essentielle de citron)',
            '1 | c. à soupe | Gingembre en poudre',
            '1 | c. à café | Poudre à lever ou bicarbonate de soude',
            '2 | c. à soupe | Café fort très froid',
        ],
        'instructions' => '<p>Battre ensemble le beurre, le sucre et le miel jusqu’à ce que le mélange soit crémeux. Y ajouter les épices et la poudre à lever diluée dans le café, puis la farine.</p>'
            .'<p>Abaisser la pâte à 1/2 cm d’épaisseur.</p>'
            .'<p>Découper des formes à l’emporte-pièce et les disposer bien espacées sur une tôle faiblement beurrée et farinée ou recouverte d’une feuille de papier sulfurisé.</p>'
            .'<p>Faire cuire à 150/160 °C (th. 5) pendant 15 min environ. Bien surveiller la cuisson, car les gâteaux brunissent vite à cause de la quantité importante de sucre et de miel. <strong>Ne pas trop faire cuire.</strong></p>'
            .'<p>Dès qu’ils sont refroidis, les mettre dans une boîte en fer.</p>'
            .'<p>Éviter les formes d’animaux, car ces sablés ne plaisent pas forcément aux enfants.</p>',
    ],
    [
        'title' => 'Biscuits croustillants noix de coco-chocolat',
        'category' => 'Recette facile',
        'pdf' => 'biscuits-noix-de-coco-chocolat.pdf',
        'photo' => 'biscuits-noix-de-coco-chocolat.source.png',
        'classes' => ['12ème Classe'],
        'ingredients' => [
            '320 | g | Beurre mou',
            '240 | g | Sucre roux',
            '250 | g | Noix de coco râpée | noix-de-coco-rapee',
            '360 | g | Farine',
            '1 | pincée | Fleur de sel',
            '4 | | Jaunes d’œufs',
            '400 | g | Chocolat noir (glaçage), ou 300 g de chocolat + 100 g de beurre doux',
        ],
        'instructions' => '<ol><li>Mélanger d’abord le beurre et le sucre roux, puis ajouter la noix de coco râpée, la farine et le sel. Mélanger jusqu’à obtenir une texture sableuse.</li>'
            .'<li>Ajouter les jaunes d’œufs et mélanger de nouveau. La pâte doit se rassembler et former une boule.</li>'
            .'<li>Étaler la pâte sur un plan de travail fariné, ou entre deux feuilles de papier cuisson, sur une épaisseur de 5 mm.</li>'
            .'<li>Détailler des biscuits avec un ou plusieurs emporte-pièces de votre choix.</li>'
            .'<li>Placer 15 minutes au réfrigérateur, puis enfourner 15 minutes dans un four préchauffé à 180 °C.</li>'
            .'<li>Pour le glaçage au chocolat, deux méthodes :<br>a. Hacher le chocolat noir. En faire fondre les 3/4 environ au bain-marie jusqu’à 55 °C maximum. Retirer du bain-marie et ajouter le reste du chocolat haché : la température redescend. Dès qu’elle atteint 28-29 °C, remettre quelques secondes au bain-marie pour faire remonter jusqu’à 31-32 °C.<br>b. Faire fondre 300 g de chocolat avec 150 g de beurre. Attendre que tout soit fondu pour mélanger.</li>'
            .'<li>Tremper la moitié de chaque biscuit dans le chocolat et déposer sur une plaque recouverte de papier sulfurisé. Avant refroidissement, saupoudrer la partie chocolatée avec un peu de noix de coco râpée.</li></ol>'
            .'<p>Laisser refroidir, puis ranger les biscuits dans une boîte hermétique et conserver au sec.</p>',
    ],
];
