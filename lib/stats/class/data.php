<?php
namespace vw\stats;

use core\libIncluder;

/**
 * Création d'une statistique sur la base d'un tableau PHP
 *
 * @author Daniel Gomes
 */
class data extends base
{
	/**
	 * @var array
	 */
	protected $data;
	protected $compar = false;	// Boolean : mode normal ou comparaison

	protected $datedeb;			// Date début au format 	: yyyy-mm-dd
	protected $datefin;			// Date fin au format  		: yyyy-mm-dd

	protected $datedebCompar;	// Date début au format 	: yyyy-mm-dd (comparaison)
	protected $datefinCompar;	// Date fin au format  		: yyyy-mm-dd (comparaison)

	protected $chpDateType;		// Type de champ date : date | time | datetime

	protected $htmlEndForm='';
	protected $htmlEndLink='';

	protected $dtpDeb;			// Champ de recherche : début plage
	protected $dtpFin;			// Champ de recherche : fin plage
	protected $dtpDebCompar;	// Champ de recherche : début plage (comparaison)
	protected $dtpFinCompar;	// Champ de recherche : fin plage (comparaison)

	protected $activSearch;		// Activation du moteur de recherche par date


	public function sethtmlEndForm($htmlEndForm)
	{
		$this->htmlEndForm=$htmlEndForm;
	}

	public function sethtmlEndLink($htmlEndLink)
	{
		$this->htmlEndLink=$htmlEndLink;
	}


	protected function getDefaultOptions()
	{
		return array_merge(parent::getDefaultOptions(), array(
			'data' 				=> array(),
			'chpDateType'		=> 'date',

			'activSearch'		=> false,

			'dtpDeb'			=> '',
			'dtpFin'			=> '',
			'dtpDebCompar'		=> '',
			'dtpFinCompar'		=> '',
		));
	}


	/**
	 * Configuration du stats data
	 *
	 * @param  array 	$options 		Tableau d'options du Stats Data
	 */
	protected function initOptions($options)
	{
		parent::initOptions($options);
		$this->type = 'array';

		if (isset($_GET['compar'])) {
			$this->compar = true;
		}

		$this->data 		= $options['data'];
		$this->chpDateType 	= $options['chpDateType'];

		$this->activSearch 	= $options['activSearch'];

		// Récupération des variables GET
		$this->checkGET();
	}


	/**
	 * Récupération et stockage des variable GET du moteur de recherche
	 */
	private function checkGET()
	{
		if (isset($_GET['dtp_deb'])) {
			$this->dtpDeb = $_GET['dtp_deb'];
		}

		if (isset($_GET['dtp_fin'])) {
			$this->dtpFin = $_GET['dtp_fin'];
		}

		if ($this->compar === true) {

			if (isset($_GET['dtp_deb_compar'])) {
				$this->dtpDebCompar = $_GET['dtp_deb_compar'];
			}

			if (isset($_GET['dtp_fin_compar'])) {
				$this->dtpFinCompar = $_GET['dtp_fin_compar'];
			}
		}
	}


	/**
	 * Moteur de recherche du gestionaire de statistiques
	 */
	protected function plageDateTimePicker()
	{
		if ($this->activSearch !== true) {
			return;
		}

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
}
