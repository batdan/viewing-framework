<?php
namespace tpl;

/**
 * Gestion des container / division en colonnes
 * Structure de la présentation d'une page web
 *
 * @author Daniel Gomes
 */
class container
{
	/**
	 * Liste des attributs
	 */
	private $_name;					// Nom du container
	private $_container = array();	// Options des sous-ensembles du container

	private $_dom;					// Gestion en dom du code généré

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

		$this->init();
	}


	/**
	 * Setters
	 */
	public function setName($name) {
		$this->_name = $name;
	}
	public function setContainer($container) {
		$this->_container = $container;
	}


	/**
	 * Getters
	 */
	public function getDom() {
		return $this->_dom;
	}


	/**
	 * Retourne le code HTML généré par la classe "container"
	 */
	public function getHTML() {
		return $this->_dom->saveHTML();
	}


	/**
	 * Création des colonnes du container
	 */
	private function init()
	{
		/**
		 * Liste des options par colonne :
		 * 		colWidth 		-> ex : 'col-4'		(facultatif si le nombre de colonne divisible par 12)
		 * 		fieldset 		-> true / false		(par défaut à 'false')
		 * 		fieldsetColor	-> ex : '#ff0000'	(par défaut à '#ccc')
		 * 		legend			-> texte			(par défaut vide et ne fonctionne que si 'fieldset' est à 'true')
		 *
		 * @var unknown
		 */

		// On vérifie si toutes les largeurs de colonnes sont renseignées (règle = tout ou rien)
		$check = 0;
		foreach ($this->_container as $colonne) {
			if (! empty($colonne['colWidth'])) {
				$check++;
			}
		}

		// Toutes les largeurs de colonnes ne sont pas renseignées
		if ($check > 0  &&  $check < count($this->_container)) {
			die ("Attention, soit toutes les largeurs de colonnes 'colWidth' sont renseignées,<br>soit aucune et dans ce cas le nombre de colonnes doit être divisible par 12");
		}

		// Aucune largeur de colonne n'est renseignée, on tente une division à parts égales
		if ($check == 0) {
			if (is_int(count($this->_container))) {
				$colSize 			= 12 / count($this->_container);
				$defaultColWidth	= 'col-lg-' . $colSize;
			} else {
				die ("Le nombre de colonnes doit être divisible par 12,<br>Sinon, il est nécessaire de renseigner l'option 'colWidth' de chaque colonne");
			}
		}

		// Toutes les lageurs de colonnes sont renseignées, on vérifie si elle ne dépasse pas 12
		if ($check == count($this->_container)) {
			$countCell = 0;
			foreach ($this->_container as $colonne) {
				$expCellWidth 	= explode ('-', $colonne['colWidth']);
				$cellSize		= intval(end($expCellWidth));

				$countCell += $cellSize;
			}

			if ($countCell > 12) {
				die ("Attention, la largeurs cumulée des célulles dépasse 12");
			}
		}

		$this->_dom = new \DOMDocument("1.0", "utf-8");

		// Container
		$container = $this->_dom->createElement('div');
		$container->setAttribute('id', 'colsContainer_' . $this->_name . '_id');
		$container->setAttribute('class', 'row');

		// Création des colonnes
		for ($i=0; $i<count($this->_container); $i++) {

			$colonne = $this->_container[$i];

			if (empty($colonne['colWidth'])) {
				$colonne['colWidth'] = $defaultColWidth;
			}

			$div = $this->_dom->createElement('div');
			$div->setAttribute('id', $this->_name . '_col' . $i);
			$div->setAttribute('class', $colonne['colWidth']);

			$divData = $this->_dom->createElement('div');
			$divData->setAttribute('id', $this->_name . '_colData' . $i);
			$divData->setAttribute('class', 'col-lg-12');

			// Intégration d'un fieldset
			if (! empty($colonne['fieldset'])  &&  $colonne['fieldset'] ==  true) {
				$fieldset = $this->_dom->createElement('fieldset');

				if (! empty($colonne['fieldsetColor'])) {
					$fieldsetColor = $colonne['fieldsetColor'];
				} else {
					$fieldsetColor = '#ccc';
				}
				$fieldset->setAttribute('style', 'border:1px solid ' . $fieldsetColor);

				if (! empty($colonne['legend'])) {
					$legend = $this->_dom->createElement('legend');
					$legend->setAttribute('style', 'font-size:14px; width:inherit; padding:0 10px; margin-left:5px; border-bottom:none; position:relative; top:8px;');
					$fieldset->appendChild($legend);

					$legendText = $this->_dom->createTextNode($colonne['legend']);
					$legend->appendChild($legendText);
				}

				$fieldset->appendChild($divData);

				$div->setAttribute('style', 'position:relative; top:-20px;');
				$div->appendChild($fieldset);

			// Intégration sans fieldset
			} else {
				$div->appendChild($divData);
			}

			$container->appendChild($div);
		}

		$this->_dom->appendChild($container);
	}


	/**
	 * Permet d'insérer du code la fin d'un colonne de container
	 */
	public function append($col, $element)
	{
		$xpath 	= new \DOMXPath($this->_dom);

		$colId	= $this->_name . '_colData' . $col;
		$query	= '//div[@id="' . $colId . '"]';
		$entries= $xpath->query($query);
		$div	= $entries->item(0);

		$nodes	= $element->getDom();

		foreach ($nodes->childNodes as $child) {
			$newNode = $this->_dom->importNode($child, true);
			$div->appendChild($newNode);
		}
	}
}
