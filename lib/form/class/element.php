<?php
namespace form;

/**
 * Gestion des éléments de formulaire
 * Structure de la présentation du formulaire
 *
 * @author Daniel Gomes
 */
class element
{
	/**
	 * Attributs
	 */
	protected $_form;							// Récupération de l'instance du formulaire
	protected $_load = false;					// Permet de savoir si la classe est dérivée et la méthode load surclassée
	protected $_save = false;					// Permet de savoir si la classe est dérivée et la méthode save surclassée

	protected $_type;							// Type de champ de formulaire
	protected $_champ;							// Nom du champ en base
	protected $_label;							// Label du champ
	protected $_options;						// Attributs pouvant être ajoutés au champ
												// et "dataList" -> tableaux assiatifs pour les éléments de type radio, checkbox, select, etc.

	protected $_labelWidth = 'col-sm-3';		// Largeur par défaut du label
	protected $_champWidth = 'col-sm-9';		// Largeur par défaut du champ

	protected $_dom;							// Gestion en dom du code généré
	protected $_container;						// Conteneur de l'élément de formulaire

	// Ce tableau définit les options spécifiques - les autres options seront intégrés dans la balise du champs de formulaire
	protected $_optionsSpe = array(
								'labelWidth', 	// Annule et remplace la taille par défaut du label (labelWidth = 'col-lg-4')
								'champWidth', 	// Annule et remplace la taille par défaut du champ (champWidth = 'col-lg-8')
								'required',		// Champ obligatoire
								'options',		// Peut servir par exemple à générer les options de l'appel javascript lié à un champs de formulaire
								'dataList',		// Tableau associatif contenant les clés / valeurs des boutons radio, select, etc.
								'typeChp',		// Type de champ en base lié au champ de formulaire
								);


	/**
	 * Hydratation de la classe et initialisation
	 * @param array $data
	*/
	public function __construct($form, $type, $champ, $label=null, $options=null)
	{
		$this->setForm($form);
		$this->setType($type);
		$this->setChamp($champ);
		$this->setLabel($label);
		$this->setOptions($options);

		// On enregistre le nom du champs dans l'instance du formulaire
		$type_actions = array('button', 'submit');
		if (! in_array($type, $type_actions)) {
			$this->_form->setListElements($this);
		}

		$this->init();
		$this->getDom();
	}


	/**
	 * Setters
	 */
	public function setForm($form) {
		$this->_form = $form;
	}
	public function setType($type) {
		$this->_type = $type;
	}
	public function setChamp($champ) {
		$this->_champ = $champ;
	}
	public function setLabel($label) {
		$this->_label = $label;
	}
	public function setOptions($options) {
		$this->_options = $options;

		if (! empty($options['labelWidth'])) {
			$this->_labelWidth = $options['labelWidth'];
		}

		if (! empty($options['champWidth'])) {
			$this->_champWidth = $options['champWidth'];
		}
	}


	/**
	 * Getters
	 */
	public function getClassName() {
		return get_class($this);
	}
	public function getDom() {
		return $this->_dom;
	}
	public function getChamp() {
		return $this->_champ;
	}
	public function getLoad() {
		return $this->_load;
	}
	public function getSave() {
		return $this->_save;
	}



	/**
	* Création des éléments de formulaire
	*/
	private function init()
	{
		$this->_dom = new \DOMDocument("1.0", "utf-8");

		if ($this->_type == 'hidden') {

			$this->chpInputHidden();		// Champs input de type 'hidden'

		} else {

			$this->_container = $this->_dom->createElement('div');
	    	$this->_container->setAttribute('id', 'div_' . $this->_form->getName() . '_' . $this->_champ . '_id');
	    	$this->_container->setAttribute('class', 'form-group');

			$this->label();					// Affichage des label
			$this->chpInputClassic();		// Champs input de type 'text' ou 'password'
			$this->chpInputCheckbox();		// Champs input de type 'Checkbox'
			$this->chpInputButton();		// Champs input de type 'radio' ou 'checkbox-multiple'
			$this->chpInputAction();		// Champs input de type 'button' ou 'submit'
			$this->chpTextarea();			// Champs 'textarea'
			$this->chpCkEditor();			// Champs 'ckEditor' (editeur de texte wysiywyg)
			$this->chpSelect();				// Champs 'select'
			$this->chpDateTimePicker();		// Champs 'datetimepicker'

			$this->_dom->appendChild($this->_container);
		}
	}


	/**
	 * Gestion de l'affichage de la balise 'label'
	 */
	private function label()
	{
		if (! is_null($this->_label)) {
			$label = $this->_dom->createElement('label');
			$label->setAttribute('for', $this->_form->getName() . '_' . $this->_champ . '_id');
			$label->setAttribute('class', $this->_labelWidth . ' control-label');
			$label->setAttribute('style', 'text-align:left;');

			//$text = $this->_dom->createTextNode($this->_label);
			//$label->appendChild($text);

			$label->appendChild( $this->addHtml($this->_label) );

			$this->_container->appendChild($label);
		}
	}


	/**
	 * Affichage des champs input de type 'hidden'
	 */
	private function chpInputHidden()
	{
		if ($this->_type == 'hidden') {
			$input = $this->_dom->createElement('input');
			$input->setAttribute('name', 	$this->_form->getName() . '_' . $this->_champ);
			$input->setAttribute('id', 		$this->_form->getName() . '_' . $this->_champ . '_id');
			$input->setAttribute('type', 	$this->_type);

			// Chargement des attributs
			if (isset($this->_options['value'])) {
				$input->setAttribute('value', $this->_options['value']);
			}

			$this->_dom->appendChild($input);
		}
	}


	/**
	 * Affichage des champs input de type 'text', 'password', 'email', 'url' ou 'number'
	 */
	private function chpInputClassic()
	{
		$type_classic = array('text', 'password', 'email', 'url', 'number');
		if (in_array($this->_type, $type_classic)) {

			$id = $this->_form->getName() . '_' . $this->_champ . '_id';

			// Conteneur de champ
			$div = $this->_dom->createElement('div');
			$div->setAttribute('class', $this->_champWidth);

			$input = $this->_dom->createElement('input');
			$input->setAttribute('name', 	$this->_form->getName() . '_' . $this->_champ);
			$input->setAttribute('id', 		$id);
			$input->setAttribute('type', 	$this->_type);
			$input->setAttribute('class', 	$this->_champWidth . ' form-control has-feedback');

			// Chargement des attributs
			if (! is_null($this->_options)) {
				foreach ($this->_options as $k=>$v) {
					if (! in_array($k, $this->_optionsSpe)) {
						$input->setAttribute($k, $v);
					}
				}
			}

			// Champ opbligatoire
			if (isset($this->_options['required']) && $this->_options['required'] === true) {

				$input->setAttribute('required', true);
			}

			$div->appendChild($input);

			// Message d'erreur
			if (isset($this->_options['required']) && $this->_options['required'] === true) {
				$error = $this->_dom->createElement('div');
				$error->setAttribute('class', 'help-block with-errors');
				$div->appendChild($error);
			}

			$this->_container->appendChild($div);
		}
	}


	/**
	 * Affichage des champs input de type 'checkbox'
	 */
	private function chpInputCheckbox()
	{
		if ($this->_type == 'checkbox') {

			// Conteneur de champ
			$div = $this->_dom->createElement('div');
			$div->setAttribute('class', $this->_champWidth);

			$input = $this->_dom->createElement('input');
			$input->setAttribute('name', 	$this->_form->getName() . '_' . $this->_champ);
			$input->setAttribute('id', 		$this->_form->getName() . '_' . $this->_champ . '_id');
			$input->setAttribute('type', 	$this->_type);
			$input->setAttribute('value', 	1);

			// Chargement des attributs
			if (! is_null($this->_options)) {
				foreach ($this->_options as $k=>$v) {
					if (! in_array($k, $this->_optionsSpe)) {
						$input->setAttribute($k, $v);
					}
				}
			}

			// Champ opbligatoire
			if (isset($this->_options['required']) && $this->_options['required'] === true) {
				$input->setAttribute('required', true);
			}

			$div->appendChild($input);

			// Message d'erreur
			if (isset($this->_options['required']) && $this->_options['required'] === true) {
				$error = $this->_dom->createElement('div');
				$error->setAttribute('class', 'help-block with-errors');
				$div->appendChild($error);
			}

			$this->_container->appendChild($div);
		}
	}


	/**
	 * Champs input de type 'radio'
	 */
	private function chpInputButton()
	{
		$type_button = array('radio');
		if (in_array($this->_type, $type_button)) {

			// Conteneur de champ
			$div = $this->_dom->createElement('div');
			$div->setAttribute('class', $this->_champWidth);

			$radioContainer = $this->_dom->createElement('div');
			$radioContainer->setAttribute('class', 'btn-group');
			$radioContainer->setAttribute('data-toggle', 'buttons');
			$radioContainer->setAttribute('id' , $this->_form->getName() . '_' . $this->_champ . '_id');
			$radioContainer->setAttribute('type', 'group-' . $this->_type);

			if (empty($this->_options['dataList'])) {
				die ("Le champ de type '" . $this->_type . "' n'a pas d'attribut : options['dataList']");
			} else {
				$i=0;
				foreach ($this->_options['dataList'] as $k=>$v) {

					$label = $this->_dom->createElement('label');
					$label->setAttribute('class', 'btn btn-default');

					$input = $this->_dom->createElement('input');
					$input->setAttribute('type', $this->_type);
					$input->setAttribute('name', $this->_form->getName() . '_' . $this->_champ);
					$input->setAttribute('id', $this->_form->getName() . '_' . $this->_champ . '_id_' . $i);
					$input->setAttribute('value', $k);

					// Champ opbligatoire
					if (isset($this->_options['required']) && $this->_options['required'] === true) {
						$input->setAttribute('required', true);
					}

					$text = $this->_dom->createTextNode( ' ' . $v );

					$label->appendChild($input);
					$label->appendChild($text);
					$radioContainer->appendChild($label);

					$i++;
				}
			}

			$div->appendChild($radioContainer);

			// Message d'erreur
			if (isset($this->_options['required']) && $this->_options['required'] === true) {
				$error = $this->_dom->createElement('div');
				$error->setAttribute('class', 'help-block with-errors');
				$div->appendChild($error);
			}

			$this->_container->appendChild($div);
		}
	}


	/**
	 * Champs input de type 'button' ou 'submit'
	 */
	private function chpInputAction()
	{
		$type_classic = array('button', 'submit');
		if (in_array($this->_type, $type_classic)) {

			// Conteneur de champ
			$div = $this->_dom->createElement('div');
			$div->setAttribute('class', $this->_champWidth);

			$button = $this->_dom->createElement('button');
			$button->setAttribute('type', $this->_type);
			$button->setAttribute('class', 'btn btn-primary btn-sm pull-right');

			// Chargement des attributs
			if (! is_null($this->_options)) {
				foreach ($this->_options as $k=>$v) {
					if (! in_array($k, $this->_optionsSpe) && ($k != 'value')) {
						$button->setAttribute($k, $v);
					}
				}
			}

			// Texte du bouton
			if (empty ($this->_options['value'])) {
				$this->_options['value'] = ucfirst($this->_type);
			}

			$text = $this->_dom->createTextNode($this->_options['value']);
			$button->appendChild($text);

			$div->appendChild($button);
			$this->_container->appendChild($div);
		}
	}


	/**
	 * Affichage des champs 'textarea'
	 */
	private function chpTextarea()
	{
		if ($this->_type == 'textarea') {

			// Conteneur de champ
			$div = $this->_dom->createElement('div');
			$div->setAttribute('class', $this->_champWidth);

			$textarea = $this->_dom->createElement('textarea');
			$textarea->setAttribute('name', 	$this->_form->getName() . '_' . $this->_champ);
			$textarea->setAttribute('id', 		$this->_form->getName() . '_' . $this->_champ . '_id');
			$textarea->setAttribute('class', 	$this->_champWidth . ' form-control');
			$textarea->setAttribute('rows', '3');

			// Chargement des attributs
			if (! is_null($this->_options)) {
				foreach ($this->_options as $k=>$v) {
					if (! in_array($k, $this->_optionsSpe)) {
						$textarea->setAttribute($k, $v);
					}
				}
			}

			// Champ opbligatoire
			if (isset($this->_options['required']) && $this->_options['required'] === true) {
				$textarea->setAttribute('required', true);
			}

			$div->appendChild($textarea);

			// Message d'erreur
			if (isset($this->_options['required']) && $this->_options['required'] === true) {
				$error = $this->_dom->createElement('div');
				$error->setAttribute('class', 'help-block with-errors');
				$div->appendChild($error);
			}

			$this->_container->appendChild($div);
		}
	}


	/**
	 * Affichage des champs 'ckEditor' (éditeur de texte wysiywyg)
	 */
	private function chpCkEditor()
	{
		if ($this->_type == 'ckeditor') {

			$nameChamp	= $this->_form->getName() . '_' . $this->_champ;
			$idChamp 	= $nameChamp . '_id';

			// Conteneur de champ
			$div = $this->_dom->createElement('div');
			$div->setAttribute('class', $this->_champWidth);

			$textarea = $this->_dom->createElement('textarea');
			$textarea->setAttribute('type', 	'ckeditor');
			$textarea->setAttribute('name', 	$nameChamp);
			$textarea->setAttribute('id', 		$idChamp);
			$textarea->setAttribute('class', 	$this->_champWidth . ' form-control');
			$textarea->setAttribute('row', '3');

			// Chargement des attributs
			if (! is_null($this->_options)) {
				foreach ($this->_options as $k=>$v) {
					if (! in_array($k, $this->_optionsSpe)) {
						$textarea->setAttribute($k, $v);
					}
				}
			}

			$ckHeight = '';
			if (isset($this->_options['height']) && $this->_options['height']) {
				$ckHeight = "height : '" . $this->_options['height'] . "px',";
			}

			// Champ opbligatoire
			if (isset($this->_options['required']) && $this->_options['required'] === true) {
				$textarea->setAttribute('required', true);
			}

			$div->appendChild($textarea);

			// Message d'erreur
			if (isset($this->_options['required']) && $this->_options['required'] === true) {
				$error = $this->_dom->createElement('div');
				$error->setAttribute('class', 'help-block with-errors');
				$div->appendChild($error);
			}

			$this->_container->appendChild($div);

			// Chargement de la librairie JS
			\core\libIncluderList::add_ckEditor();

			// Script JS
			$js = <<<eof
CKEDITOR.replace('$idChamp', {
							$ckHeight
});
eof;
			\core\libIncluder::add_JsScript($js);
		}
	}


	/**
	 * Affichage des champs 'select'
	 * Pour utiliser selectPicker -> options=array('typeChp'=>'selectpicker')
	 * 		Attention : l'utilisation du selectPicker ne permet pas de rendre le champ obligatoire
	 */
	private function chpSelect()
	{
		if ($this->_type == 'select') {

			// id champ
			$id = $this->_form->getName() . '_' . $this->_champ . '_id';

			// Conteneur de champ
			$div = $this->_dom->createElement('div');
			$div->setAttribute('class', $this->_champWidth);

			$select = $this->_dom->createElement('select');
			$select->setAttribute('name', 	$this->_form->getName() . '_' . $this->_champ);
			$select->setAttribute('id', 	$id);

			// Activation ou non du selectpicker
			if (isset($this->_options['typeChp']) && $this->_options['typeChp'] == 'selectpicker') {
				$select->setAttribute('class',		'selectpicker form-control');
				$select->setAttribute('data-width',	'100%');

				// Chargement de la librairie JS
				\core\libIncluderList::add_bootstrapSelect();
			} else {
				$select->setAttribute('class',	'form-control');
			}

			// Champ opbligatoire
			if (isset($this->_options['required']) && $this->_options['required'] === true) {
				$select->setAttribute('required', true);
			}

			if (empty($this->_options['dataList'])) {
				die ("Le champ de type '" . $this->_type . "' n'a pas d'attribut : options['dataList']");
			} else {
				foreach ($this->_options['dataList'] as $k=>$v) {

					$option = $this->_dom->createElement('option');
					$option->setAttribute('value', $k);

					$text = $this->_dom->createTextNode($v);

					$option->appendChild($text);
					$select->appendChild($option);
				}
			}

			$div->appendChild($select);

			// Message d'erreur
			if (isset($this->_options['required']) && $this->_options['required'] === true) {
				$error = $this->_dom->createElement('div');
				$error->setAttribute('class', 'help-block with-errors');
				$div->appendChild($error);
			}

			$this->_container->appendChild($div);
		}
	}


	/**
	 * Affichage des champs 'datetimepicker'
	 * typeChp => 'datetime' || 'date' || 'time'
	 */
	private function chpDateTimePicker()
	{
		if ($this->_type == 'datetimepicker') {

			if (! empty($this->_options['typeChp'])) {

				$optionJs = array();
				switch ($this->_options['typeChp']) {
					case 'datetime' :
						$optionJs['format'] = 'YYYY-MM-DD HH:mm';
						$icone = 'glyphicon glyphicon-calendar';
						break;
					case 'date' :
						$optionJs['format'] = 'YYYY-MM-DD';
						$icone = 'glyphicon glyphicon-calendar';
						break;
					case 'time' :
						$optionJs['format'] = 'LT';
						$icone = 'glyphicon glyphicon-time';
						break;
				}
				$optionJs = json_encode($optionJs);
			}

			// id champ
			$id 	= $this->_form->getName() . '_' . $this->_champ . '_id';

			// Conteneur de champ
			$div = $this->_dom->createElement('div');
			$div->setAttribute('class', $this->_champWidth);

			$datetimepicker = $this->_dom->createElement('div');
			$datetimepicker->setAttribute('class', 'input-group date');
			$datetimepicker->setAttribute('id', $id);
			$datetimepicker->setAttribute('role', 'datetimepicker');

			$input = $this->_dom->createElement('input');
			$input->setAttribute('type', 'text');
			$input->setAttribute('name', $this->_form->getName() . '_' . $this->_champ);
			$input->setAttribute('class', 'form-control has-feedback');

			// Champ opbligatoire
			if (isset($this->_options['required']) && $this->_options['required'] === true) {
				$input->setAttribute('required', true);
			}

			$datetimepicker->appendChild($input);

			$span = $this->_dom->createElement('span');
			$span->setAttribute('class', 'input-group-addon');

			$span2 = $this->_dom->createElement('span');
			$span2->setAttribute('class', $icone);

			$span->appendChild($span2);
			$datetimepicker->appendChild($span);

			$div->appendChild($datetimepicker);

			// Message d'erreur
			if (isset($this->_options['required']) && $this->_options['required'] === true) {
				$error = $this->_dom->createElement('div');
				$error->setAttribute('class', 'help-block with-errors');
				$div->appendChild($error);
			}

			$this->_container->appendChild($div);

			// Chargement de la librairie JS
			\core\libIncluderList::add_bootstrapDatetimepicker();

			// Script JS
			$js = <<<eof
$('#$id').datetimepicker(
	$optionJs
);
eof;
			\core\libIncluder::add_JsScript($js);
		}
	}


	/**
	 * Permet de merger du code HTML dans le DOM
	 *
	 * @param       string      $html           Code à insérer
	 * @param       string      $colWidth       Taille du container
	 * @return      object
	 */
	private function addHtml($html, $colWidth='col-lg-12')
	{
		$container = $this->_dom->createElement('div');
		$container->setAttribute('class', $colWidth);

		$divRow = $this->_dom->createElement('div');
		$divRow->setAttribute('class', 'row');

		// Récupération du code à insérer
		$newDom = new \DOMDocument("1.0", "utf-8");
		$newDom->loadHTML('<?xml encoding="UTF-8">' . $html);

		$xpath = new \DOMXPath($newDom);
		$query		= '//body';
		$entries	= $xpath->query($query);
		$body		= $entries->item(0);

		if ($entries->length > 0) {
			foreach ($body->childNodes as $child) {
				$newNode = $this->_dom->importNode($child, true);
				$divRow->appendChild($newNode);
			}
		}

		$container->appendChild($divRow);

		return $container;
	}


	/**
	 * Permet de modifier la valeur de n'importe quel champ du formulaire avant son chargement
	 * Ne fonctionne que dans le cas du chargement d'une entrée existente (update)
	 * l'atribut $_load doit être à true
	 */
	public function load($data)
	{
		return $data;
	}


	/**
	 * Permet de modifier la valeur de n'importe quel champ du formulaire avant sa sauvegarde
	 * Ne fonctionne que dans le cas du chargement d'une entrée existente (update)
	 * l'atribut $_save doit être à true
	 */
	public function save($data)
	{
		return $data;
	}
}
