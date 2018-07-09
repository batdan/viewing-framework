<?php
namespace tpl;

/**
 * Chargement du fil d'ariane
 *
 * @author Daniel Gomes
 */
class breadcrumb
{
    /**
     * Liste des attributs
     */
    private $_dom;				// Gestion en dom du code généré


    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->_dom = new \DOMDocument("1.0", "utf-8");

        $this->init();
    }


    /**
     * Initialisation du fil d'ariane
     */
    private function init()
    {
        $breadcrumb = $this->_dom->createElement('ol');
    	$breadcrumb->setAttribute('class', 'breadcrumb no-select');

        $this->_dom->appendChild($breadcrumb);
    }


    /**
     * Ajout d'un lien
     */
    public function addLink($label, $link=null)
    {
        $xpath = new \DOMXPath($this->_dom);

        $query      = '//ol[@class="breadcrumb no-select"]';
        $entries    = $xpath->query($query);
        $breadcrumb	= $entries->item(0);

        $li     = $this->_dom->createElement('li');
        $text   = $this->_dom->createTextNode($label);

        if (! is_null($link)) {

            $a = $this->_dom->createElement('a');
            $a->setAttribute('href', $link);

            $a->appendChild($text);
            $li->appendChild($a);

        } else {

            $li->setAttribute('class', 'active');
            $li->appendChild($text);
        }

        $breadcrumb->appendChild($li);
    }


    /**
     * Mise en forme du dernier <li> si par erreur l'url a été ajoutée
     */
    private function checkLastLi()
    {
        // Ajout de la class "active"  au dernier <li>
        $xpath = new \DOMXPath($this->_dom);

        $query  = '//ol[@class="breadcrumb no-select"]';
        $entries= $xpath->query($query);
        $breadcrumb = $entries->item(0);

        $nbLi   = $breadcrumb->childNodes->length;
        $lastLi = $breadcrumb->childNodes->item($nbLi-1);
        $lastLi->setAttribute('class', 'active');

        // Suppression du lien si nécessaire
        $child  = $lastLi->childNodes->item(0);

        if ($child->nodeName == 'a') {
            $text = $this->_dom->createTextNode($child->childNodes->item(0)->nodeValue);

            $lastLi->removeChild($child);
            $lastLi->appendChild($text);
        }
    }


    /**
     * Retourne le breadcrumb généré en DOM
     */
    public function getDom()
    {
        $this->checkLastLi();

        return $this->_dom;
    }
}
