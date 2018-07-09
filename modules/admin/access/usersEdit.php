<?php
/**
 * Edition des utilisateurs
 */
require_once ( __DIR__ . '/../../../bootstrap.php' );

// Instance PDO
$dbh = \core\dbSingleton::getInstance();

$url_sortie = '/modules/admin/access/usersList.php';

// id & titre de la page
if (empty($_GET['id'])) {
    $id = '';
    $title = 'Ajouter un utilisateur';
} else {
    $id = $_GET['id'];
    $title = 'Modifier un utilisateur';
}

use tpl\content;
use tpl\breadcrumb;
use tpl\tabs;
use tpl\container;
use tpl\addHtml;

use form\form;
use form\element;
use form\elemSelectMultiple;
use form\elemPassCrypt;
use form\control;

use admin\access\idParent;

// Création du conteneur de la page
$content = new content(4);

// Fil d'ariane
$breadcrumb = new breadcrumb();
$breadcrumb->addLink('Liste des utilisateurs', $url_sortie);
$breadcrumb->addLink($title);
$content->append($breadcrumb);

// Création du formulaire
$form = new form();
$form->hydrateAndInit(array(
				'name' 				=> 'usersEdit',
				'table'				=> 'users',
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
				'name' => 'usersEditTabs',
				'tabs' => array(
                                $title,
                                'Autorisations'
                          )));


// Onglet 1
$tabs->append(0, new element($form, 'radio',		'activ',		'Actif',             array('required'=>true, 'dataList' => array('0'=>'O',   '1'=>'I') )));
$tabs->append(0, new addHtml($form->getSep()));

$tabs->append(0, new element($form, 'radio',		'civilite',		'Civilité',          array('required'=>true, 'dataList' => array('0'=>'Mme', '1'=>'M.') )));
$tabs->append(0, new element($form, 'text',			'prenom', 		'Prénom',            array('required'=>true)));
$tabs->append(0, new element($form, 'text',			'nom', 		    'Nom',               array('required'=>true)));
$tabs->append(0, new addHtml($form->getSep()));

$tabs->append(0, new element($form, 'text',			'home',		    'Page d\'accueil'));
$tabs->append(0, new addHtml($form->getSep()));

$tabs->append(0, new element($form, 'text',			'login', 		'Identifiant',       array('required'=>true)));
$tabs->append(0, new elemPassCrypt($form, null,     'pass',   	    'Mot de passe',      array('required'=>true)));
$tabs->append(0, new addHtml($form->getSep()));

$tabs->append(0, new elemSelectMultiple($form,      'groupe',       'Groupes',           array(
                                                                                              'required'  =>  true,
                                                                                              'table_L'   => array('table' => 'users_group_link',  'key'=>'id',    'cle_1'=>'id_user', 'cle_2'=>'id_group'),
                                                                                              'table_2'   => array('table' => 'users_group',       'key'=>'id',    'chp'=>'name'),
                                                                                         )));


// Ajout de l'ensemble des onglets dans le formulaire
$form->append($tabs);

// Insertion du formulaire dans la page
$content->append($form);

// Affichage de la page
$rendu = $content->rendu();

// Chargement du template
include( __DIR__ . '/../../../template/default.php' );
