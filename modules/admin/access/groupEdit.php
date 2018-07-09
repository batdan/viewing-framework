<?php
/**
 * Edition des groupe d'utilisateurs
 */
require_once ( __DIR__ . '/../../../bootstrap.php' );

// Instance PDO
$dbh = \core\dbSingleton::getInstance();

$url_sortie = '/modules/admin/access/groupList.php';

// id & titre de la page
if (empty($_GET['id'])) {
    $id = '';
    $title = 'Ajouter un groupe';
} else {
    $id = $_GET['id'];
    $title = 'Modifier un groupe';
}

use tpl\content;
use tpl\breadcrumb;
use tpl\tabs;
use tpl\container;
use tpl\addHtml;

use form\form;
use form\element;
use form\control;

use admin\access\idParent;

// Création du conteneur de la page
$content = new content(7);

// Fil d'ariane
$breadcrumb = new breadcrumb();
$breadcrumb->addLink('Liste des groupes d\'utilisateurs', $url_sortie);
$breadcrumb->addLink($title);
$content->append($breadcrumb);

// Création du formulaire
$form = new form();
$form->hydrateAndInit(array(
				'name' 				=> 'groupEdit',
				'table'				=> 'users_group',
				'clePrimaireName'	=> 'id',
				'clePrimaireId'		=> $id,
				'urlSortie'			=> $url_sortie,
));

// Ajout des boutons de contrôle du formulaire
$form->append(new control($form));

// Initialisation des onglets
$tabs = new tabs();
$tabs->hydrateAndInit(array(
				'form' => $form,
				'name' => 'groupEditTabs',
				'tabs' => array($title)
));

// Onglet 1
$tabs->append(0, new element($form, 'text',			'name', 		'Groupe',       array('required'=>true)));
$tabs->append(0, new element($form, 'textarea',		'description', 	'Description'));

// Ajout de l'ensemble des onglets dans le formulaire
$form->append($tabs);

// Insertion du formulaire dans la page
$content->append($form);

// Affichage de la page
$rendu = $content->rendu();

// Chargement du template
include( __DIR__ . '/../../../template/default.php' );
