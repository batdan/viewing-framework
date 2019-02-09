<?php
namespace vw\stats;

use core\libIncluder;
use core\mongoDbSingleton;
use backoffice\bdd\MongoUtils;

/**
 * Création d'une statistique sur la base d'une requête MongoDb
 *
 * @author Daniel Gomes
 */
class noSqlDb extends base
{
	/**
	 * Instance MongoDb
	 */
	protected $connectCollection;


	/**
	 * $data : Tableau des valeurs
	 *
	 * 	array (
	 * 		[intitule1] => 	array (
     *								[label] 		=> Intitulé 1
     *								[graphOnLoad] 	=> Affichage de cette donnée dans le graph au chargement ( true | false )
     *								[graphColor] 	=> #E10F1A			// Couleur graphique + pastille intitulé ligne
     *								[values] 		=> Array(
	 *									[T1] => 24						// Valeurs > interval de temps
     *									[T2] => 44
     *									...
     *		)
     *		...
     * 	);
	 */
	protected $data;


	/**
	 * $champs : Tableau avec le nom des champs ou leur Alias s'ils en ont un
	 *
	 * array(
	 *		'nom_champ' = array (
	 *						'label' 		=> '...',		// nom affiché
	 *						'graphOnLoad'	=> '...',		// boolean : true | false
	 *						'graphColor'	=> '...',		// code couleur hexa : #...
	 *		),
	 *		...
	 *	);
	 */
	protected $champs;

	protected $mongoConf;		// Choix de la configuration MongoDb (non renseigné = default)
	protected $mongoInstances;	// Instances de connexion aux BDD mongoDb
	protected $mongoCollection;	// Choix de la collection
	protected $mongoOptions;	// Configuration des options de la requête


	protected $req;				// Requête MongoDb

	protected $addGroup;		// Permet de rajouter des filtres en plus des intervals de temps dans "$group"

	protected $reqCompar;		// Requête (comparaison)
	protected $hydrateReq;		// Tableau facultatif : Permet de passer des valeurs supplémentaires à la close WHERE d'un requête
								// Exemple : $hydrateReq = array(
								// 								':chp1'=>$val1,
								// 								':chp2'=>$val2,
								// 								...
								// 			 );

	protected $fieldsForm;		// Tableau facultatif : Mise en forme par défaut des colonnes ( align | unite | decimales )
								// 		Exemple : $fieldsForm = array('align'=>'right', 'unite'=>'€', 'decimales'=> 2);

	protected $datedeb;			// Date début au format 	: yyyy-mm-dd
	protected $datefin;			// Date fin au format  		: yyyy-mm-dd

	protected $datedebCompar;	// Date début au format 	: yyyy-mm-dd (comparaison)
	protected $datefinCompar;	// Date fin au format  		: yyyy-mm-dd (comparaison)

	protected $chpDate;			// Champ servant à filtrer sur la date
	protected $chpDateType;		// Type de champ date : date | time | datetime

	protected $stepTimeline;	// Choix de la durée d'un interval : annee | mois | semaine | jour | ...
	protected $stepActiv;		// Tableau facultatif : Activation ou non des types d'interval présents dans le moteur de recherche
								// Par défaut, ils sont tous présents

	protected $dtpDeb;			// Champ de recherche : début plage
	protected $dtpFin;			// Champ de recherche : fin plage
	protected $selectStep;		// Champ de recherche : Select interval

	protected $compar = false;	// Boolean : mode normal ou comparaison
	protected $dtpDebCompar;	// Champ de recherche : début plage (comparaison)
	protected $dtpFinCompar;	// Champ de recherche : fin plage (comparaison)
	protected $htmlCompar;		// Code HTML des datepicker (comparaison)
	protected $htmlEndForm='';
	protected $htmlEndLink='';
	protected $linkNormal;		// Lien moteur de recherche sans comparaison
	protected $linkCompar;		// Lien moteur de recherche avec comparaison

	protected $days = array(
		'Lundi',
		'Mardi',
		'Mercredi',
		'Jeudi',
		'Vendredi',
		'samedi',
		'Dimanche',
	);

	/**
	 * Tableau contenant tous les intervals pour renvoyer un 0 dans les cases vides
	 * @var array
	 */
	protected $intervals = array();


	public function sethtmlEndForm($htmlEndForm)
	{
		$this->htmlEndForm=$htmlEndForm;
	}

	public function sethtmlEndLink($htmlEndLink)
	{
		$this->htmlEndLink=$htmlEndLink;
	}

	public function postResultCompar($resultCompar){
		return $resultCompar;
	}


	public function postResult($result){
		return $result;
	}

	protected function getDefaultOptions()
	{
		return array_merge(
			parent::getDefaultOptions(),
			array(
				'data' 				=> array(),
				'champs'			=> array(),

				'mongoConf'			=> 'default',
				'mongoCollection'	=> '',
				'mongoOptions'		=> array('allowDiskUse' => true),

				'req'				=> '',
				'addGroup'			=> '',

				'hydrateReq'		=> '',

				'fieldsForm'		=> array(),

				'datedeb'			=> '',
				'datefin'			=> '',

				'datedebCompar'		=> '',
				'datefinCompar'		=> '',

				'chpDate'			=> '',
				'chpDateType'		=> '',

				'stepTimeline'		=> '',
				'stepActiv'			=> array(
											'YEAR'		=> 'Années',
											'MONTH'		=> 'Mois',
											'WEEK'		=> 'Semaines',
											// 'WEEK_S'	=> 'Semaines (1er jour samedi)',
											'DAY'		=> 'Jours',
											'int_JOUR'	=> 'Interval jours de la semaine',
											'HOUR'		=> 'Heures de la journée',
				),

				'dtpDeb'			=> '',
				'dtpFin'			=> '',
				'dtpDebCompar'		=> '',
				'dtpFinCompar'		=> '',
			)
		);
	}


	protected function initOptions($options)
	{
		parent::initOptions($options);
		$this->type = 'noSql';

		if (isset($_GET['compar'])) {
			$this->compar = true;
		}

		$this->data				= $options['data'];
		$this->champs			= $options['champs'];

		$this->mongoConf		= $options['mongoConf'];
		$this->mongoCollection	= $options['mongoCollection'];
		$this->mongoOptions		= $options['mongoOptions'];

		$this->req 				= $options['req'];
		$this->addGroup			= $options['addGroup'];

		$this->hydrateReq		= $options['hydrateReq'];

		$this->fieldsForm		= $options['fieldsForm'];

		$this->datedeb			= $options['datedeb'];
		$this->datefin			= $options['datefin'];

		$this->datedebCompar	= $options['datedebCompar'];
		$this->datefinCompar	= $options['datefinCompar'];

		$this->chpDate			= $options['chpDate'];
		$this->chpDateType		= $options['chpDateType'];

		$this->stepTimeline		= $options['stepTimeline'];
		$this->stepActiv		= $options['stepActiv'];

		// Récupération des variables GET
		$this->checkGET();

		// Le calcul des datas de la statistique ne se font que si toutes les conditions sont réunies
		if ($this->compar) {
			if (	!empty($this->dtpDeb)
				&&	!empty($this->dtpFin)
				&&	!empty($this->dtpFinCompar)
				&&	!empty($this->dtpFinCompar)
				&& 	!empty($this->stepTimeline)
			) {
				$this->setData();
			}
		} else {
			if (
					!empty($this->dtpDeb)
				&& 	!empty($this->dtpFin)
				&& 	!empty($this->stepTimeline)
			) {
				$this->setData();
			}
		}
	}

	/**
	 * Formatage de la requête le type d'interval retenu
	 */
	private function dateFormatType()
	{
		if (! empty($this->stepTimeline)) {

			// Configuration des intervals
			$intervals = array(
				'YEAR' 		=> array(
									"year" 			=> array('$year'		=> '$'  . $this->chpDate),
				),
				'MONTH'		=> array(
									"year" 			=> array('$year'		=> '$'  . $this->chpDate),
									"month" 		=> array('$month'		=> '$'  . $this->chpDate),
				),
				'WEEK'		=> array(
									"year" 			=> array('$year'		=> '$'  . $this->chpDate),
									"week" 			=> array('$week'  		=> '$'  . $this->chpDate),
				),
				'DAY'		=> array(
									"year" 			=> array('$year'	 	=> '$'  . $this->chpDate),
									"month" 		=> array('$month' 		=> '$'  . $this->chpDate),
									"dayOfMonth" 	=> array('$dayOfMonth' 	=> '$'  . $this->chpDate),
				),
				'int_JOUR'	=> array(
									"dayOfWeek" 	=> array('$dayOfWeek'	=> '$'  . $this->chpDate),
				),
				'HOUR'		=> array(
									"hour" 			=> array('$hour'	 	=> '$'  . $this->chpDate),
				),
			);

			$interval = $intervals[$this->stepTimeline];

			if (!empty($this->addGroup)) {
				$interval = array_merge(
					array($this->addGroup => '$' . $this->addGroup),
					$interval
				);
			}

			// Groupe Interval
			$group = array(
				"_id" => $interval
			);

			return $group;

		} else {
			// die("L'attribut 'stepType' n'est pas renseigné");
		}
	}


	/**
	 * Connexion à la base de donnée mongoDb
	 */
	private function connectMongoDb($mongoConf=null)
	{
		if (!is_array($this->mongoInstances)) {
			$this->mongoInstances = array();
		}

		if (is_null($mongoConf)) {
			$mongoConf = $this->mongoConf;
		}

		try {
			$this->mongoInstances[$mongoConf] = mongoDbSingleton::getInstance($mongoConf);
		} catch (\Exception $e) {
			echo $e->getMessage;
		}
	}


	/**
	 * Récupération et stockage des variable GET du moteur de recherche
	 */
	private function checkGET()
	{
		if (isset($_GET['dtp_deb'])) {

			$this->dtpDeb = $_GET['dtp_deb'];

			switch ($this->chpDateType)
			{
				case 'date' :
					$dateDeb = $_GET['dtp_deb'] . ' 00:00:00';
					$this->datedeb = MongoUtils::convertType($dateDeb, $this->chpDateType);
					break;

				case 'datetime' :
					$this->datedeb = MongoUtils::convertType($_GET['dtp_deb'], $this->chpDateType);
					break;
			}
		}

		if (isset($_GET['dtp_fin'])) {

			$this->dtpFin = $_GET['dtp_fin'];

			switch ($this->chpDateType)
			{
				case 'date' :
					// Ajoute d'un jour à la date de fin pour que la date demandée soit incluse
					$d = new \DateTime($_GET['dtp_fin']);
					$d->modify('+1 day');

					$dateFin = date('d-m-Y H:i:s', $d->getTimestamp());
					$this->datefin 	= MongoUtils::convertType($dateFin, $this->chpDateType);
					break;

				case 'datetime' :
					$this->datefin 	= MongoUtils::convertType($_GET['dtp_fin'], $this->chpDateType);
					break;
			}
		}

		if ($this->compar === true) {

			if (isset($_GET['dtp_deb_compar'])) {

				$this->dtpDebCompar = $_GET['dtp_deb_compar'];

				switch ($this->chpDateType)
				{
					case 'date' :
						$dateDebCompar = $_GET['dtp_deb_compar'] . ' 00:00:00';
						$this->datedebCompar = MongoUtils::convertType($dateDebCompar, $this->chpDateType);
						break;

					case 'datetime' :
						$this->datedebCompar = MongoUtils::convertType($_GET['dtp_deb_compar'], $this->chpDateType);
						break;
				}
			}

			if (isset($_GET['dtp_fin_compar'])) {

				$this->dtpFinCompar = $_GET['dtp_fin_compar'];

				switch ($this->chpDateType)
				{
					case 'date' :
						// Ajoute d'un jour à la date de fin pour que la date demandée soit incluse
						$d = new \DateTime($_GET['dtp_fin_compar']);
						$d->modify('+1 day');

						$dateFinCompar = date('d-m-Y H:i:s', $d->getTimestamp());
						$this->datefinCompar = MongoUtils::convertType($d->format($dateFinCompar), $this->chpDateType);
						break;

					case 'datetime' :
						$this->datefinCompar = MongoUtils::convertType($_GET['dtp_fin_compar'], $this->chpDateType);
						break;
				}
			}
		}

		if (isset($_GET['stepTimeline'])) {
			$this->stepTimeline = $_GET['stepTimeline'];
		}
	}


	/**
	 * Création du tableau de données
	 */
	private function setData()
	{
		// Connexion à la BDD
		$this->connectMongoDb();

		// Il est possible de chaîner plusieurs requêtes en les plaçant dans un tableau
		$reqlist = array();
		if (isset($this->req[0]['$project'])) {
			$reqlist[] = $this->req;
		} else {
			$reqlist = $this->req;
		}

		// On stock le résultats des requêtes normales et de comparaison
		$result 	  = array();
		$resultCompar = array();

		// Itérateur du résultat la requête
		$itRes=0;

		foreach ($reqlist as $key => $reqDescription) {

			// Il est possible de spécifier pour chaque requêtes le chpdate et chpDateType
			if (isset($reqDescription['req'])) {

				/**
				 * $reqlist = array(
				 * 				 array(
				 * 					'req' 			=> pipeline,
				 * 					'chpDate'		=> 'nom_champ_date',
				 * 					'chpDateType'	=> 'type_champ_date',
				 * 					'mongoConfName'	=> 'nom_conf_mongoDb',
				 * 					'collection'	=> 'nom_collection',
				 * 				),
				 * 				...
				 * );
				 *  ...
				 */

				$req = $reqDescription['req'];

				if (!empty($reqDescription['chpDate'])) {
					$this->chpDate = $reqDescription['chpDate'];
				}

				if (!empty($reqDescription['chpDateType'])) {
					$this->chpDateType = $reqDescription['chpDateType'];
				}

				if (!empty($reqDescription['mongoConfName'])) {

					$mongoConfName = $reqDescription['mongoConfName'];
					$this->connectMongoDb($mongoConfName);

					if (!empty($reqDescription['collection'])) {
						$instance = $this->mongoInstances[$mongoConfName];
						$connectCollection = $instance->{$reqDescription['collection']};
					}
				}


			} else {

				/**
				 * $reqlist = array(
				 * 				pipeline1,
				 * 				pipeline2,
				 * 				...
				 * )
				 */

				if (empty($this->mongoCollection)) {
					echo 'le nom de la collection absent !',
					die;
				}

				if (isset($this->mongoCollection)) {
					$instance = $this->mongoInstances[$this->mongoConf];
					$connectCollection = $instance->{$this->mongoCollection};
				}

				$req = $reqDescription;
			}

			// Remplacement du champ date
			$req[1]['$match'] = array_merge(
				$req[1]['$match'],
				array(
					$this->chpDate => array(
						'$gte' => $this->datedeb,
						'$lte' => $this->datefin
					)
				)
			);

			// Remplacement du filtre par plage ou interval
			$req[2]['$group'] = array_merge(
				$this->dateFormatType(),
				$req[2]['$group']
			);

			// Gestion de l'ordre des Résultats - Récupération des clés
			$sortKeys = array_keys($req[2]['$group']['_id']);

			foreach ($sortKeys as $sortKey) {
				$req[3]['$sort']['_id.' . $sortKey] = 1;
			}

			// Exécution de la requête | pipeline
			$res = $connectCollection->aggregate(
			    $req,
				$this->mongoOptions
			);

			// Récupération des résultats
			foreach ($res as $doc) {

				// Récupération du nom de l'interval dans la timeLine (nom de la colonne)
				$stepLib = $this->stepLibelle($doc);

				// Attention de toujours grouper sur une clé se nommant "resultat" dans le tableau de confiuration

				// Goupé seulement sur l'interval de temps
				if (empty($this->addGroup)) {
					$result[$itRes][$stepLib] = $doc->resultat;

				// Ajout d'un champ supplémentaire pour grouper
				} else {
					$result[$itRes][$doc->_id->{$this->addGroup}][$stepLib] = $doc->resultat;
				}
			}

			// Exécution de la requête de comparaison
			if ($this->compar === true && !empty($this->dtpDebCompar) && !empty($this->dtpFinCompar)) {

				$this->reqCompar = $req;

				// Remplacement du champ date
				$this->reqCompar[1]['$match'] = array_merge(
					$this->reqCompar[1]['$match'],
					array(
						$this->chpDate => array(
							'$gte' => $this->datedebCompar,
							'$lte' => $this->datefinCompar
						)
					)
				);

				// Exécution de la requête | pipeline
				$res = $connectCollection->aggregate(
				    $this->reqCompar,
					$this->mongoOptions
				);

				// Récupération des résultats
				foreach ($res as $doc) {

					// Récupération du nom de l'interval dans la timeLine (nom de la colonne)
					$stepLib = $this->stepLibelle($doc);

					// Attention de toujours grouper sur une clé se nommant "resultat" dans le tableau de confiuration

					// Goupé seulement sur l'interval de temps
					if (empty($this->addGroup)) {
						$resultCompar[$itRes][$stepLib] = $doc->resultat;

					// Ajout d'un champ supplémentaire pour grouper
					} else {
						$resultCompar[$itRes][$doc->_id->{$this->addGroup}][$stepLib] = $doc->resultat;
					}
				}
			}

			// Chargement de la configuration des colonnes
			if ($itRes == 0) {

				if (empty($this->addGroup)) {

					foreach ($result[$itRes] as $k=>$v) {
						$this->libelle[$k] = array_merge(
							array('label' => $k),
							$this->fieldsForm
						);
					}

				} else {

					foreach ($result[$itRes] as $addGroup => $val) {
						foreach ($val as $k=>$v) {
							$this->libelle[$k] = array_merge(
								array('label' => $k),
								$this->fieldsForm
							);
						}
					}
				}
			}

			$itRes++;
		}

		// Résultat & comparaison période N-1
		// $result 	  = $this->postResult($result);$
		// $resultCompar = $this->postResultCompar($resultCompar);

		$line  = 0;		// Chargement des datas | $line : Ligne dans le tableau
		$itRes = 0;		// Itérateur pour requêtes multiples
		$nChp  = 0;		// Itérateur configuration des champs / ligne

		foreach ($this->champs as $k=>$v) {

			// Pas de groupe supplémentaire
			if (empty($this->addGroup)) {

				foreach ($result[$itRes] as $k2=>$v2) {

					// Ajout de la configuration de la ligne
					$this->addLibelle($nChp, $line);


					if (!empty($v2)) {

						if (!in_array($k2, $this->intervals)) {
							$this->intervals[] = $k2;
						}

						$this->data[$line]['values'][$k2] = $v2;
					} else {
						$this->data[$line]['values'][$v2] = 0;
					}

					$line++;

					if ($this->compar !== true) {
						$nChp++;
					}

					// Lignes de comparaison
					if ($this->compar === true && (! empty($this->dtpDebCompar)) && (! empty($this->dtpFinCompar))) {

						$this->addLibelle($nChp, $line, true);

						$resultKeys 	  = array_keys($result[$itRes]);
						$resultComparKeys = array_keys($resultCompar[$itRes]);

						$nbResult = count($result[$itRes]);

						for ($l=0; $l<$nbResult; $l++) {

							if (isset($resultComparKeys[$l])) {

								if (!in_array($resultKeys[$l], $this->intervals)) {
									$this->intervals[] = $resultKeys[$l];
								}

								$this->data[$line]['values'][$resultKeys[$l]]		= $resultCompar[$itRes][$resultComparKeys[$l]];
								$this->data[$line]['valuesN'][$resultKeys[$l]] 		= $result[$itRes][$resultKeys[$l]];
							} else {
								$this->data[$line]['values'][$resultKeys[$l]]		= false;
								$this->data[$line]['valuesN'][$resultKeys[$l]] 		= 0;
							}
						}

						$line++;
						$nChp++;
					}
				}

			// Groupé sur un champ définit dans la configuration
			} else {

				if (isset($result[$itRes])) {

					foreach ($result[$itRes] as $addGroup => $val) {

						foreach ($val as $k2 => $v2) {

							// Ajout de la configuration de la ligne
							$this->addLibelle($nChp, $line);

							if (!empty($v2)) {

								if (!in_array($k2, $this->intervals)) {
									$this->intervals[] = $k2;
								}

								$this->data[$line]['values'][$k2] = $v2;
							} else {
								$this->data[$line]['values'][$v2] = 0;
							}
						}

						$line++;

						if ($this->compar !== true) {
							$nChp++;
						}

						// Lignes de comparaison
						if ($this->compar === true && (! empty($this->dtpDebCompar)) && (! empty($this->dtpFinCompar))) {

							$this->addLibelle($nChp, $line, true);

							$resultKeys 	  = array_keys($result[$itRes][$addGroup]);
							$resultComparKeys = array_keys($resultCompar[$itRes][$addGroup]);

							$nbResult = count($result[$itRes][$addGroup]);

							for ($l=0; $l<$nbResult; $l++) {

								if (!in_array($resultKeys[$l], $this->intervals)) {
									$this->intervals[] = $resultKeys[$l];
								}

								if (isset($resultComparKeys[$l])) {
									$this->data[$line]['values'][$resultKeys[$l]]		= $resultCompar[$itRes][$addGroup][$resultComparKeys[$l]];
									$this->data[$line]['valuesN'][$resultKeys[$l]] 		= $result[$itRes][$addGroup][$resultKeys[$l]];
								} else {
									$this->data[$line]['values'][$resultKeys[$l]]		= false;
									$this->data[$line]['valuesN'][$resultKeys[$l]] 		= 0;
								}
							}

							$line++;
							$nChp++;
						}
					}
				}
			}

			$itRes++;
		}

		// Remplissage des intervals vides
		$this->checkIntervals();

		// echo '<pre>';
		// 	// print_r($res);				// Résultats de la requête
		// 	// print_r($this->champs);		// Liste des champs
		// 	// print_r($this->libelle);		// Liste des colonnes
		// 	// print_r($resultCompar);
		// 	// print_r($result);
		// 	print_r($this->intervals);
		// 	print_r($this->data);			// Tableau de données formatés
		// echo '</pre>';
	}


	/**
	 * Ajout de la config de chaques ligne aux résultats
	 *
	 * @param 	integer		$nChp 				Itérateur configuration des champs / ligne
	 * @param 	integer		$line 				Numéro de la ligne du tableau
	 * @param 	boolean		$compar				Mode normal ou comparaison N-1
	 */
	private function addLibelle($nChp, $line, $compar=false)
	{
		$champsKeys = array_keys($this->champs);
		$champName	= $champsKeys[$nChp];

		$this->data[$line]['name']  = $champName;

		$label 	= $this->champs[$champName]['label'];
		$type 	= 'normal';

		if ($compar) {
			$label .= ' N-1';
			$type	= 'compar';
		}

		$this->data[$line]['label'] = $label;
		$this->data[$line]['type']	= $type;

		if (isset($this->champs[$champName]['graph'])  &&  $this->champs[$champName]['graph'] === true) {

			$this->data[$line]['graph'] 		= $this->champs[$champName]['graph'];

			$graphOnLoad = $this->champs[$champName]['graphOnLoad'];	// Affichage du graphique au lancement
			$color = $this->champs[$champName]['graphColor'];			// Couleur puce & graphique

			if ($compar) {
				$graphOnLoad = false;
				$color = $this->pantoneColor($color);
			}

			$this->data[$line]['graphOnLoad'] 	= $graphOnLoad;
			$this->data[$line]['graphColor'] 	= $color;

			if (!empty($this->champs[$champName]['graphYAxis'])) {
				$this->data[$line]['graphYAxis']	= $this->champs[$champName]['graphYAxis'];
			}

			if (!empty($this->champs[$champName]['graphType'])) {
				$this->data[$line]['graphType']	= $this->champs[$champName]['graphType'];
			}
		}

		if (isset($this->champs[$champName]['group'])  &&  $this->champs[$champName]['group'] === true) {
			$this->data[$line]['group'] = $this->champs[$champName]['group'];
		}

		if (isset($this->champs[$champName]['groupLabel'])  &&  $this->champs[$champName]['groupLabel'] != '') {
			$this->data[$line]['groupLabel'] = $this->champs[$champName]['groupLabel'];
		}

		if (isset($this->champs[$champName]['groupColor'])  &&  $this->champs[$champName]['groupColor'] != '') {
			$this->data[$line]['groupColor'] = $this->champs[$champName]['groupColor'];
		}

		if (! empty($this->champs[$champName]['align'])) {
			$this->data[$line]['align'] = $this->champs[$champName]['align'];
		}

		if (isset($this->champs[$champName]['unite'])) {
			$this->data[$line]['unite'] = $this->champs[$champName]['unite'];
		}

		if (isset($this->champs[$champName]['decimales'])) {
			$this->data[$line]['decimales'] = $this->champs[$champName]['decimales'];
		}
	}


	/**
	 * Récupération du nom de l'interval dans la timeLine
	 * Libelle de chaques colonnes
	 *
	 * @param  	object		$doc		Document MongoDb
	 * @return 	string
	 */
	private function stepLibelle($doc)
	{
		switch($this->stepTimeline) {
			case 'YEAR' :
				$stepLib = $doc->_id->year;
				break;
			case 'MONTH' :
				$month = str_pad($doc->_id->month, 2, '0', STR_PAD_LEFT);
				$stepLib = $doc->_id->year . '-' . $month;
				break;
			case 'WEEK' :
				$week  = 'W' . str_pad($doc->_id->week, 2, '0', STR_PAD_LEFT);
				$stepLib = $doc->_id->year . '-' . $week;
				break;
			case 'DAY' :
				$month = str_pad($doc->_id->month, 2, '0', STR_PAD_LEFT);
				$dayOfMonth = str_pad($doc->_id->dayOfMonth, 2, '0', STR_PAD_LEFT);
				$stepLib = $doc->_id->year . '-' . $month . '-' . $dayOfMonth;
				break;
			case 'HOUR' :
				$stepLib = $doc->_id->hour . ':00';
				break;
			case 'int_JOUR' :
				$stepLib = $this->days[$req[0]->_id->dayOfWeek];
				break;
			default :
				$stepLib = 'Unknown';
				break;
		}

		return $stepLib;
	}


	/**
	 * Permet de modifier une couleur hexadécimale pour récupérer une variante proche
	 */
	protected function pantoneColor($colorHexa) {

		$colorHexaR = substr($colorHexa,1,2);
		$colorHexaV = substr($colorHexa,3,2);
		$colorHexaB = substr($colorHexa,5,2);

		$colorsDec = array(
			'R'=>hexdec($colorHexaR),
			'V'=>hexdec($colorHexaV),
			'B'=>hexdec($colorHexaB),
		 );

		foreach ($colorsDec as $k => $v) {
			if ($v > 50) {
				$colorsDec[$k] -= 40;
			} elseif ($v > 40 && $v <= 50) {
				$colorsDec[$k] -= 30;
			} elseif ($v > 30 && $v <= 40) {
				$colorsDec[$k] -= 20;
			} elseif ($v > 20 && $v <= 30) {
				$colorsDec[$k] -= 10;
			} elseif ($v > 10 && $v <= 20) {
				$colorsDec[$k] -= 5;
			}
		}

		$colorHexR = str_pad(dechex($colorsDec['R']), 2, '0', STR_PAD_LEFT);
		$colorHexV = str_pad(dechex($colorsDec['V']), 2, '0', STR_PAD_LEFT);
		$colorHexB = str_pad(dechex($colorsDec['B']), 2, '0', STR_PAD_LEFT);

		return '#' . $colorHexR . $colorHexV . $colorHexB;
	}


	/**
	 * Moteur de recherche du gestionaire de statistiques
	 */
	protected function plageDateTimePicker()
	{
		// Création des éléments de formulaire pour la comparaison
		$this->plageDateTimePickerCompar();

		// Scripts JS
		$pageCourante = $_SERVER['PHP_SELF'];

		$js = <<<eof
			$(function () {
				// Gestion liée des deux dateTimePicker
			    $('#dtp_deb').datetimepicker({
					format: '{$this->formatDateTimePicker()}'
				});
			    $('#dtp_fin').datetimepicker({
					format: '{$this->formatDateTimePicker()}',
			        useCurrent: false //Important! See issue #1075
			    });
			    $("#dtp_deb").on("dp.change", function (e) {
			        $('#dtp_fin').data("DateTimePicker").minDate(e.date);
			    });
			    $("#dtp_fin").on("dp.change", function (e) {
			        $('#dtp_deb').data("DateTimePicker").maxDate(e.date);
			    });

				// Affichage amélioré du select
				$('#stepTimeline').selectpicker();
			});
eof;

		libIncluder::add_JsScript($js);


		// Choix des intervals de temps
		$options = array();

		foreach ($this->stepActiv as $k=>$v) {

			if ($k == $this->stepTimeline) {
				$selected = ' selected';
			} else {
				$selected = '';
			}

			$options[] = '<option value="' . $k . '"'.$selected.'>' . $v . '</option>';
		}

		$options = implode(chr(10), $options);

		$placeholder = ' de la période';

		// Récupération des GET de la page hors script
		$inputStats = array(
			'dtp_deb',
			'dtp_fin',
			'dtp_deb_compar',
			'dtp_fin_compar',
			'stepTimeline',
		);

		$addHiddenInput = array();
		foreach($_GET as $name => $value) {
			if (!in_array($name, $inputStats)) {
				$addHiddenInput[] = '<input type="hidden" name="'.$name.'" value="'.$value.'">';
			}
		}

		$addHiddenInput = implode('', $addHiddenInput);

		// Code des champs et des boutons de recherche
		$html = <<<eof
			<div class="container-fluid" style="padding:0;">

				<form method="get">

					$addHiddenInput

					<div class="col-lg-12" style="padding:0; margin:0; margin-bottom:7px; color:#777;">
						{$this->affLinkNormal()}
						<div style="display:inline-block; padding:0 5px;">|</div>
						{$this->affLinkCompar()}
						{$this->htmlEndLink}
					</div>

					<div class="col-lg-12" style="padding:0;">
					    <div class="col-lg-2" style="padding:0;">
					        <div class="form-group" style="margin-bottom:5px;">
					            <div class="input-group date" id="dtp_deb" role="datetimepicker">
					                <input type="text" name="dtp_deb" id="dtp_deb_id" class="form-control" style="height:34px;" value="{$this->dtpDeb}" placeholder="Début $placeholder" required>
					                <span class="input-group-addon">
					                    <span class="glyphicon glyphicon-calendar"></span>
					                </span>
					            </div>
					        </div>
					    </div>
					    <div class="col-lg-2" style="padding:0; margin-left:5px;">
					        <div class="form-group" style="margin-bottom:5px;">
					            <div class="input-group date" id="dtp_fin">
					                <input type="text" name="dtp_fin" id="dtp_fin_id" class="form-control" style="height:34px;" value="{$this->dtpFin}" placeholder="Fin $placeholder" required>
					                <span class="input-group-addon">
					                    <span class="glyphicon glyphicon-calendar"></span>
					                </span>
					            </div>
					        </div>
					    </div>
						<div class="col-lg-2" style="padding:0; margin-left:5px;">
							<select name="stepTimeline" id="stepTimeline" class="form-control" required>
								<option value="">-- Intervals --</option>
								$options
							</select>
						</div>
						<div class="col-lg-5" style="padding:0; margin-left:5px;">
							<button type="submit" class="btn btn-primary" data-tooltip="true">Envoyer</button>
						</div>
					</div>

					{$this->htmlCompar}
					{$this->htmlEndForm}
				</form>
			</div>
eof;

	return $html;
	}


	/**
	 * Moteur de recherche du gestionaire de statistiques
	 * Gestion de la comparaison
	 */
	protected function plageDateTimePickerCompar()
	{
		if ($this->compar === true) {

			$placeholder = ' période à comparer';

			// Code HTML de Comparaison
			$this->htmlCompar = <<<eof
				<input type="hidden" name="compar" value="1">
				<div id="datePickerCompar_id" class="col-lg-12" style="padding:0;">
					<div class="col-lg-2" style="padding:0;">
						<div class="form-group">
							<div class="input-group date" id="dtp_deb_compar">
								<input type="text" name="dtp_deb_compar" id="dtp_deb_id_compar" class="form-control" style="height:34px;" value="{$this->dtpDebCompar}" placeholder="Début $placeholder" required>
								<span class="input-group-addon">
									<span class="glyphicon glyphicon-calendar"></span>
								</span>
							</div>
						</div>
					</div>
					<div class="col-lg-2" style="padding:0; margin-left:5px;">
						<div class="form-group">
							<div class="input-group date" id="dtp_fin_compar">
								<input type="text" name="dtp_fin_compar" id="dtp_fin_id_compar" class="form-control" style="height:34px;" value="{$this->dtpFinCompar}" placeholder="Fin $placeholder" required>
								<span class="input-group-addon">
									<span class="glyphicon glyphicon-calendar"></span>
								</span>
							</div>
						</div>
					</div>
			</div>
eof;

			$js = <<<eof
				$(function () {
					// Gestion liée des deux dateTimePicker
					$('#dtp_deb_compar').datetimepicker({
						format: '{$this->formatDateTimePicker()}'
					});
					$('#dtp_fin_compar').datetimepicker({
						format: '{$this->formatDateTimePicker()}',
						useCurrent: false //Important! See issue #1075
					});
					$("#dtp_deb_compar").on("dp.change", function (e) {
						$('#dtp_fin_compar').data("DateTimePicker").minDate(e.date);
					});
					$("#dtp_fin_compar").on("dp.change", function (e) {
						$('#dtp_deb_compar').data("DateTimePicker").maxDate(e.date);
					});

					// Affichage amélioré du select
					// $('#stepTimeline').selectpicker();
				});
eof;

			libIncluder::add_JsScript($js);

		} else {
			$this->htmlCompar = '';
		}
	}


	/**
	 * Permet de compléter les lignes n'ayant pas de valeurs pour tous les intervals par un 0
	 */
	private function checkIntervals()
	{
		// Remise en ordre croissant des libellés
		ksort($this->libelle);

		// Mise en ordre de tous les intervals connus
		sort($this->intervals);

		$intervals = array();
		foreach($this->intervals as $interval) {
			$intervals[$interval] = 0;
		}

		foreach ($this->data as $line => $val) {

			$this->data[$line]['values'] = array_merge($intervals, $val['values']);

			if (isset($val['valuesN'])) {
				$this->data[$line]['valuesN'] = array_merge($intervals, $val['valuesN']);
			}
		}
	}
}
