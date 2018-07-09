<?php
require_once ( __DIR__ . '/../bootstrap.php' );

use core\libIncluderList;

use tpl\content;
use tpl\breadcrumb;
use tpl\addHtml;

use modal\deleteEntry;

libIncluderList::add_bootstrapTable();

// Création du conteneur de la page
$content = new content();

// Fil d'ariane
$breadcrumb = new breadcrumb();
$breadcrumb->addLink('Bootstrap table (ver. Sql)');
$content->append($breadcrumb);

// Modal suppression entrées
$modal = new deleteEntry( array('idModal'=>'myModal', 'idCallBack'=>'myTable'));
$content->append($modal);

// Datatable
$table = include ( __DIR__ . '/inc/list-sql-conf.php' );
$content->append(new addHtml($table));

// Affichage de la page
$rendu = $content->rendu();

// Chargement du template
include( __DIR__ . '/../template/default.php' );
