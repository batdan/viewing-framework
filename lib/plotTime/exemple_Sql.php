<?php
require_once "../bootstrap.php";

use Zi\PlotTime\Stats_Sql;

$bdd			= 'offidem';

$chpDate		= 'DATE_CREA';		// Champ permettant de filtrer sur une plage de dates
$chpDateType	= 'datetime';		// date | time | datetime

$req1 = "SELECT 		__plageOuInterval__

						COUNT(ID)						AS count,

						AVG(ARRHES)						AS ARRHES_moy,
						AVG(Remise)	 					AS Remise_moy,
						AVG(PrixTotalClient) 			AS PrixTotalClient_moy,

						((AVG(ARRHES) + AVG(Remise) + AVG(PrixTotalClient)) / 3) AS test,

						AVG(PRIXREV)		 			AS PRIXREV_moy

		FROM 			O_FICH_CONTRAT

		WHERE 			__chpDate__ >= :plageDeb
		AND 			__chpDate__ <= :plageFin

		GROUP BY 		myInterval ASC";

$req = array($req1);

$hydrateReq = array();


///////////////////////////////////////////////////////////////////////////////
// Composition du tableau et des champs
$champs = array(
				'count'					=> array(
											'label'				=> 'Quantité',
											'align'				=> 'right',			// Il est possible de surclasser l'alignement par défaut
											'unite' 			=> '',				// Il est possible de surclasser l'unité par défaut
											'decimales'			=> 0,				// Il est possible de surclasser le nombre décimales par défault
										),
				'ARRHES_moy' 			=> array(
											'label'				=> 'Arrhes',		// Label de la ligne
											'graph'				=> true,			// Affichage de cette donnée dans le graph
											'graphOnLoad'		=> false,			// Affichage ou non de cette donnée au load du graph
											'graphColor'		=> '#e10f1a',		// Couleur de la pastille et dans le graph
											'groupLabel'		=> 'test',			// Sous groupe de 'test'
											'groupColor'		=> '#8a00ff',
										),
				'Remise_moy' 			=> array(
											'label'				=> 'Remise',
											'graph'				=> true,
											'graphOnLoad'		=> false,
											'graphColor'		=> '#2f2e7c',
											'groupLabel'		=> 'test',
											'groupColor'		=> '#8a00ff',
										),
				'PrixTotalClient_moy'	=> array(
											'label'				=> 'Prix total client',
											'graph'				=> true,
											'graphOnLoad'		=> true,
											'graphColor'		=> '#009aae',
											'groupLabel'		=> 'test',
											'groupColor'		=> '#8a00ff',
										),
				'test'					=> array(
											'label'				=> 'Test de Groupe',
											'graph'				=> true,
											'graphOnLoad'		=> true,
											'graphColor'		=> '#8a00ff',
											'group'				=> true,
											'groupColor'		=> '#8a00ff',
										),
				'PRIXREV_moy' 			=> array(
											'label'				=> 'prix de revient',
											'graph'				=> true,
											'graphOnLoad'		=> false,
											'graphColor'		=> '#61af35',
										),
				);


// Mise en forme par défaut des valeurs du tableau
$fieldsForm = array(
					'align' 	=> 'right',
					'unite' 	=> '€',
					'decimales'	=> 2,
					);

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$grid = new Stats_Sql(array(
								'champs'			=>	$champs,

								'bdd' 				=> 	$bdd,					// clé absente = instance PDO : 'default'
								'req'				=>	$req,
								'hydrateReq'		=>	$hydrateReq,			// Tableau facultatif s'il est nécessaire de renseigner des valeurs de champs dans la clause WHERE
																				// Exemple : $hydrateReq = array(':chp1'=>$val1, ':chp2'=>$val2, ...)

								'fieldsForm'		=>	$fieldsForm,			// Mise en forme par défaut des colonnes (facultatif)

							//	'datedeb'			=>	$datedeb,
							//	'datefin'			=>	$datefin,
							//	'heuredeb'			=>	$heuredeb,
							//	'heurefin'			=>	$heurefin,

								'chpDate'			=>	$chpDate,
								'chpDateType'		=>	$chpDateType,

							//	'stepTimeline'		=>	$stepTimeline,
							//	'stepActiv'			=>	$stepActiv,				// Tableau : Possibilité de limiter en nombre les types d'interval du moteur de recherche

								'title' 			=> 	'Test de titre',
								'description'		=>	'Description de la statistique et de son intérêt',
								'width'				=> 	'calc(100% - 10px)',

								'colorMinMax'		=> 	true,

								'ligneTotaux'		=> 	true,
								'ligneTotauxConf'	=> 	array(
															'libelle'	=> 'TOTAL',
															'unite' 	=> '€',
															'decimales'	=> 2,
															'calcul'	=> 'somme',  	// choix : somme, moyenne, mediane, min, max
														),

								'graph'				=> 	true,
								'graphConf'			=> 	array(
															'type'		=> 'column',		// type : line, spline, area, areaspline, column, bar, pie, scatter, polar
															'title'		=> 'Premier test de titre',
															'subtitle'	=> 'Premier test de sous-titre',
															'uniteLabel'=> 'Montant',
															'unite'		=> '€',
															'height'	=> 400,
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
