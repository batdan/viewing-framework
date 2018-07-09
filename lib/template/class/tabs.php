<?php
namespace tpl;

/**
 * Gestion des onglets / tabs
 * Structure de la présentation
 *
 * @author Daniel Gomes
 */
class tabs
{
	/**
	 * Liste des attributs
	 */
	private $_name;								// Nom de l'ensemble d'onglets
	private $_tabs 			= array();			// Options des onglets
	private $_defaultTab 	= 0;				// Onglet par défaut à l'ouverture de la page
	private $_colWidth		= 'col-xs-12';		// Taille de l'ensemble des onglets dans son conteneur

	private $_dom;								// Gestion en dom du code généré
	private $_container;						// Conteneur de l'élément


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
	public function setDefaultTab($defaultTab) {
		$this->_defaultTab = $defaultTab;
	}
	public function setTabs($tabs) {
		$this->_tabs = $tabs;
	}
	public function setColWidth($colWidth) {
		$this->_colWidth = $colWidth;
	}


	/**
	 * Getters
	 */
	public function getDom() {
		return $this->_dom;
	}


	/**
	 * Retourne le code HTML généré par la classe "tabs"
	 */
	public function getHTML() {
		return $this->_dom->saveHTML();
	}


    /**
     * Création des onglets
     */
    private function init()
    {
    	$this->_dom = new \DOMDocument("1.0", "utf-8");

		$tabsId = $this->_name . '_id';

    	// Container
    	$this->_container = $this->_dom->createElement('div');
    	$this->_container->setAttribute('id', 'tabsContainer_' . $tabsId);

    	// Création des onglets
    	$this->createTabs();

    	// Création des conteneurs appelés par les onglets
    	$this->createDivContent();

    	$this->_dom->appendChild($this->_container);

		// Script JS
		$js = <<<eof
$('#$tabsId').tab();
eof;
		\core\libIncluder::add_JsScript($js);
    }


    /**
     * Création des onglets
     */
    private function createTabs()
    {
    	$ul = $this->_dom->createElement('ul');
    	$ul->setAttribute('name', 		$this->_name);
    	$ul->setAttribute('id', 		$this->_name . '_id');
    	$ul->setAttribute('class',		'nav nav-tabs ' . $this->_colWidth);
    	$ul->setAttribute('data-tabs',	'tabs');

    	for ($i=0; $i<count($this->_tabs); $i++) {
    		$li = $this->_dom->createElement('li');
    		$li->setAttribute('id', $this->_name . '_id' . $i);
    		if ($this->_defaultTab == $i) {
    			$li->setAttribute('class', 	'active');
    		}

    		$a = $this->_dom->createElement('a');
    		$a->setAttribute('href', '#' . $this->_name . '_tab' .$i);
    		$a->setAttribute('data-toggle', 'tab');

    		$text = $this->_dom->createTextNode($this->_tabs[$i]);

    		$a->appendChild($text);
    		$li->appendChild($a);
    		$ul->appendChild($li);
    	}

    	$this->_container->appendChild($ul);
    }


    /**
     * Création des conteneurs appelés par les onglets
     */
    private function createDivContent()
    {
    	$div_content = $this->_dom->createElement('div');
    	$div_content->setAttribute('id', 'content_' . $this->_name . '_id');
    	$div_content->setAttribute('class', 'tab-content ' . $this->_colWidth);
    	$div_content->setAttribute('style', 'padding:25px 0;');

    	for ($i=0; $i<count($this->_tabs); $i++) {
    		$div = $this->_dom->createElement('div');
    		$div->setAttribute('id', $this->_name . '_tab' . $i);
    		if ($this->_defaultTab == $i) {
    			$div->setAttribute('class', 'tab-pane active');
    		} else {
    			$div->setAttribute('class', 'tab-pane');
    		}

    		$div_content->appendChild($div);
    	}

    	$this->_container->appendChild($div_content);
    }


    /**
     * Permet d'insérer du code la fin des éléments du contenu d'un onglet
     */
    public function append($tab, $element)
    {
    	$xpath 	= new \DOMXPath($this->_dom);

    	$tabId	= $this->_name . '_tab' . $tab;
    	$query	= '//div[@id="' . $tabId . '"]';
    	$entries= $xpath->query($query);
    	$div	= $entries->item(0);

    	$nodes	= $element->getDom();

    	foreach ($nodes->childNodes as $child) {
    		$newNode = $this->_dom->importNode($child, true);
    		$div->appendChild($newNode);
    	}
    }
}
