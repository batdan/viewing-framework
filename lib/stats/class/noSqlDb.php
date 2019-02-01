<?php
namespace vw\stats;

use core\libIncluder;
use core\mongoDbSingleton;
use backoffice\bdd\MongoUtils;

/**
 * Création d'une statisque sur la base d'une requête MongoDb
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
	protected $mongoBase;		// Sélection de la base de donées
	protected $mongoCollection;	// Choix de la collection

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
				'mongoBase'			=> '',
				'mongoCollection'	=> '',

				'req'				=> '',
				'addGroup'			=> array(),

				'hydrateReq'		=> '',

				'fieldsForm'		=> '',

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
											'60'		=> 'Heures de la journée',
											'30'		=> '1/2 heures de la journée',
											'15'		=> '1/4 heures de la journée',
											'10'		=> '1/6 heures de la journée (10 min)',
											'5'			=> '1/12 heures de la journée (5 min)',
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
		$this->type			= 'sql';

		if (isset($_GET['compar'])) {
			$this->compar = true;
		}

		$this->data				= $options['data'];
		$this->champs			= $options['champs'];

		$this->mongoConf		= $options['mongoConf'];
		$this->mongoBase		= $options['mongoBase'];
		$this->mongoCollection	= $options['mongoCollection'];

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

		$this->checkGET();

		if (isset($_GET['dtp_deb']) && isset($_GET['dtp_fin']) && isset($_GET['stepTimeline'])) {
			$this->setData();
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

			if (is_array($this->addGroup) && count($this->addGroup) > 0) {
				$interval = array_merge($interval, $this->addGroup);
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
	private function connectMongoDb()
	{
		$mongoClient = mongoDbSingleton::getInstance($this->mongoConf);

		try {
			$this->connectCollection = $mongoClient->{$this->mongoBase}->{$this->mongoCollection};
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
					$this->datedeb 	= MongoUtils::convertType($_GET['dtp_deb'] . ' 00:00:00', $this->chpDateType);
					break;

				case 'datetime' :
					$this->datedeb 	= MongoUtils::convertType($_GET['dtp_deb'], $this->chpDateType);
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
					$this->datefin 	= MongoUtils::convertType($d->format('Y-m-d H:i:s'), $this->chpDateType);
					break;

				case 'datetime' :
					$this->datedeb 	= MongoUtils::convertType($_GET['dtp_fin'], $this->chpDateType);
					break;
			}
		}

		if ($this->compar === true) {

			if (isset($_GET['dtp_deb_compar'])) {

				switch ($this->chpDateType)
				{
					case 'date' :
						$this->datedebCompar = MongoUtils::convertType($_GET['dtp_deb_compar'] . ' 00:00:00', $this->chpDateType);
						break;

					case 'datetime' :
						$this->datedebCompar = MongoUtils::convertType($_GET['dtp_deb_compar'], $this->chpDateType);
						break;
				}
			}

			if (isset($_GET['dtp_fin_compar'])) {

				switch ($this->chpDateType)
				{
					case 'date' :
						// Ajoute d'un jour à la date de fin pour que la date demandée soit incluse
						$d = new \DateTime($_GET['dtp_fin_compar']);
						$d->modify('+1 day');
						$this->datefinCompar = MongoUtils::convertType($d->format('Y-m-d H:i:s'), $this->chpDateType);
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
		if (is_array($this->req)) {
			$reqlist = $this->req;
		} else {
			$reqlist = array($this->req);
		}


		// On stock le résultats des requêtes normales et de comparaison
		$result = array();
		$resultCompar = array();

		foreach ($reqlist as $reqDescription) {

			// Il est possible de spécifier pour chaque requêtes le chpdate et chpDateType
			if (isset($reqDescription['req'])) {

				/**
				 * $req = array(
				 * 				array(
				 * 					'req' 			=> pipeline,
				 * 					'chpDate'		=> 'nom_champ_date',
				 * 					'chpDateType'	=> 'type_champ_date',
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

				if (!empty($reqDescription['collection'])) {
					try {
						$this->connectCollection = $mongoClient->{$this->mongoBase}->{$reqDescription['collection']};
					} catch (\Exception $e) {
						echo $e->getMessage;
					}
				}

			} else {

				/**
				 * $req = array(
				 * 			pipeline1,
				 * 			pipeline2,
				 * 			...
				 * )
				 */

				$req = $reqDescription;
			}

			// Remplacement du filtre par plage ou interval
			$req[0]['$group'] = array_merge($this->dateFormatType(), $req[0]['$group']);

			// Remplacement du champ date
			$req[0]['$match'] = array_merge(
				$req[0]['$match'],
				array(
					$this->chpDate => array(
						'$gte' => $this->datedeb,
						'$lte' => $this->datefin
					)
				)
			);


			// Exécution de la requête
			if (is_array($reqDescription) && !empty($reqDescription[3])) {
				$sql = $reqDescription[3]->prepare($req);
			} else {
				$sql = $this->connectCollection->prepare($req);
			}

			// echo $req;

			$values = array(
				':plageDeb' => $plageDeb,
				':plageFin' => $plageFin,
			);

			$sql->execute(
				array_merge(
					$this->hydrateReq,
					$values
				)
			);

			while ($res = $sql->fetch()) {

				$res = get_object_vars($res);

				// Recherche du bon indice
				$i=0;

				// Si c'est pas le premier on recherche si myInterval existe déjà
				if (isset($result[$i]["myInterval"])) {

					while ($result[$i]["myInterval"] != $res["myInterval"] && $i<(count($result)-1)) {
						$i++;
					}

					if ($result[$i]["myInterval"]!=$res["myInterval"]) {
						$i++;
					}
				}

				if (isset($result[$i])) {
					$result[$i] = array_merge($result[$i], $res);
				} else {
					$result[$i] = $res;
				}
			}

			// Exécution de la requête de comparaison
			if ($this->compar === true && (!empty($this->dtpDebCompar)) && (!empty($this->dtpFinCompar))) {

				$this->reqCompar = $req;
				$this->reqCompar = str_replace (":plageDeb", ":plageDebCompar", $this->reqCompar);
				$this->reqCompar = str_replace (":plageFin", ":plageFinCompar", $this->reqCompar);

				// Exécution de la requête
				if (is_array($reqDescription) && !empty($reqDescription[3])) {
					$sqlCompar = $reqDescription[3]->prepare($this->reqCompar);
				} else {
					$sqlCompar = $this->connectCollection->prepare($this->reqCompar);
				}

				$valuesCompar = array(
					':plageDebCompar' => $plageDebCompar,
					':plageFinCompar' => $plageFinCompar,
				);

				$sqlCompar->execute(
					array_merge(
						$this->hydrateReq,
						$valuesCompar
					)
				);

				while ($resCompar = $sqlCompar->fetch()) {

					$resCompar = get_object_vars($resCompar);

					// Recherche du bon indice
					$i=0;

					// Si c'est pas le premier on recherche si myInterval existe déjà
					if (isset($resultCompar[$i]["myInterval"])) {

						while ($resultCompar[$i]["myInterval"]!=$resCompar["myInterval"] && $i<(count($resultCompar)-1)) {
							$i++;
						}

						// Pas trouvée
						if ($resultCompar[$i]["myInterval"]!=$resCompar["myInterval"]) {
							$i++;
						}
					}

					if (isset($resultCompar[$i])) {
						$resultCompar[$i] = array_merge($resultCompar[$i], $resCompar);
					} else {
						$resultCompar[$i] = $resCompar;
					}
				}
			}

			// Chargement des colonnes
			foreach ($result as $k=>$v) {
				$this->libelle[$v['myInterval']] = array_merge(
					array('label' => $v['myInterval']),
					$this->fieldsForm
				);
			}
		}

		// Résultat & comparaison période N-1
		$result 	  = $this->postResult($result);
		$resultCompar = $this->postResultCompar($resultCompar);

		// Chargement des datas
		$line = 0;

		foreach ($this->champs as $k=>$v) {

			$this->data[$line]['name']  = $k;
			$this->data[$line]['label'] = $v['label'];
			$this->data[$line]['type']	= 'normal';

			if (isset($v['graph'])  &&  $v['graph'] === true) {
				$this->data[$line]['graph'] 		= $v['graph'];
				$this->data[$line]['graphOnLoad'] 	= $v['graphOnLoad'];
				$this->data[$line]['graphColor'] 	= $v['graphColor'];

				if (!empty($v['graphYAxis'])) {
					$this->data[$line]['graphYAxis']	= $v['graphYAxis'];
				}

				if (!empty($v['graphType'])) {
					$this->data[$line]['graphType']	= $v['graphType'];
				}
			}

			if (isset($v['group'])  &&  $v['group'] === true) {
				$this->data[$line]['group'] = $v['group'];
			}

			if (isset($v['groupLabel'])  &&  $v['groupLabel'] != '') {
				$this->data[$line]['groupLabel'] = $v['groupLabel'];
			}

			if (isset($v['groupColor'])  &&  $v['groupColor'] != '') {
				$this->data[$line]['groupColor'] = $v['groupColor'];
			}

			if (! empty($v['align'])) {
				$this->data[$line]['align'] = $v['align'];
			}

			if (isset($v['unite'])) {
				$this->data[$line]['unite'] = $v['unite'];
			}

			if (isset($v['decimales'])) {
				$this->data[$line]['decimales'] = $v['decimales'];
			}

			$linenterval_req 	= array();
			$resN   			= array();

			foreach ($result as $k2=>$v2) {

				$linenterval_req[] = $v2;

				if (isset($v2[$k])) {
					$resN[] = $v2[$k];
					$this->data[$line]['values'][$v2['myInterval']] = $v2[$k];
				} else {
					$resN[] = 0;
					$this->data[$line]['values'][$v2['myInterval']] = 0;
				}
			}

			// Lignes de comparaison
			if ($this->compar === true && (! empty($this->dtpDebCompar)) && (! empty($this->dtpFinCompar))) {

				$line++;

				$this->data[$line]['name']  = $k;
				$this->data[$line]['label'] = $v['label'] . ' N-1';
				$this->data[$line]['type']	= 'compar';

				if (isset($v['graph'])  &&  $v['graph'] === true) {
					$this->data[$line]['graph'] 		= $v['graph'];
					$this->data[$line]['graphOnLoad'] 	= false;
					$this->data[$line]['graphColor'] 	= $this->pantoneColor($v['graphColor']);
				}

				if (isset($v['group'])  &&  $v['group'] === true) {
					$this->data[$line]['group'] = $v['group'];
				}

				if (isset($v['groupLabel'])  &&  $v['groupLabel'] != '') {
					$this->data[$line]['groupLabel'] = $v['groupLabel'];
				}

				if (isset($v['groupColor'])  &&  $v['groupColor'] != '') {
					$this->data[$line]['groupColor'] = $v['groupColor'];
				}

				if (! empty($v['align'])) {
					$this->data[$line]['align'] = $v['align'];
				}

				if (isset($v['unite'])) {
					$this->data[$line]['unite'] = $v['unite'];
				}

				if (isset($v['decimales'])) {
					$this->data[$line]['decimales'] = $v['decimales'];
				}

				$j=0;

				for ($l=0; $l<count($result); $l++) {

					if (isset($resultCompar[$l]) && isset($resultCompar[$l][$k])) {
						$this->data[$line]['values'][$linenterval_req[$j]['myInterval']] 	= $resultCompar[$l][$k];
						$this->data[$line]['valuesN'][$linenterval_req[$j]['myInterval']]	= $resN[$l];

					} else {

						$this->data[$line]['values'][$linenterval_req[$j]['myInterval']] 	= 0;
						$this->data[$line]['valuesN'][$linenterval_req[$j]['myInterval']] 	= false;
					}

					$j++;
				}
			}

			$line++;
		}

		// echo '<pre>';
		// 	//print_r($res);				// Résultats de la requête
		// 	// print_r($this->champs);		// Liste des champs
		// 	// print_r($this->libelle);		// Liste des colonnes
		// 	print_r($this->data);			// Tableau de données formatés
		// echo '</pre>';
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
	 * Lien pour passer en recherche de statistique sans comparaison
	 */
	private function affLinkNormal()
	{
		if ($this->compar === true) {
			$url  = explode('?', $_SERVER['REQUEST_URI']);
			$file = $url[0];

			if (count($url) > 1  &&  $url[1] != '') {

				$get  = explode('&', $url[1]);

				$newGet = array();
				$delete = array('compar', 'dtp_deb_compar', 'dtp_fin_compar');

				foreach ($get as $v) {
					$k = explode('=', $v);

					if (! in_array($k[0], $delete)) {
						$newGet[] = $v;
					}
				}

				$newGet = implode('&', $newGet);

				$html = '<a href="' . $file . '?' . $newGet . '">Normal</a>';
			} else {
				$html = '<a href="' . $_SERVER['REQUEST_URI'] . '">Normal</a>';
			}

		} else {
			$html = 'Normal';
		}

		return $html;
	}


	/**
	 * Lien pour passer en recherche de statistique avec comparaison
	 */
	private function affLinkCompar()
	{
		if ($this->compar === true) {
			$html = 'Comparaison';
		} else {
			$url  = explode('?', $_SERVER['REQUEST_URI']);
			$file = $url[0];

			if (count($url) > 1  &&  $url[1] != '') {

				$get = explode('&', $url[1]);

				if (! in_array('compar', $get)) {
					$get[] = 'compar=1';
				}

				$get = implode('&', $get);

				$html = '<a href="' . $file . '?' . $get . '">Comparaison</a>';
			} else {
				$html = '<a href="' . $_SERVER['REQUEST_URI'] . '">Comparaison</a>';
			}
		}

		return $html;
	}


	/**
	 * Format dateTimePicker
	 */
	protected function formatDateTimePicker()
	{
		switch ($this->chpDateType)
		{
			case 'date'		: $formatDateTimePicker = 'YYYY-MM-DD';			break;
			case 'time'		: $formatDateTimePicker = 'HH:mm';				break;
			default			: $formatDateTimePicker = 'YYYY-MM-DD HH:mm';
		}

		return $formatDateTimePicker;
	}
}
