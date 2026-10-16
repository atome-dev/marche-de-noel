<?php

/*
|--------------------------------------------------------------------------
| Concert
|--------------------------------------------------------------------------
|
| Billetterie HelloAsso du concert, à mettre à jour chaque année dans ce
| fichier (versionné). Les clés de l'API sont celles des sapins
| (trees.helloasso) : sans elles, la page affiche seulement la billetterie.
|
*/

return [

    // Identifiant de la billetterie (formulaire HelloAsso de type « Event »).
    'form_slug' => 'orchestre-des-eleves',

    // Adresse publique de la billetterie (lien et widget).
    'url' => 'https://www.helloasso-sandbox.com/associations/association-fictive-pour-tester-l-api/evenements/orchestre-des-eleves',

    // « link » : bouton vers la billetterie (nouvel onglet) ; « iframe » : widget intégré.
    'display' => 'iframe',

    // Durée (secondes) pendant laquelle la liste des billets est réutilisée sans interroger HelloAsso.
    'cache_seconds' => 60,

];
