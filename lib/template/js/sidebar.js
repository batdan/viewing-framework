// JS file
$( '.sidebar div[role="sidebarFleche"]' ).click( function () {

    var idFleche = $(this).attr('id');

    // liste des li enfants
    var id = idFleche.split('_');
    id = id[1];

    // Plie / Déplie menu de gauche
    if ($(this).attr('class') == 'fleche fa fa-angle-right') {

        // Affichage des 'li' enfants
        $('li[idParent="li_' + id + '"').removeAttr('style');

        // La flèche est orientée vers le bas
        $(this).attr('class', 'fleche fa fa-angle-down');

    } else {

        // Affichage des 'li' enfants
        $('li[idParent="li_' + id + '"').attr('style', 'display:none;');

        // La flèche est orientée vers la droite
        $(this).attr('class', 'fleche fa fa-angle-right');
    }
});
