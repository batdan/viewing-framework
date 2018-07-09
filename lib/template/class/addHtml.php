<?php
namespace tpl;

/**
 * Permet d'ajouter du code HTML dans du DOM
 *
 * @author Daniel Gomes
 */
class addHtml
{
	/**
	 * Liste des attributs
	 */
	private $_dom;				// Gestion en DOM du code généré


    /**
     * Hydratation de la classe et initialisation
     * @param array $data
     */
    public function __construct($html, $colWidth = 'col-lg-12')
    {
    	$this->_dom = new \DOMDocument("1.0", "utf-8");

    	$div = $this->_dom->createElement('div');
    	$div->setAttribute('class', $colWidth);

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

    	$div->appendChild($divRow);
    	$this->_dom->appendChild($div);
    }


    /**
     * Retourne le code HTML généré par la classe "html"
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
}
