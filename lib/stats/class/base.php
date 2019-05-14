<?php
namespace vw\stats;

use core\libIncluder;
use core\libIncluderList;

/**
 * Compilation des données pour la création :
 * 		- d'un tableau de statistiques
 * 		- d'un graphique
 *
 * @author Daniel Gomes
 */
class base
{
	/**
	 * Type de graphique : array, sql, noSql
	 * @var string
	 */
	protected $type;

	/**
	 * @var string
	 */
	protected $lined;

	/**
	 * @var string
	 */
	protected $action;

	/**
	 * @var array
	 */
	protected $libelle;

	/**
	 * Fonction javascript de callback permettant de changer les styles des lignes
	 *
	 * @var string
	 */
	protected $rowStyle;

	/**
	 * Largeur du tableau
	 * @var string
	 */
	protected $width;

	protected $title;
	protected $description;
	protected $results_line;
	protected $colorMinMax;

	/**
	 * Affichage de la ligne des totaux
	 * @var unknown
	 */
	protected $ligneTotaux;
	protected $ligneTotauxConf;
	protected $columnsValue = array();

	/**
	 * Graphique
	 */
	protected $graph;
	protected $graphConf;

	/**
	 * Groupes / Sous-groupes
	 */
	protected $groupLabel;
	protected $group;
	protected $groupColor;

	/**
	 * Scripts JS appelé après le chargement de la page
	 */
	protected $jsIncludeCallback;
	protected $js = '';

	/**
	 * Jour de la semaine - MySql DATE_FORMAT '%w'
	 */
	protected $jourSemaine = array('Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi');


	/**
	 * Constructeur
	 *
	 * @param array $options
	 */
	public function __construct(array $options = array())
	{
		// Configuration du graphique
		$options = array_merge($this->getDefaultOptions(), $options);
        $this->initOptions($options);

		// Valeur par défaut des champs de la time line : fields
		$this->getDefaultOptionsFields();

        $this->action = $this->getParam($this->getUrlKey(), 'display');

		// Librairies JS & CSS à charger
		libIncluderList::add_bootstrapSelect();
		libIncluderList::add_bootstrapDatetimepicker();
		libIncluderList::add_stats();

		// Scripts JS & CSS à charger
		libIncluder::add_JsScript("$('[data-tooltip]').tooltip();");

		// Chargement des scripts liées au grahique
		if ($this->graph && count($this->data) > 0) {
			libIncluder::add_JsScript($this->dataChartJs());
		}
	}


	/**
	 * Retourne la valeur d'un POST ou d'un GET
	 * @param  string $parameterName
	 * @param  string $defaultValue
	 * @return string
	 */
	private function getParam($parameterName, $defaultValue)
	{
		if (isset($_GET[$parameterName])) {
			return $_GET[$parameterName];
		} elseif (isset($_POST[$parameterName])) {
			return $_POST[$parameterName];
		} else {
			return $defaultValue;
		}
	}


	public function getId()
	{
		return $this->id;
	}

	public function setBreadcrumb($breadcrumb)
	{
		$this->breadcrumb->setBreadcrumb($breadcrumb);
	}

	public function addBreadcrumb(array $linetem)
	{
		$this->breadcrumb->add($linetem);
		return $this;
	}

	public function getBreadcrumb()
	{
		return $this->breadcrumb;
	}

	public function getUrlKey($name = 'action')
	{
		return '_' . $name . $this->id;
	}

	public function getTitle()
	{
		return $this->title;
	}

	public function getAction()
	{
		return $this->action;
	}

	protected function addJs()
	{
		libIncluder::add_JsScript($this->js);
	}


	/**
	 * Retourne les options par défaut.
	 *
	 * @return array
	 */
	protected function getDefaultOptions()
	{
		return array(
			'id' 				=> md5($_SERVER['PHP_SELF']),
			'url' 				=> $_SERVER['REQUEST_URI'],
			'libelle' 			=> array(),
			'rowStyle' 			=> null,
			'width' 			=> '1000px',

			'title' 			=> '',
			'description'		=> '',
			'results_line' 		=> array(),

			'colorMinMax' 		=> false,
			'colorMin'			=> '#db0f00',	// rouge
			'colorMax'			=> '#07a007',	// vert

			'ligneTotaux'		=> false,
			'ligneTotauxConf'	=> array(
										'libelle'	=> 'TOTAL',
										'unite' 	=> null,
										'decimales'	=> 0,
										'calcul'	=> 'somme',
								   ),
			'graph'				=> false,
			'graphConf'			=> array(
										'type'		=> 'line',
										'title'		=> null,
										'subtitle'	=> null,
										'unite'		=> null,
										'uniteLabel'=> null,
								),

			'groupLabel'		=> '',
			'group'			=> false,
			'groupColor'	=> '',
		);
	}


	/**
	 * Valeurs par défaut des champs de la timeLine (fields)
	 */
	public function getDefaultOptionsFields()
	{
		foreach ($this->libelle as $k => $v) {
			if (empty($v['label'])) {
				$this->libelle[$k]['label'] = $k;
			}
			if (empty($v['align'])) {
				$this->libelle[$k]['align'] = 'right';
			}
			if (empty($v['unite'])) {
				$this->libelle[$k]['unite'] = null;
			}
			if (empty($v['decimales'])) {
				$this->libelle[$k]['decimales'] = 0;
			}
		}
	}


	/**
	 * Récupération des options
	 *
	 * @param unknown $options
	 */
	protected function initOptions($options)
	{
		$this->id 				= $options['id'];
		$this->libelle 			= $options['libelle'];
		$this->rowStyle 		= $options['rowStyle'];
		$this->width 			= $options['width'];

		$this->title 			= $options['title'];
		$this->description		= $options['description'];
		$this->results_line 	= $options['results_line'];

		$this->colorMinMax		= $options['colorMinMax'];
		$this->colorMin			= $options['colorMin'];
		$this->colorMax			= $options['colorMax'];

		$this->ligneTotaux		= $options['ligneTotaux'];
		$this->ligneTotauxConf	= $options['ligneTotauxConf'];

		$this->graph			= $options['graph'];
		$this->graphConf		= $options['graphConf'];

		$this->groupLabel		= $options['groupLabel'];
		$this->group			= $options['group'];
		$this->groupColor		= $options['groupColor'];
	}


	public function setJsIncludeCallback($callback)
	{
		if (! is_callable($callback)) {
			throw new \Exception('Provided callback is not callable');
		}
		$this->jsIncludeCallback = $callback;
		return $this;
	}


	protected function display()
	{
		$html = '';

		// Moteur de recherche
		if (method_exists($this, 'plageDateTimePicker')) {
			$html .= $this->plageDateTimePicker();
		} else {
			// $html .= 'La méthode n\'existe pas';
		}

		if ($this->width) {
			$html .= '<div id="div_' . $this->id . '" class="stats" style="width:' . $this->width .'">' . chr(10);
		}

		// On vérifie que toutes les conditions soient réunies avant d'afficher la statistique
		$affStat = false;

		// Stats de la class "vw\stats\data" sans activation de la recherche par dates
		if ($this->type == 'array' && $this->activSearch === false) {
			$affStat = true;
		} else {
			if ($this->compar) {
				if (!empty($this->dtpDeb) && !empty($this->dtpFin) && !empty($this->dtpFinCompar) && !empty($this->dtpFinCompar)) {
					if ($this->type == 'array') {
						$affStat = true;
					} else {
						if (!empty($this->stepTimeline)) {
							$affStat = true;
						}
					}
				}
			} else {
				if (!empty($this->dtpDeb) && !empty($this->dtpFin)) {
					if ($this->type == 'array') {
						$affStat = true;
					} else {
						if (!empty($this->stepTimeline)) {
							$affStat = true;
						}
					}
				}
			}
		}

		// Toutes le conditions sont réunies, la stat est affichée
		if ($affStat === true) {

			// Affichage du titre
			if (!empty($this->title)) {
				$html .= '<h1 style="margin-top:10px;">' . ucfirst($this->title) . '</h1>' . chr(10);
			}

			// Affichage de la description
			if (!empty($this->description)) {
				$html .= '<div class="desc_H1">' . ucfirst($this->description) . '</div>' . chr(10);
			}

			// BEFORE TABLE
			$html .= $this->beforeRenderTable();
			//$html .= $this->renderCustomToolBar();

			$html .= '<table ' . $this->buildAttrs($this->getTableAttributes()) . '>' . chr(10);

				// THEAD
				$html .= $this->renderThead();

				// TBODY
				$html .= '<tbody>' . chr(10);
				$html .= $this->renderTbody();
				// On stock les colonnes si la ligne des totaux doit être affichée
				if ($this->ligneTotaux) {
					$html .= $this->renderTbodyTotaux();
				}
				$html .= '</tbody>' . chr(10);

				// TFOOT
				$html .= $this->renderTfoot();

			$html .= '</table>' . chr(10);

			// Hide Json Data - Graphique
			if ($this->graph) {
				$html .= $this->renderGraph();
			}
		}

		// AFTER TABLE
		$html .= $this->afterRenderTable();

		if ($this->width) {
			$html .= '</div>' . chr(10);
		}

		return $html;
	}

	/**
	 * Possibilité d'ajouter un script JS en CallBack
	 */
	protected function renderJs()
	{
		if (is_callable($this->jsIncludeCallback)) {
			call_user_func($this->jsIncludeCallback, $this->js);
		}
	}

	/**
	 * Retourne un script JS à appeler avant l'affichage de la table (en construction)
	 * @return string
	 */
	protected function beforeRenderTable()
	{

	}

	/**
	 * Retourne un script JS à appeler après l'affichage de la table
	 * @return string
	 */
	protected function afterRenderTable()
	{
		return $this->renderJs();
	}


	/**
	 * Création du code JS pour l'affichage du graphique
	 * @return string
	 */
	protected function dataChartJs()
	{
		foreach($this->data as $k => $v) {
			if (! isset($v['values'])) {
				return;
			}

			break;
		}

		$js = '';

		$graphTitle 	= '';
		$graphSubTitle 	= '';
		$graphText		= array();
		$graphTextSecond= array();
		$graphUnite		= '';

		// Type de grpahique (type : line, spline, area, areaspline, column, bar, pie, scatter | polar: true)
		if ($this->graphConf['type'] == 'polar') {
			$type = "polar: true";
		} elseif ($this->graphConf['type'] == 'xy') {
			$type = " zoomType: 'xy'";
		} else {
			$type = "type: '".$this->graphConf['type']."'";
		}

		// Titre
		if (! empty($this->graphConf['title'])) {
			$graphTitle = $this->graphConf['title'];
		}

		// Sous-titre
		if (! empty($this->graphConf['subtitle'])) {
			$graphSubTitle = $this->graphConf['subtitle'];
		}

		// Légende
		if (! empty($this->graphConf['uniteLabel'])) {
			$graphText[] = $this->graphConf['uniteLabel'];
		}
		if (! empty($this->graphConf['uniteLabelSecond'])) {
			$graphTextSecond[] = $this->graphConf['uniteLabelSecond'];
		}
		if (! empty($this->graphConf['unite'])) {
			$graphText[] = $this->graphConf['unite'];
		}
		if (! empty($this->graphConf['uniteSecond'])) {
			$graphTextSecond[] = $this->graphConf['uniteSecond'];
		}

		$graphText = implode(' ', $graphText);
		$graphTextSecond = implode(' ', $graphTextSecond);

		// Unite
		if (! empty($this->graphConf['unite'])) {
			$graphUnite = ' ' . $this->graphConf['unite'];
		}

		if (! empty($this->graphConf['uniteSecond'])) {
			$uniteSecond = ' ' . $this->graphConf['uniteSecond'];
		}

		// Hauteur du tableau
		if (! empty($this->graphConf['height'])) {
			$height = $this->graphConf['height'];
		} else {
			$height = 500;
		}

		$js = <<<eof
			$(function () {
			    $('#graph_{$this->id}').highcharts({
			        credits: {
						enabled: false
					},
			        chart: {
			            $type,
			            height:$height
			        },
			        title: {
			            text: '$graphTitle',
			            x: -20 //center
			        },
			        subtitle: {
			            text: '$graphSubTitle',
			            x: -20
			        },
			        rangeSelector: {
						selected: 2
					},
			        xAxis: {
			            categories: {$this->dataChartFieds()}
			        },
			        yAxis: {
			            title: {
			                text: '$graphText'
			            },
			            plotLines: [{
			                value: 0,
			                width: 1,
			                color: '#808080'
			            }]
			        },
			        tooltip: {
			            valueSuffix: '$graphUnite'
			        },
			        legend: {
			            layout: 'horizontal',
			            align: 'center',
			            verticalAlign: 'bottom',
			            borderWidth: '1',
			            borderColor: '#ddd'
			        },
			        series: {$this->dataChartSeries()}
			    });
			});
eof;

		if ($this->graphConf['type'] == 'xy') {
			$js = <<<eof
				$(function () {
				    $('#graph_{$this->id}').highcharts({
				        credits: {
							enabled: false
						},
				        chart: {
				            $type,
				            height:$height
				        },
				        title: {
				            text: '$graphTitle',
				            x: -20 //center
				        },
				        subtitle: {
				            text: '$graphSubTitle',
				            x: -20
				        },
				        rangeSelector: {
							selected: 2
						},
				        xAxis: [{
				            categories: {$this->dataChartFieds()}
				        }],
				        yAxis: [{
				            title: {
				                text: '$graphText'
				            },
				            plotLines: [{
				                value: 0,
				                width: 1,
				                color: '#808080'
				            }]
				        },
				        {
				            title: {
				                text: '$graphTextSecond'

				            },
				            plotLines: [{
				                value: 0,
				                width: 1,
				                color: '#808080'
				            }],
				            opposite: true
				        }],
				        tooltip: {
				            valueSuffix: '$graphUnite'
				        },
				        legend: {
				            layout: 'horizontal',
				            align: 'center',
				            verticalAlign: 'bottom',
				            borderWidth: '1',
				            borderColor: '#ddd'
				        },
				        series: {$this->dataChartSeries()}
				    });
				});
eof;
			}

		return $js;
	}


	/**
	 * Création d'un json contenant la liste des champs
	 *
	 * @return json
	 */
	protected function dataChartFieds()
	{
		$dataChartFields = array();

		foreach ($this->libelle as $k => $v) {
			$dataChartFields[] = $k;
		}

		return json_encode($dataChartFields);
	}


	/**
	 * Création d'un json avec la liste des valeurs pour le graph
	 *
	 * @return json
	 */
	protected function dataChartSeries()
	{
		$dataChartSeries = array();

		$line=0;

		// Affichage dans le graphique des totaux
		if ($this->ligneTotaux) {

			$dataChartSeries[$line]['name'] 	= $this->ligneTotauxConf['libelle'];
			$dataChartSeries[$line]['visible']	= false;
			$dataChartSeries[$line]['color']	= '#777';
			$dataChartSeries[$line]['data'] 	= $this->calcTotaux('normal', 0);

			$line++;

			// Affichage dans le graphique des totaux N-1
			if ($this->compar === true) {

				$dataChartSeries[$line]['name'] 	= $this->ligneTotauxConf['libelle'] . ' N-1';
				$dataChartSeries[$line]['visible']	= false;
				$dataChartSeries[$line]['color']	= '#333';
				$dataChartSeries[$line]['data'] 	= $this->calcTotaux('compar', 0);

				$line++;
			}
		}

		foreach ($this->data as $k=>$v) {

			if (isset($v['values']) && isset($v['graph'])  &&  $v['graph'] === true) {

				$dataChartSeries[$line]['name'] 	= $v['label'];
				$dataChartSeries[$line]['visible']	= $v['graphOnLoad'];
				$dataChartSeries[$line]['color']	= $v['graphColor'];

				if (!empty($v['graphYAxis'])) {
					$dataChartSeries[$line]['yAxis']	= $v['graphYAxis'];
				}

				if (!empty($v['graphType'])){
					$dataChartSeries[$line]['type']		= $v['graphType'];
				}

				// Passage des chiffres en entier pour le graphique
				$formData = array();

				foreach ($v['values'] as $k2=>$v2) {
					$formData[] = round($v2, 2);
				}

				$dataChartSeries[$line]['data'] = array_values($formData);

				$line++;
			}
		}

		return json_encode($dataChartSeries);
	}


	/**
	 * Calcul des totaux
	 *
	 * @param 	string 		$type 		Type : normal | compar
	 * @param 	integer		$decimal 	Nombre de décimales
	 *
	 * @return	array
	 */
	private function calcTotaux($type, $decimal)
	{
		$libelleKeys = array_keys($this->libelle);

		$calcul	= $this->ligneTotauxConf['calcul'];
		$totaux = array();

		foreach ($libelleKeys as $libelleKey)
		{
			$columnValue = array();

			foreach ($this->data as $val) {

				if (isset($val['type'])) {
					$confType = $val['type'];
				} else {
					$confType = 'normal';
				}

				if ($confType == $type && isset($val['values'])) {
					$columnValue[] = $val['values'][$libelleKey];
				}
			}

			if (is_callable(array($this, $calcul))) {
				$totaux[] = round($this->{$calcul}($columnValue), $decimal);
			}
		}

		return $totaux;
	}


	/**
	 * Ligne des entêtes / timeline
	 *
	 * @return string
	 */
	protected function renderThead()
	{
		$dataKeys = array_keys($this->data);
		$firstKey = $dataKeys[0];

		if (! isset($this->data[$firstKey]['values'])) {
			$html = '<thead><tr>' . chr(10);
			$html.= '<th>Aucun résultat</th>' . chr(10);
			$html.= '</thead></tr>' . chr(10);

			return $html;
		}

		$borderBottom = 'border-bottom:1px solid #555;';

		$html = '<thead><tr>' . chr(10);
		$html.= '<th align="left" style="'.$borderBottom.'">Libellés</th>' . chr(10);

		// Affichage des champs
		foreach ($this->libelle as $k => $v) {
			$attrs = array();
			$label = null;

			if (is_array($v)) {

				if (isset($this->stepTimeline)  &&  $this->stepTimeline == 'int_JOUR'  && is_numeric($v['label'])) {
					$v['label'] = $this->jourSemaine[$v['label']];
				}

				$style = array();

				if (! empty($v['label'])) {
					$label = $v['label'];
				}

				if (! empty($v['align'])) {
					$style[] = 'text-align:'.$v['align'].';';
				} else {
					$style[] = 'text-align:right;';
				}

				if (! empty($v['width'])) {
					$style[] = 'width:'.$v['width'].';';
				}

				$style[] = $borderBottom;

				$attrs['style'] = implode(" ", $style);

			} else {
				$label = $v;
			}

			$html .= '<th ' . $this->buildAttrs($attrs) . '>' . htmlentities($label, ENT_QUOTES, 'UTF-8') . '</th>' . chr(10);
		}


		// Champs de calculs en fin de lignes
		if (count($this->results_line) > 0) {

			foreach ($this->results_line as $line) {

				$label = null;
				$attrs = array();
				$style = array();

				if (! empty($line['label'])) {
					$label = $line['label'];
				}

				if (! empty($line['align'])) {
					$style[] = 'text-align:'.$line['align'].';';
				} else {
					$style[] = 'text-align:right;';
				}

				if (! empty($line['width'])) {
					$style[] = 'width:'.$line['width'].';';
				}

				if (! empty($line['bg_label'])) {
					$style[] = 'background:'.$line['bg_label'].';';
				}

				$style[] = $borderBottom;

				$attrs['style'] = implode(" ", $style);

				$html .= '<th ' . $this->buildAttrs($attrs) . '>' . htmlentities($label, ENT_QUOTES, 'UTF-8') . '</th>' . chr(10);
			}
		}

		$html .= '</tr></thead>' . chr(10);
		return $html;
	}


	/**
	 * Affichage des données du tableau
	 *
	 * @return string
	 */
	protected function renderTbody()
	{
		$html = '';

		$borderBottom = 'border-bottom:1px solid #555;';

		$line = 0;

		foreach ($this->data as $k_line=>$v_line) {

			if (! isset($v_line['values'])) {
				continue;
			}

			// Arrière plan - une ligne sur deux grisée
			if ($line % 2) {
				$bgColor = "background:#efefef;";
			} else {
				$bgColor = "background:#fff;";
			}

			$label = str_replace(" ", "&nbsp;", $v_line['label']);

			if ($this->graph  &&  isset($v_line['graph'])) {
				$label = '<i class="fa fa-square" style="display:inline; color:'.$v_line['graphColor'].'"></i>&nbsp;&nbsp;'.$label;
			}

			$styleIntitule = '';
			if (isset($v_line['type'])  &&  $v_line['type'] == 'compar') {
				$styleIntitule = $borderBottom;
			}

			// Affichage spécifique des groupes
			$borderLeft	= '';
			$btnAffSG 	= '';
			if (isset($v_line['group'])  &&  $v_line['group'] === true) {

				$hex = str_replace("#", "", $v_line['groupColor']);

				if(strlen($hex) == 3) {
					$r = hexdec(substr($hex,0,1).substr($hex,0,1));
					$g = hexdec(substr($hex,1,1).substr($hex,1,1));
					$b = hexdec(substr($hex,2,1).substr($hex,2,1));
				} else {
					$r = hexdec(substr($hex,0,2));
					$g = hexdec(substr($hex,2,2));
					$b = hexdec(substr($hex,4,2));
				}

				$bgColor  	= "background: rgba(".$r.", ".$g.", ".$b.", 0.1); "; //$bgColor  	= "background:#fdffcf;";

				$borderLeft	= "border-left:6px solid " . $v_line['groupColor'] . ";";

				if (isset($v_line['name'])) {
					$btnAffSG  = '<i class="fa fa-eye" style="display:inline; color:'.$v_line['groupColor'].'; cursor:pointer;" ';
					$btnAffSG .= 'onclick="$(\'.' . $v_line['name'] . '\').fadeToggle(\'300\');"></i>&nbsp;&nbsp;';
				}
			}

			// S'il s'agit d'un sous-groupe, on le masque et on ajoute le border-left
			if (isset($v_line['groupLabel'])  &&  $v_line['groupLabel'] != '') {
				$trStyle = ' style="display:none; border-left:6px solid ' . $v_line['groupColor'] . ';"';
				$trClass = ' class="' . $v_line['groupLabel'] . '"';
			} else {
				$trStyle = '';
				$trClass = '';
			}

			$html .= '<tr' . $trClass . $trStyle . '>' . chr(10);
			$html .= '<td class="intitule" style="'.$styleIntitule.' '.$bgColor.' '.$borderLeft.'">' . $btnAffSG . $label . '</td>' . chr(10);

			// Récupération de la valeur min et max de la ligne
			if ($this->colorMinMax) {
				$valMin = $this->min($v_line['values']);
				$valMax = $this->max($v_line['values']);
			}

			//////////////////////////////////////////////////////////////////////////////////////////
			// Boucle sur la liste des champs à afficher
			$col=0;
			foreach ($v_line['values'] as $k_chp=>$v_chp) {

				// On stock les colonnes si la ligne des totaux doit être affichée
				if ($this->ligneTotaux) {
					if (! isset($v_line['type'])  ||  $v_line['type'] == 'normal') {
						$this->columnsValue['normal'][$k_chp][] = $v_chp;
					}
					if (isset($v_line['type'])  &&  $v_line['type'] == 'compar') {
						$this->columnsValue['compar'][$k_chp][] = $v_chp;
					}
				}

				$attrs	= array();
				$style 	= array();

				if ($this->colorMinMax && $v_chp!=0) {
					if ($v_chp == $valMin) {
						$style[] = 'font-weight:bold; color:'.$this->colorMin.';';
					}
					if ($v_chp == $valMax) {
						$style[] = 'font-weight:bold; color:'.$this->colorMax.';';
					}
				}

				//////////////////////////////////////////////////////////////////////////////////////////
				// Affichage de la ligne
				if (is_array($this->libelle[$k_chp])) {

					$value  = $v_chp;
					$unite	= '';

					$divTitleDeb = '';
					$divTitleFin = '';

					// Largeur de colonne
					if (! empty($this->libelle[$k_chp]['width'])) {
						$style[] = 'width:'.$this->libelle[$k_chp]['width'].';';
					}

					// Alignement d'une valeur
					if (! empty($v_line['align'])) {
						$style[] = 'text-align:'.$v_line['align'].';';
					} else {
						if (! empty($this->libelle[$k_chp]['align'])) {
							$style[] = 'text-align:'.$this->libelle[$k_chp]['align'].';';
						} else {
							$style[] = 'text-align:right;';
						}
					}

					// Unité d'une valeur
					if (isset($v_line['unite'])) {
						if ($v_line['unite'] != '') {
							$unite = '&nbsp;'.$v_line['unite'];
						}
					} else {
						if (! empty($this->libelle[$k_chp]['unite'])) {
							$unite = '&nbsp;'.$this->libelle[$k_chp]['unite'];
						}
					}

					// Nombre de décimales d'une valeur
					if (isset($v_line['decimales'])) {
						$value = number_format($value, $v_line['decimales'], '.', '&nbsp;');
						$decimales = $v_line['decimales'];
					} else {
						if (! empty($this->libelle[$k_chp]['decimales'])) {
							$value = number_format($value, $this->libelle[$k_chp]['decimales'], '.', '&nbsp;');
							$decimales = $this->libelle[$k_chp]['decimales'];
						}
					}

					//////////////////////////////////////////////////////////////////////////////////////////
					// Affichage de la ligne de comparaison si présente
					if (isset($v_line['type'])  &&  $v_line['type'] == 'compar') {
						$style[] = $borderBottom;

						// Attribut title pour afficher le diff en nombre et pourcentage des deux périodes
						if (isset($v_line['valuesN'][$k_chp]) && $v_line['valuesN'][$k_chp] !== false) {

							$valueN = $v_line['valuesN'][$k_chp];

							if ($valueN != $v_chp) {

								$title = array();

								$val = ($valueN - $v_chp);

								// Différence des 2 plage en nombre
								if (isset($decimales)) {
									$val  = number_format($val, $decimales, '.', ' ');
								}
								if (! empty($unite)) {
									$val .= str_replace("&nbsp;", " ", $unite);
								}

								if ($valueN > $v_chp) {
									$title[] = '+' . $val;
								} else {
									$title[] = $val;
								}

								// Différence des deux plage en pourcentage
								if ($v_chp > 0) {
									$pourcentage = (($valueN / $v_chp) - 1) * 100;
									$affPourcent = number_format($pourcentage, 2, '.', ' ');
									if ($pourcentage > 0) {
										$affPourcent = '+' . $affPourcent;
									}
									$title[] = $affPourcent . '%';

									$attrsDiv['data-tooltip'] = "true";
									$attrsDiv['title'] = 'Evolution : ' . implode(' | ', $title);

									$style[] = 'cursor:default;';

									$divTitleDeb = '<div '.$this->buildAttrs($attrsDiv).'>';
									$divTitleFin = '</div>';
								}
							}
						}
					}

					$style[] = $bgColor;

					$attrs['style'] = implode(" ", $style);

					$html .= '<td '.$this->buildAttrs($attrs).'>' . $divTitleDeb . $value . $unite . $divTitleFin . '</div></td>' . chr(10);

				} else {

					$html .= '<td '.$this->buildAttrs($attrs).'>' . $v_chp . '</td>' . chr(10);
				}

				$col++;
			}

			// Boucle d'affichage de résultats des calculs
			if (count($this->results_line) > 0) {

				$col=0;

				foreach ($this->results_line as $k_calc=>$v_calc) {

					// On stock les colonnes si la ligne des totaux doit être affichée
					if ($this->ligneTotaux) {
						if (! isset($v_line['type'])  ||  $v_line['type'] == 'normal') {
							$this->columnsValue['normal'][$k_calc][] = $this->{$k_calc}($v_line['values']);
						}
						if (isset($v_line['type'])  &&  $v_line['type'] == 'compar') {
							$this->columnsValue['compar'][$k_calc][] = $this->{$k_calc}($v_line['values']);
						}
					}

					$attrs	= array();

					if (is_callable(array($this, $k_calc))) {

						$style 	= array();

						$value 	= $this->{$k_calc}($v_line['values']);
						$valBrut= $value;

						$unite	= '';

						$divTitleDeb = '';
						$divTitleFin = '';

						// On stock les résultats de la période au cas ou il serait nécessaire de les comparer
						if (isset($v_line['type'])  &&  $v_line['type'] == 'normal') {

							if (! isset($valueN_Calc)) {
								$valueN_Calc = array();
							}

							$valueN_Calc[$k_calc][$col] = $valBrut;
						}

						// Largeur de colonne
						if (! empty($v_calc['width'])) {
							$style[] = 'width:'.$v_calc['width'].';';
						}

						// Couleur de fond
						if (! empty($v_calc['bg_value'])) {

							// Couleur inversée dans le cas d'un sous-groupe
							if (isset($v_line['group'])  &&  $v_line['group'] === true) {
								$style[] = 'background:#333; color:'.$v_calc['bg_value'].';';
							} else {
								$style[] = 'background:'.$v_calc['bg_value'].';';
							}

						} else {
							$style[] = 'background:#fff;';
						}

						// Alignement d'une valeur
						if (! empty($v_line['align'])) {
							$style[] = 'text-align:'.$v_line['align'].';';
						} else {
							if (! empty($v_calc['align'])) {
								$style[] = 'text-align:'.$v_calc['align'].';';
							} else {
								$style[] = 'text-align:right;';
							}
						}

						// Unité d'une valeur
						if (isset($v_line['unite'])) {
							if ($v_line['unite'] != '') {
								$unite = '&nbsp;'.$v_line['unite'];
							}
						} else {
							if (! empty($v_calc['unite'])) {
								$unite = '&nbsp;'.$v_calc['unite'];
							}
						}

						// Nombre de décimales d'une valeur
						if (isset($v_line['decimales'])) {
							$value = number_format($value, $v_line['decimales'], '.', '&nbsp;');
							$decimales = $v_line['decimales'];
						} else {
							if (! empty($v_calc['decimales'])) {
								$value = number_format($value, $v_calc['decimales'], '.', '&nbsp;');
								$decimales = $v_calc['decimales'];
							}
						}

						if (isset($v_line['type'])  &&  $v_line['type'] == 'compar') {
							$style[] = $borderBottom;
						}

						// Comparaison des deux périodes et création de la tooltip
						if (isset($v_line['type'])  &&  $v_line['type'] == 'compar') {

							if ($valueN_Calc[$k_calc][$col] != $valBrut) {

								$title = array();

								$val = ($valueN_Calc[$k_calc][$col] - $valBrut);

								// Différence des 2 plage en nombre
								if (isset($decimales)) {
									$val  = number_format($val, $decimales, '.', ' ');
								}
								if (! empty($unite)) {
									$val .= str_replace("&nbsp;", " ", $unite);
								}

								if ($valueN_Calc[$k_calc][$col] > $valBrut) {
									$title[] = '+' . $val;
								} else {
									$title[] = $val;
								}

								// Différence des deux plage en pourcentage
								if ($valBrut > 0) {
									$pourcentage = (($valueN_Calc[$k_calc][$col] / $valBrut) - 1) * 100;
									$affPourcent = number_format($pourcentage, 2, '.', ' ');
									if ($pourcentage > 0) {
										$affPourcent = '+' . $affPourcent;
									}
									$title[] = $affPourcent . '%';
								}

								$attrsDiv['data-tooltip'] = "true";
								$attrsDiv['title'] = 'Evolution : ' . implode(' | ', $title);

								$style[] = 'cursor:default;';

								$divTitleDeb = '<div '.$this->buildAttrs($attrsDiv).'>';
								$divTitleFin = '</div>';
							}
						}

						$attrs['style'] = implode(" ", $style);

						$html .= '<td ' . $this->buildAttrs($attrs) . '>' . $divTitleDeb . $value . $unite . $divTitleFin . '</td>';
					}
					$col++;
				}
			}

			$html .= '</tr>';

			if (! isset($v_line['group'])) {
				$line++;
			}
		}

		return $html;
	}


	protected function renderTbodyTotaux()
	{
		$html = '';

		if (count($this->columnsValue) == 0) {
			return $html;
		}

		$html .= '<tr><td style="height:15px; border-left:0; border-right:0; background:#fff;" colspan="' . (count($this->columnsValue['normal']) + 1) . '"></td></tr>' . chr(10);

		$html .= '<tr>' . chr(10);
		$html .= '<td style="background:#fff;" class="intitule">' . str_replace(" ", "&nbsp;",$this->ligneTotauxConf['libelle']) . '</td>' . chr(10);

		// Tableau des champs, totaux des lignes inclus pour l'alignement et la couleur des champs
		$listeChp = array_merge($this->libelle, $this->results_line);

		foreach ($this->columnsValue['normal'] as $k=>$v) {

			$attrs 	= array();
			$unite 	= '';
			$calcul	= $this->ligneTotauxConf['calcul'];

			if (is_callable(array($this, $calcul))) {

				$style = array();
				$style[] = 'font-weight: bold;';

				$value = $this->{$calcul}($this->columnsValue['normal'][$k]);

				// Couleur de fond
				if (! empty($listeChp[$k]['bg_value'])) {
					$style[] = 'background:'.$listeChp[$k]['bg_value'].';';
				} else {
					$style[] = 'background:#fff;';
				}

				// Alignement d'une valeur
				if (! empty($listeChp[$k]['align'])) {
					$style[] = 'text-align:'.$listeChp[$k]['align'].';';
				} else {
					$style[] = 'text-align:right;';
				}

				// Unité d'une valeur
				if (! empty($this->ligneTotauxConf['unite'])) {
					$unite = '&nbsp;'.$this->ligneTotauxConf['unite'];
				}

				// Nombre de décimales d'une valeur
				if (! empty($this->ligneTotauxConf['decimales'])) {
					$value = number_format($value, $this->ligneTotauxConf['decimales'], '.', '&nbsp;');
				}

				$attrs['style'] = implode(" ", $style);

			} else {

				$value = 'Erreur action';
			}

			$html .= '<td ' . $this->buildAttrs($attrs) . '>'.$value.$unite.'</td>' . chr(10);
		}

		$html .= '</tr>' . chr(10);

		// S'il y a une comparaison de période, on affiche la ligne des totaux N-1
		if (isset($this->columnsValue['compar'])  &&  count($this->columnsValue['compar']) > 0) {

			$html .= '<tr><td style="height:15px; border-left:0; border-right:0; background:#fff;" colspan="' . (count($this->columnsValue['normal']) + 1) . '"></td></tr>' . chr(10);

			$html .= '<tr>' . chr(10);
			$html .= '<td style="background:#efefef;" class="intitule">' . str_replace(" ", "&nbsp;",$this->ligneTotauxConf['libelle']) . ' N-1</td>' . chr(10);

			// Tableau des champs, totaux des lignes inclus pour l'alignement et la couleur des champs
			$listeChp = array_merge($this->libelle, $this->results_line);

			foreach ($this->columnsValue['compar'] as $k=>$v) {

				$divTitleDeb = '';
				$divTitleFin = '';

				$attrs 	= array();
				$unite 	= '';
				$calcul	= $this->ligneTotauxConf['calcul'];

				if (is_callable(array($this, $calcul))) {

					$style = array();
					$style[] = 'font-weight: bold;';

					$valueN = $this->{$calcul}($this->columnsValue['normal'][$k]);
					$value  = $this->{$calcul}($this->columnsValue['compar'][$k]);
					$valBrut= $value;

					// Couleur de fond
					if (! empty($listeChp[$k]['bg_value'])) {
						$style[] = 'background:'.$listeChp[$k]['bg_value'].';';
					} else {
						$style[] = 'background:#efefef;';
					}

					// Alignement d'une valeur
					if (! empty($listeChp[$k]['align'])) {
						$style[] = 'text-align:'.$listeChp[$k]['align'].';';
					} else {
						$style[] = 'text-align:right;';
					}

					// Unité d'une valeur
					if (! empty($this->ligneTotauxConf['unite'])) {
						$unite = '&nbsp;'.$this->ligneTotauxConf['unite'];
					}

					// Nombre de décimales d'une valeur
					if (! empty($this->ligneTotauxConf['decimales'])) {
						$value = number_format($value, $this->ligneTotauxConf['decimales'], '.', '&nbsp;');
					}

					// Comparaison des deux périodes et création de la tooltip
					if ($valueN != $value) {

						$title = array();

						$val = ($valueN - $valBrut);

						// Différence des 2 plage en nombre
						if (isset($this->ligneTotauxConf['decimales'])) {
							$val  = number_format($val, $this->ligneTotauxConf['decimales'], '.', ' ');
						}
						if (! empty($unite)) {
							$val .= str_replace("&nbsp;", " ", $unite);
						}

						if ($valueN > $valBrut) {
							$title[] = '+' . $val;
						} else {
							$title[] = $val;
						}

						// Différence des deux plage en pourcentage
						if ($valBrut > 0) {
							$pourcentage = (($valueN / $valBrut) - 1) * 100;
							$affPourcent = number_format($pourcentage, 2, '.', ' ');
							if ($pourcentage > 0) {
								$affPourcent = '+' . $affPourcent;
							}
							$title[] = $affPourcent . '%';
						}

						$attrsDiv['data-tooltip'] = "true";
						$attrsDiv['title'] = 'Evolution : ' . implode(' | ', $title);

						$style[] = 'cursor:default;';

						$divTitleDeb = '<div '.$this->buildAttrs($attrsDiv).'>';
						$divTitleFin = '</div>';
					}

					$attrs['style'] = implode(" ", $style);

				} else {

					$value = 'Erreur action';

				}


				$html .= '<td ' . $this->buildAttrs($attrs) . '>' . $divTitleDeb . $value . $unite . $divTitleFin . '</td>' . chr(10);
			}

			$html .= '</tr>' . chr(10);
		}

		return $html;
	}

	protected function renderTfoot()
	{
		return '';
		//return '<tfoot></tfoot>' . chr(10);
	}


	protected function renderGraph()
	{
		return '<div style="margin-top:30px;" id="graph_' . $this->id . '"></div>' . chr(10);
	}


	protected function getTableAttributes()
	{
		$attrs = array('style' => 'width:'.$this->width.';');

		return $attrs;
	}


	/**
	 * Mise ne forme des attribus d'une balise HTML
	 *
	 * @param 	string 	$attrs
	 * @return 	string
	 */
	protected function buildAttrs($attrs)
	{
		$html = '';
		foreach ($attrs as $k => $v) {
			$html .= $k . '="' . htmlentities($v, ENT_QUOTES, 'UTF-8') . '" ';
		}
		return trim($html);
	}


	/**
	 * Calcul d'un somme
	 *
	 * @param 	array 	$lineValues
	 * @return 	number
	 */
	protected function somme($lineValues)
	{
		$somme = 0;
		foreach ($lineValues as $value) {
			$somme += $value;
		}
		return $somme;
	}


	/**
	 * Calcul d'une moyenne
	 *
	 * @param	array 	$lineValues
	 * @return 	number
	 */
	protected function moyenne($lineValues)
	{
		$somme 		= $this->somme($lineValues);
		$moyenne	= $somme / count($lineValues);

		return $moyenne;
	}


	/**
	 * Calcul d'un médiane
	 *
	 * @param 	array 	$lineValues
	 * @return 	number
	 */
	protected function mediane($lineValues)
	{
		sort($lineValues);

		$count 		= count($lineValues);
		$middleval 	= floor(($count - 1) / 2);

		// Nb de chiffres impaires
		if ($count % 2) {
			$median = $lineValues[$middleval];
		// Nb de chiffres pairs
		} else {
			$low 	= $lineValues[$middleval];
			$high 	= $lineValues[$middleval + 1];
			$median	= (($low + $high) / 2);
		}

		return $median;
	}


	/**
	 * Retourne la valeur min d'une suite de chiffres
	 *
	 * @param 	array	$lineValues
	 * @return 	number
	 */
	protected function min($lineValues)
	{
		sort($lineValues);
		return $lineValues[0];
	}


	/**
	 * Retourne la valeur max d'une suite de chiffres
	 *
	 * @param 	array	$lineValues
	 * @return 	number
	 */
	protected function max($lineValues)
	{
		rsort($lineValues);
		return $lineValues[0];
	}


	public function render()
	{
		if (is_callable(array($this, $this->action))) {
			return $this->{$this->action}();
		}
	}

	public function renderPage()
	{
		$r = new renderer($this);
		return $r->render();
	}


	/**
	 * Lien pour passer en recherche de statistique sans comparaison
	 */
	protected function affLinkNormal()
	{
		if ($this->compar === true) {

			$url  = explode('?', $_SERVER['REQUEST_URI']);
			$file = $url[0];

			if (count($url) > 1  &&  !empty($url[1])) {

				$get  = explode('&', $url[1]);

				$newGet = array();
				$delete = array(
					'compar',
					'dtp_deb_compar',
					'dtp_fin_compar');

				foreach ($get as $v) {
					$k = explode('=', $v);

					if (!in_array($k[0], $delete)) {
						$newGet[] = $v;
					}
				}

				$affNewGet = '';
				if (count($newGet) > 0)  {
					$affNewGet = '?' . implode('&', $newGet);
				}

				$html = '<a href="' . $file . $affNewGet . '">Normal</a>';
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
	protected function affLinkCompar()
	{
		if ($this->compar === true) {

			$html = 'Comparaison';

		} else {

			$url  = explode('?', $_SERVER['REQUEST_URI']);
			$file = $url[0];

			if (count($url) > 1  &&  !empty($url[1])) {

				$get = explode('&', $url[1]);

				if (!in_array('compar', $get)) {
					$get[] = 'compar=1';
				}

				$get = implode('&', $get);

				$html = '<a href="' . $file . '?' . $get . '">Comparaison</a>';
			} else {
				$html = '<a href="' . $_SERVER['REQUEST_URI'] . '?compar=1">Comparaison</a>';
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


	/**
	 * Permet de modifier une couleur hexadécimale pour récupérer une variante proche
	 */
	public static function pantoneColor($colorHexa) {

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
}
