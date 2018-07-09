<?php
require_once ( __DIR__ . '/../bootstrap.php' );

# id
if (empty($_GET['id'])) { $id = ''; 		 }
else 					{ $id = $_GET['id']; }

use tpl\content;
use tpl\breadcrumb;
use tpl\tabs;
use tpl\container;
use tpl\addHtml;

use form\form;
use form\element;
use form\control;

// Titre de la page
$title = 'Test de formulaire';

// Poste
$poste = array(
				""									=> "",
				"Agent polyvalent"					=> "Agent polyvalent",
				"Assistant administratif"			=> "Assistant administratif",
				"Assistant(e) commercial(e)"		=> "Assistant(e) commercial(e)",
				"Chargé(e) d'opération"				=> "Chargé(e) d'opération",
				"Chargé(e) de SAV"					=> "Chargé(e) de SAV",
				"Chef de Projet Web Marketing"		=> "Chef de Projet Web Marketing",
				"Commercial sédentaire"				=> "Commercial sédentaire",
				"Développeur d'Applications Web"	=> "Développeur d'Applications Web",
				"Responsable d'équipe commerciale"	=> "Responsable d'équipe commerciale",
				"Responsable d'équipe logistique"	=> "Responsable d'équipe logistique",
				"Secrétaire Polyvalent(e)"			=> "Secrétaire Polyvalent(e)",
				"Rédacteur Web"						=> "Rédacteur Web",
				"Responsable pôle édition"			=> "Responsable pôle édition",
				);


// Création du conteneur de la page
$content = new content(6);

// Fil d'ariane
$breadcrumb = new breadcrumb();
$breadcrumb->addLink('Bootstrap table', '/exemples/listArray.php');
$breadcrumb->addLink('Formulaire');
$content->append($breadcrumb);

// Création du formulaire
$form = new form();
$form->hydrateAndInit(array(
				'name' 				=> 'firstForm',
				'table'				=> 'rh_salaries',
				'clePrimaireName'	=> 'id',
				'clePrimaireId'		=> $id,
				'urlSortie'			=> '/exemples/listArray.php',
));

// Ajout des boutons de contrôle du formulaire
$form->append(new control($form));

// Initialisation des onglets
$tabs = new tabs();
$tabs->hydrateAndInit(array(
				'form' => $form,
				'name' => 'FormTabs',
				'tabs' => array(
								'tab0 texte',
								'tab1 texte',
								'tab2 texte',
				)
));

// Onglet 1
$tabs->append(0, new element($form, 'radio',			'sexe',			'Civilite',		array('required'=>true, 'dataList' => array('M'=>'M.', 'F'=>'Mme') )));

$tabs->append(0, new element($form, 'text', 			'prenom', 		'Prénom', 		array('required'=>true)));

// Exemple de modification de la méthode save
$tabs->append(0, new element($form, 'text', 			'nom', 			'nom', 			array('required'=>true)));
//$tabs->append(0, new test\classElement($form, 'text', 'nom', 			'nom', 			array('required'=>true)));
$tabs->append(0, new addHtml($form->getSep()));

$tabs->append(0, new element($form, 'password', 		'statut', 		'Statut', 		array('required'=>true)));

$tabs->append(0, new element($form, 'textarea',			'contrat', 		'Contrat', 		array('required'=>true, 'placeholder'=>'test de placeholder')));
$tabs->append(0, new addHtml($form->getSep()));

$tabs->append(0, new element($form, 'ckeditor',			'divers_badge',	'Test', 		array('required'=>true, 'placeholder'=>'test de placeholder')));
$tabs->append(0, new addHtml($form->getSep()));

$tabs->append(0, new element($form, 'select',			'poste',		'Poste',		array('required'=>true, 'dataList'=>$poste)));
//$tabs->append(0, new element($form, 'select',			'poste',		'Poste',		array('required'=>true, 'dataList'=>$poste, 'typeChp'=>'selectpicker')));


$tabs->append(0, new element($form, 'checkbox',			'boolean', 		'Boolean',		array('required'=>true)));

// $tabs->append(0, new element($form, 'datetimepicker','DATE_MODIF', 	'Date modif', 	array('required'=>true, 'typeChp'=>'datetime')));

// Onglet 2
$container1 = new container();
$container1->hydrateAndInit(array(
		'form'			=> $form,
		'name'			=> 'container1',
		'container'		=> array(
								array(
									'colWidth' 		=> 'col-lg-4',
									'fieldset'		=> true,
									'fieldsetColor'	=> '#f00',
									'legend'		=> 'col1',
								),
								array(
									'colWidth' 		=> 'col-lg-4',
									'fieldset'		=> true,
									'legend'		=> 'col2',
								),
								array(
									'colWidth' 		=> 'col-lg-4',
								),
		)
));

$container1->append(0, new addHtml('test de texte dans la permière colonne de container'));
$container1->append(1, new addHtml('test de texte dans la seconde colonne de container'));
$container1->append(2, new addHtml('test de texte dans la troisième colonne de container'));

$tabs->append(1, $container1);

// Onglet 3
$tabs->append(2, new addHtml('test de texte dans la troisème onglet'));

// Ajout de l'ensemble des onglets dans le formulaire
$form->append($tabs);

// Insertion du formulaire dans la page
$content->append($form);

// Affichage de la page
$rendu = $content->rendu();

// Chargement du template
include( __DIR__ . '/../template/default.php' );
