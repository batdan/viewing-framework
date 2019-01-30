<?php
require_once "../bootstrap.php";

use vw\stats\data;


/**
 * Composition du tableau et des champs
 */
$lines	= array(
				'intitule1' => 	array(
									'label'			=> 'Intitulé 1',		// Label de la ligne
									'graph'			=> true,				// Affichage de cette donnée dans le graph
									'graphOnLoad'	=> false,				// Affichage ou non de cette donnée au load du graph
									'graphColor'	=> '#E10F1A',			// Couleur de la pastille et dans le graph
								),
				'intitule2' => 	array(
									'label'			=> 'Intitulé 2',
									'graph'			=> true,
									'graphOnLoad'	=> true,
									'graphColor'	=> '#2F2E7C',
								),
				'intitule3' => 	array(
									'label'			=> 'Intitulé 3',
									'graph'			=> true,
									'graphOnLoad'	=> true,
									'graphColor'	=> '#009AAE',
								),
				'intitule4' => 	array(
									'label'			=> 'Intitulé 4',
									'graph'			=> true,
									'graphOnLoad'	=> true,
									'graphColor'	=> '#61AF35',
								),
				);

/**
 * $fields : Création des colonnes pour test
 */
$timeline	= array('T1','T2','T3','T4','T5','T6','T7','T8','T9','T10');

$fields		= array();
foreach ($timeline as $field) {
	$fields[$field] = array(
		'label' 	=> $field,
		'align' 	=> 'right',
		'unite' 	=> '€',
		'decimales'	=> 2,
	);
}


/**
 * $data : Création du tableau de données
 *
 * array (
 * 		[intitule1] => 	array (
 *								[label] => Intitulé 1
 *								[graphOnLoad] =>				Affichage de cette donnée dans le graph au chargement ( true | false )
 *								[graphColor] => #E10F1A			Couleur graphique + pastille intitulé ligne
 *								[values] => Array
 *									(
 *									[T1] => 24				Valeurs > interval de temps
 *									[T2] => 44
 *									...
 *						)
 */
$data 	= array();

$val = range(0, 50);
shuffle($val);

$a=0;
foreach ($lines as $k=>$v) {

	// Récupération des options des libellés
	foreach ($v as $k2=>$v2) {
		$data[$k][$k2] = $v2;
	}

	// Ajout des valeurs pour le test
	foreach ($timeline as $field) {
		$data[$k]['values'][$field] = $val[$a];
		$a++;
	}
}

///////////////////////////////////////////////////////////////////////////////


$grid = new data(array(
								'data' 				=> 	$data,

								'fields'			=> 	$fields,
								'title' 			=> 	'Test de titre',
								'description'		=>	'Description de la statistique et de son intérêt.',
								'width'				=> 	'calc(100% - 20px)',

								'colorMinMax'		=> 	true,

								'ligneTotaux'		=> 	true,
								'ligneTotauxConf'	=> 	array(
															'libelle'	=> 'TOTAL',
															'unite' 	=> '€',
															'decimales'	=> 2,
															'calcul'	=> 'somme',  			// choix : somme, moyenne, mediane, min, max
														),

								'graph'				=> 	true,
								'graphConf'			=> 	array(
															'type'		=> 'column',			// type : line, spline, area, areaspline, column, bar, pie, scatter, polar
															'title'		=> 'Premier test de titre',
															'subtitle'	=> 'Premier test de sous-titre',
															'uniteLabel'=> 'Montant',
															'unite'		=> '€',
															),

								'results_line' 		=> 	array(
															'somme'		=>	array(
																				'label' 	=> 'Somme',
																				'align' 	=> 'right',
																				'unite' 	=> '€',
																				'decimales'	=> 2,
																				'bg_label'	=> '#ffc48f',
																				'bg_value'	=> '#ffe4cc',
																			),
															'moyenne' 	=> 	array(
																				'label' 	=> 'Moyenne',
																				'align' 	=> 'right',
																				'unite' 	=> '€',
																				'decimales'	=> 2,
																				'bg_label'	=> '#fbff8f',
																				'bg_value'	=> '#fdffcf',
																			),
															'mediane'	=>	array(
																				'label' 	=> 'Médiane',
																				'align' 	=> 'right',
																				'unite' 	=> '€',
																				'decimales'	=> 2,
																				'bg_label'	=> '#ccff8f',
																				'bg_value'	=> '#eeffdb',
																			),
															'min'		=>	array(
																				'label' 	=> 'Min',
																				'align' 	=> 'right',
																				'unite' 	=> '€',
																				'decimales'	=> 2,
																				'bg_label'	=> '#8fffeb',
																				'bg_value'	=> '#c7fff5',
																			),
															'max'		=>	array(
																				'label' 	=> 'Max',
																				'align' 	=> 'right',
																				'unite' 	=> '€',
																				'decimales'	=> 2,
																				'bg_label'	=> '#ffc2ec',
																				'bg_value'	=> '#ffe9f8',
																			),
														),
						)
);

$grid->addBreadcrumb(array(
	'label'	=> 'Test de breadcrump ZiPlotTime',
	'url' 	=> '/test2.php'
))->addBreadcrumb(array(
	'label'	=> 'Test suite',
	'url' 	=> '/test2.php'
));

$aff = $grid->renderPage();

echo $aff;
