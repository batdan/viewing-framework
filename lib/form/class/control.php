<?php
namespace form;

use core\dbSingleton;

/**
 * Actions sur le formulaire
 *
 * @author Daniel Gomes
 */
class control
{
	/**
	 * Liste des attributs
	 */
	private $_dbh;

	private $_form;

	private $_options;

	private $_dom;			// Gestion en dom du code généré

	private $_verrouOn = false;
	private $_verrouUserId;
	private $_disabledSave;


	/**
	 * Hydratation de la classe et initialisation
	 * @param array $data
	 */
	public function __construct($form, array $options=[], $activationVerrou=true, $disabledSave=false)
	{
		// Instance PDO
		$this->_dbh = dbSingleton::getInstance();

		// Désactivation du bouton save
		$this->_disabledSave = $disabledSave;

		$this->setForm($form);
		$this->setOptions($options);

		// Vérrification vérouillage formulaire
		if ($activationVerrou) {
			$this->checkVerrou();
		}

		if ($this->_verrouOn) {
			$this->initVerrou();
		} else {
			$this->init();
		}

		$this->getDom();
	}


	/**
	 * Setters
	 */
	public function setForm($form) {
		$this->_form = $form;
	}

	public function setOptions($options) {
		$this->_options = array_merge($this->defaultOptions(), $options);
	}

	private function defaultOptions()
	{
		return [
			'delete' => true,
		];
	}


	/**
	 * Vérrification vérouillage formulaire
	 */
	private function checkVerrou()
	{
		if ($this->_form->getVerrou()) {

			$req = "SELECT id_user, date_lock FROM form_lock WHERE uri = :uri";
			$sql = $this->_dbh->prepare($req);
			$sql->execute( array( ':uri'=>$_SERVER['REQUEST_URI'] ));

			if ($sql->rowCount() > 0) {

				$res = $sql->fetch();

				if ($res->id_user != $_SESSION['auth']['id']) {

					$dateLock = new \DateTime($res->date_lock);
					$dateNow  = new \DateTime();

					$diffSec = $dateNow->getTimestamp() - $dateLock->getTimestamp();

					// Un  formulaire n'ayant pas été pingué depuis 3 minutes peut être déverrouillé
					// et reverrouillé par l'utilisateur courant
					if ($diffSec > 185) {
						$this->verrou('update');
					} else {
						$this->_verrouOn 	 = true;
						$this->_verrouUserId = $res->id_user;
					}

				} else {

					// Fiche verrouillé par l'utilisateur courant, mise à jout de la date et de l'heure
					$this->verrou('update');
				}

			} else {

				// Personne n'a verrouillé cette fiche, l'utilisateur courant la verrouille
				$this->verrou();
			}
		}
	}


	/**
	 * Vérouillage de la fiche
	 *
	 * @param 		string		$actionSql			Action Sql ( INSERT | UPDATE )
	 */
	private function verrou($actionSql = 'insert')
	{
		switch ($actionSql) {
			case 'insert' : 	$req = "INSERT INTO form_lock (id_user, date_lock, uri) VALUES (:id_user, NOW(), :uri)"; 		break;
			case 'update' : 	$req = "UPDATE form_lock SET id_user = :id_user, date_lock = NOW() WHERE uri = :uri";			break;
		}

		$sql = $this->_dbh->prepare($req);
		$sql->execute( array(
								':id_user' => $_SESSION['auth']['id'],
								':uri'	   => $_SERVER['REQUEST_URI'],
							));
	}


	/**
	 * Retourne le code HTML généré
	 */
	public function getHTML()
	{
		return $this->_dom->saveHTML();
	}


	/**
	 * Getter - Retourne le dom généré à partir du code HTML
	 * @return \DOMDocument
	 */
	public function getDom()
	{
		return $this->_dom;
	}


	/**
	 * Création des boutons de contrôle
	 */
	private function init()
	{
		$formName	= $this->_form->getName();
		$urlSortie  = $this->_form->getUrlSortie();

		$this->_dom = new \DOMDocument("1.0", "utf-8");

		// Champ caché pour récupérer l'action demandée sur le formulaire
		$actionForm = $this->_dom->createElement('input');
		$actionForm->setAttribute('type', 'hidden');
		$actionForm->setAttribute('name', 'actionForm');
		$actionForm->setAttribute('id', 'actionForm_id');
		$actionForm->setAttribute('value', '');

		// Conteneur de champ
		$div = $this->_dom->createElement('div');
		$div->setAttribute('class', 'btn-group pull-right');
		$div->setAttribute('role', 'group');
		$div->setAttribute('aria-label', 'actionsForm');

		// Bouton quitter ------------------------------------------------------
		if (isset($this->_options['custumBtn']['exit'])) {

			$buttonExit = $this->customBtn($this->_options['custumBtn']['exit']);

		}  else {

			$buttonExit = $this->_dom->createElement('button');
			$buttonExit->setAttribute('type',  'button');
			$buttonExit->setAttribute('class', 'btn btn-default btn-sm');
			$buttonExit->setAttribute('style', 'width:70px;');
			$buttonExit->setAttribute('title', 'Quit | Ctrl + q');

			$image3 = $this->_dom->createElement('i');
			$image3->setAttribute('class', 'fa fa-arrow-left');

			$buttonExit->appendChild($image3);
		}

		$buttonExit->setAttribute('id',    'btnExit');


		// Bouton envoyer ------------------------------------------------------
		if (isset($this->_options['custumBtn']['save'])) {

			$buttonSubmit = $this->customBtn($this->_options['custumBtn']['save']);

		} else {

			$buttonSubmit = $this->_dom->createElement('button');
			$buttonSubmit->setAttribute('type',  'submit');
			$buttonSubmit->setAttribute('class', 'btn btn-default btn-sm');
			$buttonSubmit->setAttribute('style', 'width:70px;');
			$buttonSubmit->setAttribute('title', 'Save | Ctrl + s');

			$image1 = $this->_dom->createElement('i');
			$image1->setAttribute('class', 'fa fa-floppy-o');

			$buttonSubmit->appendChild($image1);
		}

		$buttonSubmit->setAttribute('id', 'btnSave');

		if (isset($this->_options['custumBtn']['save']) && $this->_options['custumBtn']['save']['activ'] === false) {
			$buttonSubmit->setAttribute('id', 'myBtn');
		}


		// Bouton sauvegarder et quitter ---------------------------------------
		if (!empty($this->_form->getClePrimaireId())) {

			if (isset($this->_options['custumBtn']['saveExit'])) {

				if (! isset($this->_options['custumBtn']['saveExit']['activ']) || $this->_options['custumBtn']['saveExit']['activ'] !== false) {
					$buttonSaveExit = $this->customBtn($this->_options['custumBtn']['saveExit']);
					$buttonSaveExit->setAttribute('id', 'btnSaveExit');
				}

			} else {

				$buttonSaveExit = $this->_dom->createElement('button');
				$buttonSaveExit->setAttribute('id',    'btnSaveExit');
				$buttonSaveExit->setAttribute('type',  'submit');
				$buttonSaveExit->setAttribute('class', 'btn btn-default btn-sm');
				$buttonSaveExit->setAttribute('style', 'width:70px;');
				$buttonSaveExit->setAttribute('title', 'Save & Quit | Ctrl + Shift + s');

				$image2 = $this->_dom->createElement('i');
				$image2->setAttribute('class', 'fa fa-check-square-o');

				$buttonSaveExit->appendChild($image2);
			}

			// Bouton supprimer ----------------------------------------------------
			if ($this->_options['delete'] !== false) {
				// Modal de la corbeille
				foreach ($this->modalTrash()->childNodes as $child) {
		            $newNode = $this->_dom->importNode($child, true);
		            $this->_dom->appendChild($newNode);
		        }

				// Bouton corbeille
				$this->_dom->appendChild($this->buttonTrash());
			}
		}

		// Affichage groupe de boutons
		$div->appendChild($buttonExit);
		$div->appendChild($buttonSubmit);

		// affichage ou non du bouton 'Save & Quit'
		$affBtnSaveExit = true;
		if (empty($this->_form->getClePrimaireId())) {
			$affBtnSaveExit = false;
		}
		if (isset($this->_options['custumBtn']['saveExit']['activ']) && $this->_options['custumBtn']['saveExit']['activ'] === false) {
			$affBtnSaveExit = false;
		}

		if ($affBtnSaveExit === true) {
			$div->appendChild($buttonSaveExit);
		}

		$this->_dom->appendChild($div);

		// Action demandée sur le formulaire
		$this->_dom->appendChild($actionForm);

		// Renvoi le nom du champ caché de control au formulaire
		$this->_form->setActionForm('actionForm');

		// Code javascript commande clavier
		$js = <<<eof
$(window).bind('keydown', function(event) {
    if (event.ctrlKey || event.metaKey) {
		if (event.which == 83) {
			if (event.shiftKey) {				// Ctrf + Shift + s 	(83 -> 's')
				event.preventDefault();
				saveExit();
			} else {							// Ctrf + Shift + s
				event.preventDefault();
				$('#actionForm_id').attr('value', 'save');
				$('form[name="$formName"]').submit();
			}
		}
		if (event.which == 81) {				// Ctrl + q 			(81 -> 'q')
			event.preventDefault();
			formQuit();
		}
    }
});

// Evenement "Sauver"
$('#btnSave').click( function(event) {
	event.preventDefault();
	$(this).blur();
	$('#actionForm_id').attr('value','save');
	$('form[name="$formName"]').submit();
});

// Evenement click sur bouton "Sauver et quitter"
$('#btnSaveExit').click( function(event) {
	event.preventDefault();
	$(this).blur();
	saveExit();
});

// Evenement "Quitter"
$('#btnExit').click( function(event) {
	event.preventDefault();
	$(this).blur();
	formQuit();
});

// Sauver et quitter
function saveExit()
{
	$.post("/vendor/vw/framework/lib/form/ajax/ajax_verrouOff.php",
	{
		action : 'verrouOff',
		uri : window.location.pathname + window.location.search
	},
	function success(data)
	{
		$('#actionForm_id').attr('value','saveExit');
		$('form[name="$formName"]').submit();
	});
}

// Quitter
function formQuit()
{
	$.post("/vendor/vw/framework/lib/form/ajax/ajax_verrouOff.php",
	{
		action : 'verrouOff',
		uri : window.location.pathname + window.location.search
	},
	function success(data)
	{
		document.location.href = '$urlSortie';
	});
}
eof;
		\core\libIncluder::add_JsScript($js);
	}


	/**
	 * Affichage des informations sur le verrouillage du formulaire
	 */
	private function initVerrou()
	{
		// Information sur l'utilisateur qui verrouille le formulaire
		$req = "SELECT prenom, nom FROM users WHERE id = :id";
		$sql = $this->_dbh->prepare($req);
		$sql->execute( array( ':id'=>$this->_verrouUserId ));
		$res = $sql->fetch();

		$this->_dom = new \DOMDocument("1.0", "utf-8");

		// Champ caché pour récupérer l'action demandée sur le formulaire
		$actionForm = $this->_dom->createElement('input');
		$actionForm->setAttribute('type', 'hidden');
		$actionForm->setAttribute('name', 'actionForm');
		$actionForm->setAttribute('id', 'actionForm_id');
		$actionForm->setAttribute('value', 'verrouOn');

		// Renvoi le nom du champ caché de control au formulaire
		$this->_form->setActionForm('actionForm');

		// Conteneur
		$div = $this->_dom->createElement('span', 'Fiche verrouillée par ' . $res->prenom . ' ' . $res->nom);
		$div->setAttribute('class', 'pull-right label label-danger');
		$div->setAttribute('style', 'font-weight:normal; font-size:14px; padding:8px 10px 9px 10px;');

		$this->_dom->appendChild($div);

		$this->_dom->appendChild($actionForm);
	}


	private function buttonTrash()
	{
		if (isset($this->_options['custumBtn']['trash'])) {

			$buttonTrash = $this->customBtn($this->_options['custumBtn']['trash']);

		} else {

			$buttonTrash = $this->_dom->createElement('button');
			$buttonTrash->setAttribute('type', 'button');
			$buttonTrash->setAttribute('class', 'btn btn-danger btn-sm pull-right');
			$buttonTrash->setAttribute('title', 'Supprimer');
			$buttonTrash->setAttribute('style', 'width:70px; margin-left:10px;');

			$image = $this->_dom->createElement('i');
			$image->setAttribute('class', 'fa fa-trash-o');

			$buttonTrash->appendChild($image);
		}

		$buttonTrash->setAttribute('id', $this->_form->getName() . '_trash_id');
		$buttonTrash->setAttribute('data-toggle', 'modal');
		$buttonTrash->setAttribute('data-target', '#trashModal');


		return $buttonTrash;
	}


	/**
	 * Création de boutons customisés
	 */
	private function customBtn($confBtn)
	{
		$customBtn = $this->_dom->createElement('button');
		$customBtn->setAttribute('id',    'btnExit');
		$customBtn->setAttribute('type',  'button');

		// Chargement des attributs
		if (is_array($confBtn['attr'])) {
			foreach($confBtn['attr'] as $keyAttr => $valAttr) {
				$customBtn->setAttribute($keyAttr, $valAttr);
			}
		}

		// Insertion d'une image dans le bouton (classe d'une font de pictos, type fontAwesome)
		if (isset($confBtn['mode']) && $confBtn['mode'] == 'img') {

			$image = $this->_dom->createElement('i');

			if (isset($confBtn['img'])) {
				$image->setAttribute('class', $confBtn['img']);
			} else {
				$image->setAttribute('class', 'fa fa-arrow-left');
			}

			$customBtn->appendChild($image);
		}

		// Insertion de texte dans le bouton
		if (isset($confBtn['mode']) && $confBtn['mode'] == 'txt') {
			$text = $this->_dom->createTextNode($confBtn['txt']);
			$customBtn->appendChild($text);
		}

		return $customBtn;
	}


	private function modalTrash()
	{
		$formName 	= $this->_form->getName();
		$formNameId = $formName . '_id';
		$trashId	= $formName . '_trash_id';
		$onclick	= "$('#actionForm_id').attr('value','trash'); $('#".$formNameId."').submit();";

		$html = <<<eof
<div class="modal fade" id="trashModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close no-select" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Suppression</h4>
			</div>
			<div class="modal-body">
				<p>Voulez-vous supprimer cette fiche ?</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Annuler</button>
				<button type="button" class="btn btn-danger"  data-dismiss="modal" onclick="$onclick">Supprimer</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
eof;

		$modalTrash = new \tpl\addHtml($html);
		return $modalTrash->getDom();
	}
}
