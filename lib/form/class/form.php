<?php
namespace form;

/**
 * Permet de générer simplement des Formulaires
 *
 * @author Daniel Gomes
 */
class form
{
    /**
     * Liste des attributs
     */
	private   $_dbh;							// Instance PDO
	private   $_bddName			= 'default';	// Nom de la base de données

	protected $_name;							// Nom du formulaire
    protected $_methode 		= 'POST';		// Méthode d'envoi du formulaire
    protected $_table;							// Table lié au formulaire
    protected $_clePrimaireName;				// Nom de la clé primaire de la table
    protected $_clePrimaireId   = '';			// id de la ligne appelée (renseignée = update, vide = intert)
	protected $_listElements	= array();		// Liste des instances d'éléments de formulaire
	protected $_listChamps		= array();		// Liste des éléments de formulaire -> champ BDD (tableau clés / valeurs)
	protected $_infosBDD		= array();		// Liste des infos nécessaire aux INSERT ou UPDATE
	protected $_actionForm;						// Action sur le formulaire
    protected $_urlSortie;						// Url de retour une fois le formulaire validé
	protected $_width;							// Largeur par défaut du formulaire
	protected $_class			= 'col-xs-10';	// Largeur responsive

    protected $_dateCreaName 	= 'date_crea';	// Nom du champ par défaut lors de la création d'une entrée en base
    protected $_dateModifName	= 'date_modif';	// Nom du champ par défaut lors d'un update en base

	protected $_verifExist		= true;			// Si la clé primaire est renseigné, on vérifie si l'id existe en BDD

	protected $_curlInSave		= array();		// Permet de rajouter du code à la sauvegarde d'un élément de formulaire
	protected $_curlAfterSave	= array();		// Permet de rajouter du code à exécuter une fois la méthode 'save' ou 'save & exit' réussie

	protected $_jsAfterSave		= array();		// Code JS exécuté après la sauvegarde

    protected $_html;							// Code HTML généré par le formulaire
    protected $_dom;							// Gestion en dom du code généré
    protected $_domElements;					// Elements de formulaire en dom

	protected $_forceReload;					// Permet de forcer le reload après la sauvegarde d'un formulaire

	protected $_verrou			= true;			// Gestion du verrouillage des formulaires


    /**
     * Hydratation de la classe et initialisation
     * @param array $data
     */
    public function hydrateAndInit(array $data)
    {
    	foreach ($data as $k=>$v)
    	{
    		$method = 'set'.ucfirst($k);

    		if (method_exists($this, $method)) {
    			$this->$method($v);
    		}
    	}

		// Instance PDO
		$this->_dbh = \core\dbSingleton::getInstance($this->_bddName);

		// Css des formulaire
		\core\libIncluderList::add_vwForm();

    	$this->init();
    }


    /**
     * Setters
     */
    public function setName($name) {
    	$this->_name = $name;
    }
    public function setMethode($methode) {
    	$this->_methode = $methode;
    }
	public function setBddName($bddName) {
		$this->_bddName = $bddName;
	}
    public function setTable($table) {
    	$this->_table = $table;
    }
    public function setClePrimaireName($clePrimaireName) {
    	$this->_clePrimaireName = $clePrimaireName;
    }
    public function setClePrimaireId($clePrimaireId) {
    	$this->_clePrimaireId = $clePrimaireId;
    }
    public function setUrlSortie($urlSortie) {
    	$this->_urlSortie = $urlSortie;
    }
    public function setWidth($width) {
    	$this->_width = $width;
    }
	public function setClass($class) {
		$this->_class = $class;
	}
    public function setDateCreaName($dateCreaName) {
    	$this->_dateCreaName = $dateCreaName;
    }
    public function setDateModifName($dateModifName) {
    	$this->_dateModifName = $dateModifName;
    }
	public function setListElements($element) {
		$this->_listElements[] = $element;
	}
	public function setListChamps($champs) {
		foreach ($champs as $k=>$v) {
			$this->_listChamps[$k] = $v;
		}
	}
	public function setActionForm($actionForm) {
		$this->_actionForm = $actionForm;
	}
	public function setCurlInSave($array) {
		$this->_curlInSave[] = $array;
	}
	public function setCurlAfterSave($array) {
		$this->_curlAfterSave[] = $array;
	}
	public function setJsAfterSave($js) {
		$this->_jsAfterSave[] = $js;
	}
	public function setForceReload() {
		$this->_forceReload = true;
	}
	public function setVerrou($verrou) {
		$this->_verrou = $verrou;
	}


	/**
	 * Getters
	 */
	public function getName() {
		return $this->_name;
	}
	public function getListChamps() {
		$listChamps = array();
		foreach ($this->_listElements as $element) {
			$listChamps[$element->getChamp()] = '';
		}
		return $listChamps;
	}
	public function getListChampsSaveForm() {
		return $this->_listChamps;
	}
	public function getClePrimaireId() {
		return $this->_clePrimaireId;
	}
	public function getBddName() {
		return $this->_bddName;
	}
	public function getTable() {
		return $this->_table;
	}
	public function getUrlSortie() {
		return $this->_urlSortie;
	}
	public function getSep() {
		return '<div class="sep">&nbsp;</div>';
	}
	public function getVerrou() {
		return $this->_verrou;
	}


    /**
     * Initialise le formulaire
     */
    private function init()
    {
    	$this->_dom = new \DOMDocument("1.0", "utf-8");

		$container = $this->_dom->createElement('div');
		if (! empty($this->_width)) {
			$container->setAttribute('style', 'width: ' . $this->_width);
		} else {
			$container->setAttribute('class', $this->_class);
		}

		$id = $this->_name . '_id';

    	$form = $this->_dom->createElement('form');
    	$form->setAttribute('name', 		$this->_name);
    	$form->setAttribute('id', 			$id);
    	$form->setAttribute('method', 		$this->_methode);
		$form->setAttribute('class',		'form-horizontal');
		$form->setAttribute('action',		'');
		if ($this->_verrou) {
			$form->setAttribute('verrou',	1);
		}

		// Champs cachés
		$table = $this->_dom->createElement('input');
		$table->setAttribute('type',		'hidden');
		$table->setAttribute('name',		$this->_name . '_tableBDD');
		$table->setAttribute('value',		$this->_table);
		$form->appendChild($table);
		$this->_infosBDD[] = 'tableBDD';

		$clePrimaireName = $this->_dom->createElement('input');
		$clePrimaireName->setAttribute('type',		'hidden');
		$clePrimaireName->setAttribute('name',		$this->_name . '_clePrimaireName');
		$clePrimaireName->setAttribute('value',		$this->_clePrimaireName);
		$form->appendChild($clePrimaireName);
		$this->_infosBDD[] = 'clePrimaireName';

		$clePrimaireId = $this->_dom->createElement('input');
		$clePrimaireId->setAttribute('type',		'hidden');
		$clePrimaireId->setAttribute('name',		$this->_name . '_clePrimaireId');
		$clePrimaireId->setAttribute('value',		$this->_clePrimaireId);
		$form->appendChild($clePrimaireId);
		$this->_infosBDD[] = 'clePrimaireId';

		$date_crea = $this->_dom->createElement('input');
		$date_crea->setAttribute('type',	'hidden');
		$date_crea->setAttribute('name',	$this->_name . '_dateCreaNameBDD');
		$date_crea->setAttribute('value',	$this->_dateCreaName);
		$form->appendChild($date_crea);
		$this->_infosBDD[] = 'dateCreaNameBDD';

		$date_modif = $this->_dom->createElement('input');
		$date_modif->setAttribute('type',	'hidden');
		$date_modif->setAttribute('name',	$this->_name . '_dateModifNameBDD');
		$date_modif->setAttribute('value',	$this->_dateModifName);
		$form->appendChild($date_modif);
		$this->_infosBDD[] = 'dateModifNameBDD';

		$container->appendChild($form);
    	$this->_dom->appendChild($container);

		// Chargement librairie JS
		\core\libIncluderList::add_bootstrapValidator();
    }


    /**
     * Permet d'insérer du code la fin des éléments contenus entre les balises <form>
     */
    public function append($element)
    {
    	$xpath = new \DOMXPath($this->_dom);

    	$query	= '//form';
    	$entries= $xpath->query($query);
    	$form	= $entries->item(0);

    	$nodes	= $element->getDom();

    	foreach ($nodes->childNodes as $child) {
    		$newNode = $this->_dom->importNode($child, true);
    		$form->appendChild($newNode);
    	}
    }


	/**
	 * Requête SELECT -> Chargement des données du formulaire
	 */
	private function select()
	{
		// On vérifie si la table existe
		$this->checkTable();

		// On ne conserve que les champs existants dans la table
		$listeChamps = array_keys($this->checkChamps());

		$req = "SELECT " . implode(", ", $listeChamps) . " FROM " . $this->_table . " WHERE " . $this->_clePrimaireName . " = :clePrimaireId";
		$sql = $this->_dbh->prepare($req);
		$sql->execute(array('clePrimaireId' => $this->_clePrimaireId));

		if ($sql->rowCount() > 0) {

			$res = $sql->fetch();

			// Récupération des valeurs stockées en BDD
			foreach ($listeChamps as $k=>$v) {
				$this->_listChamps[$v] = $res->$v;
			}

			// Modifieur au chargement -> méthode load() de la classe 'element'
			$keysListChamps = array_keys($this->_listChamps);

			for ($i=0; $i<count($keysListChamps); $i++) {
				foreach ($this->_listElements as $element) {
					if ($element->getChamp() == $keysListChamps[$i]  &&  $element->getLoad() === true) {
						$this->_listChamps = $element->load($this->_listChamps);
					}
				}
			}
		} else {
			$this->_verifExist = false;
		}
	}


	/**
	 * Vérification de l'existence de la table
	 */
	private function checkTable()
	{
		$req = "SHOW TABLES";
		$sql = $this->_dbh->query($req);
		$tables = array();
		while ($res = $sql->fetch(\PDO::FETCH_NUM)) {
			$tables[] = $res[0];
		}

		if (! in_array($this->_table, $tables)) {
			die ("La table '" . $this->_table . "' n'existe pas");
		}
	}


	/**
	 * Vérification de l'existence des champs
	 */
	private function checkChamps()
	{
		$req = "SHOW COLUMNS FROM " . $this->_table;
		$sql = $this->_dbh->query($req);

		$champs = array();
		while ($res = $sql->fetch()) {
			$champs[] = $res->Field;
		}

		$champExistants = array();

		foreach ($this->_listChamps as $k=>$v) {
			if (in_array($k, $champs)) {
				$champExistants[$k] = $v;
			}
		}

		return $champExistants;
	}


	/**
	 * Ajout des valeurs dans les champs de formulaire
	 */
	public function domLoadValues()
	{
		$xpath = new \DOMXPath($this->_dom);

		foreach ($this->_listChamps as $k=>$v) {

	    	$query	= '//*[@id="' . $this->_name . '_' . $k . '_id"]';
	    	$entries= $xpath->query($query);
	    	$this->_domElements	= $entries->item(0);

			$this->loadInputHidden($k, $v);			// Chargement des input de type 'hidden'
			$this->loadInputClassic($k, $v);		// Chargement des input de type 'text', 'password', 'email', 'url' ou 'number'
			$this->loadInputRadio($k, $v, $xpath);	// Chargement des input de type 'radio'
			$this->loadInputCheckbox($k, $v);		// Chargement des input de type 'checkbox'
			$this->loadTextarea($k, $v);			// Chargement des 'textarea'
			$this->loadSelect($k, $v);				// Chargement des 'select'
			$this->loadDateTimePicker($k, $v);		// Chargement des 'datetimepicker'
		}
	}


	/**
	 * Chargement des input de type 'hidden'
	 *
	 * @param 	string 	$k 		Clé
	 * @param 	string 	$v		Valeur
	 */
	private function loadInputHidden($k, $v)
	{
		if ($this->_domElements->getAttribute('type') == 'hidden') {
			$this->_domElements->setAttribute('value', $v);
		}
	}


	/**
	 * Chargement des input de type 'text', 'password', 'email', 'url' ou 'number'
	 *
	 * @param 	string 	$k 		Clé
	 * @param 	string 	$v		Valeur
	 */
	private function loadInputClassic($k, $v)
	{
		if ($this->_domElements->nodeName == 'input') {
			$type_classic = array('text', 'password', 'email', 'url', 'number');

			if (in_array($this->_domElements->getAttribute('type'), $type_classic)) {
				$this->_domElements->setAttribute('value', $v);
			}
		}
	}


	/**
	 * Chargement des input de type 'radio'
	 *
	 * @param 	string 	$k 		Clé
	 * @param 	string 	$v		Valeur
	 * @param 	object	$xpath
	 */
	private function loadInputRadio($k, $v, $xpath)
	{
		if ($this->_domElements->getAttribute('type') == 'group-radio') {

			$query = '//input[@name="' . $this->_name . '_' . $k . '"]';
			$entries= $xpath->query($query);

			foreach ($entries as $this->_domElements) {
				if ($this->_domElements->getAttribute('value') == $v) {
					$this->_domElements->parentNode->setAttribute('class', 'btn btn-default active');
					$this->_domElements->setAttribute('checked', 'checked');
				}
			}
		}
	}


	/**
	 * Chargement des input de type 'checkbox'
	 *
	 * @param 	string 	$k 		Clé
	 * @param 	string 	$v		Valeur
	 */
	private function loadInputCheckbox($k, $v)
	{
		if ($this->_domElements->getAttribute('type') == 'checkbox') {
			if ($v == 1) {
				$this->_domElements->setAttribute('checked', 'checked');
			}
		}
	}


	/**
	 * Chargement des 'textarea'
	 *
	 * @param 	string 	$k 		Clé
	 * @param 	string 	$v		Valeur
	 */
	private function loadTextarea($k, $v)
	{
		if ($this->_domElements->nodeName == 'textarea') {
			$text = $this->_dom->createTextNode($v);
			$this->_domElements->appendChild($text);
		}
	}


	/**
	* Chargement des input de type 'select'
	*
	* @param 	string 	$k 		Clé
	* @param 	string 	$v		Valeur
	*/
	private function loadSelect($k, $v)
	{
		if ($this->_domElements->nodeName == 'select') {

			foreach ($this->_domElements->childNodes as $child) {
				if ($child->getAttribute('value') == $v) {
					$child->setAttribute('selected', 'selected');
				}
			}
		}
	}


	/**
	 * Chargement des 'datetimepicker'
	 *
	 * @param 	string 	$k 		Clé
	 * @param 	string 	$v		Valeur
	 */
	private function loadDateTimePicker($k, $v)
	{
		if ($this->_domElements->getAttribute('role') == 'datetimepicker') {
			$this->_domElements->childNodes->item(0)->setAttribute('value', $v);
		}
	}


	/**
	 * Préparation du script JS permettant l'INSERT ou l'UPDATE du formulaire
	 */
	private function postJS()
	{
		$listeinfosBDD		= array();
		$listeNomsChamps 	= array();
		$listeSaveModifier	= array();

		$xpath = new \DOMXPath($this->_dom);

		// Récupération des champs nécessaires aux INSERT et UPDATE
		foreach ($this->_infosBDD as $k=>$v) {

			$name 	= $this->_name . '_' . $v;

			// Récupération du nom de la balise du champs de formulaire
			$query	= '//*[@name="' . $name . '"]';
			$entries= $xpath->query($query);
			$entry	= $entries->item(0);

			$listeinfosBDD[] = $v . ' : $("input[name=' . $name . ']").val()';
		}

		// Récupération de la liste des champs ayant une méthode 'save modifier' activée
		foreach ($this->_listElements as $element) {
			if ($element->getSave() === true) {
				$listeSaveModifier[$element->getChamp()] = str_replace('\\', '\\\\',$element->getClassName());
			}
		}
		$listeSaveModifier = json_encode($listeSaveModifier);

		// Récupération des champs à poster
		foreach ($this->checkChamps() as $k=>$v) {

			$name 	= $this->_name . '_' . $k;

			// Récupération du nom de la balise du champs de formulaire
	    	$query	= '//*[@name="' . $name . '"]';
	    	$entries= $xpath->query($query);
	    	$entry	= $entries->item(0);

	    	// Récupération des champs à poster
	    	if ($entry->nodeName == 'input' && $entry->getAttribute('type') == 'radio') {					// Boutons radio
	    		$listeNomsChamps[] = $k . ' : $("input:radio[name=' . $name . ']:checked").val()';

			} elseif ($entry->nodeName == 'input' && $entry->getAttribute('type') == 'checkbox') {			// Checkboxs
	    		$listeNomsChamps[] = $k . ' : $("input:checkbox[name=' . $name . ']").prop("checked")';

			} elseif ($entry->nodeName == 'textarea' && $entry->getAttribute('type') == 'ckeditor') {		// ckEditor
	    		$listeNomsChamps[] = $k . ' : CKEDITOR.instances.' . $name . '_id.getData()';

			} else {
	    		$listeNomsChamps[] = $k . ' : $("' . $entry->nodeName . '[name=' . $name . ']").val()';		// Tous les autres types de champs
	    	}
		}

		// Merge champs InfosBDD et liste de champs
		$listPost = implode("," . chr(10), array_merge($listeinfosBDD, $listeNomsChamps));

		// Préparation des variables pour la création du JS Ajax
		$idForm			= $this->_name . '_id';
		$urlSortie		= $this->_urlSortie;

		$idActionForm	= $this->_actionForm . '_id';
		$ActionForm		= '';

		// On remplis le champ caché 'actionForm' pour connaitre l'action sur le formulaire (save | saveExit | trash)
		if (! empty($this->_actionForm)) {
			$actionForm = "actionForm : $('#" . $this->_actionForm . "_id').val(),";
		}

		// $js = "$('#actionForm_id').attr('value', '');";
		// \core\libIncluder::add_JsScript($js);

		// Ajout d'une méthode CURL à la sauvegarde d'un élément
		$listCurlInSave = json_encode($this->_curlInSave);
		$listCurlInSave = str_replace('|||', '"', $listCurlInSave);	// astuce permettant de ne pas echapper un double quote si nécessaire (exemple : valChp)

		// Ajout d'une méthode CURL après la sauvegarde
		$listCurlAfterSave = json_encode($this->_curlAfterSave);
		$listCurlAfterSave = str_replace('|||', '"', $listCurlAfterSave);	// astuce permettant de ne pas echapper un double quote si nécessaire (exemple : valChp)

		// Mise en forme des script JS à exécuter après la sauvegarde
		$listJsAfterSave = implode(chr(10), $this->_jsAfterSave);

		// Code JS
		$js = <<<eof
$('#$idForm').validator().on('submit', function (e) {

	var notifyType = '';

	if (e.isDefaultPrevented() && $('#actionForm_id').val() != 'trash') {
		//console.log('Formulaire incomplet');
	} else {

		e.preventDefault();

		if ($('#actionForm_id').val() != 'verrouOn') {

			$.post("/vendor/vw/framework/lib/form/ajax/sendForm.php",
			{
				bddName				: '{$this->_bddName}',
				formName 			: '{$this->_name}',
				currentPage 		: '{$_SERVER['REQUEST_URI']}',
				curlInSave 			: $listCurlInSave,
				curlAfterSave 		: $listCurlAfterSave,
				listeSaveModifier 	: '$listeSaveModifier',
				$actionForm
				$listPost
			},
			function success(data)
			{
				// console.log(data);

				if (data.ok == 1) {

					if (data.actionForm == 'save') {
						notifyType = 'info';
						myNotify(notifyType, data.text);
						if (data.req == 'INSERT') {
							document.location.href = data.redir;
						} else {
							$listJsAfterSave
						}
					} else if (data.actionForm == 'saveExit') {
						notifyType = 'info';
						myNotify(notifyType, data.text);
						setTimeout(function() { document.location.href = '$urlSortie'; }, 1000);
					} else if (data.actionForm == 'trash') {
						notifyType = 'info';
						myNotify(notifyType, data.text);
						setTimeout(function() { document.location.href = '$urlSortie'; }, 1000);
					} else {
						notifyType = 'danger';
						myNotify(notifyType, 'Action inconnue');
					}

				} else {
					notifyType = 'danger';
					myNotify(notifyType, data.text);
				}
			}, 'json');
		}
	}
});
eof;

		\core\libIncluder::add_JsScript($js);
	}


   	/**
   	 * Retourne le DOM généré par le formulaire
   	 */
    public function getDom()
    {
		$checkSave = 0;

		// Récupération de la liste des champs de formulaire
		$this->_listChamps = $this->getListChamps();

		if (! empty($this->_clePrimaireId)) {

			// Récupération des données du formulaire en base
			$this->select();

			// Intégration dans le dom -> remplissage des champs de formulaire
			$this->domLoadValues();
		}

		// Création du JS pour le postactionForm
		$this->postJS();

		if ($this->_verifExist === true) {

			return $this->_dom;

		} else {

			$js = "myNotify('danger', 'L\'id " . $this->_clePrimaireId . " n\'existe pas dans la table \'" . $this->_table . "\'');";
			\core\libIncluder::add_JsScript($js);
			return new \DOMDocument("1.0", "utf-8");

		}
    }
}
