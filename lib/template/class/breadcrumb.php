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

        $li = $this->_dom->createElement('li');

        if (! is_null($link)) {

            $a = $this->_dom->createElement('a');
            $a->setAttribute('href', $link);

            if (strstr($label, '<') === false) {
                $text = $this->_dom->createTextNode($label);
                $a->appendChild($text);
            } else {
                $a->appendChild( $this->addHtml($label) );
            }

            $li->appendChild($a);

        } else {

            $li->setAttribute('class', 'active');

            if (strstr($label, '<') === false) {
                $text = $this->_dom->createTextNode($label);
                $li->appendChild($text);
            } else {
                $li->appendChild( $this->addHtml($label) );
            }
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


    /**
     * Permet de merger du code HTML dans le DOM
     *
     * @param       string      $html           Code à insérer
     * @return      object
     */
    private function addHtml($html)
    {
        $container = $this->_dom->createElement('span');

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
                $container->appendChild($newNode);
            }
        }

        return $container;
    }
}
