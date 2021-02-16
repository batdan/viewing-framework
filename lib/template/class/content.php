<?php
namespace tpl;

/**
 * Contenu de la page
 *
 * @author Daniel Gomes
 */
class content
{
    /**
     * Liste des attributs
     */
    private $_dom;				// Gestion en dom du code généré
    private $_idMenu;           // Permet de relier une page à un élémént de menu


    /**
     * Constructeur
     *
     * @param   integer         $idMenu             Permet de relier une page à un élémént de menu
     * @param   boolean         $emptyContent       Permet de démarrer avec un content vierge
     */
    public function __construct($idMenu=null, $emptyContent=false)
    {
        // Permet de relier une page à un élémént de menu
        $this->_idMenu = $idMenu;

        // domDocument
        $this->_dom = new \DOMDocument("1.0", "utf-8");

        // Chargemement des scripts css et js génériques du backoffice
        if ($emptyContent === false) {
            \core\libIncluderList::add_vwDefault();
        }

        $this->init($emptyContent);
    }


    /**
     * Initialisation du conteneur
     */
    private function init($emptyContent)
    {
        // Chargement du menu et de la main
        $container = $this->_dom->createElement('div');
        $container->setAttribute('class', 'container-fluid fill left-main');
        $container->setAttribute('style', 'display:table; width:100%; visibility:hidden;');

        if ($emptyContent === false) {

            // On importe la zone de notification
            $notify = new \core\notify();
            $notify	= $notify->getDom();

            foreach ($notify->childNodes as $child) {
                $newNode = $this->_dom->importNode($child, true);
                $this->_dom->appendChild($newNode);
            }

            // On importe les widgets
            $widget = new \widget\widget();
            $widget	= $widget->getDom();

            foreach ($widget->childNodes as $child) {
                $newNode = $this->_dom->importNode($child, true);
                $this->_dom->appendChild($newNode);
            }

            // On importe la barre de navigation (header navbar)
            $navbar = new navbar($this->_idMenu);
            $navbar	= $navbar->getDom();

            foreach ($navbar->childNodes as $child) {
                $newNode = $this->_dom->importNode($child, true);
                $this->_dom->appendChild($newNode);
            }

            // Menu de gauche
            $sidebar = $this->_dom->createElement('div');
        	$sidebar->setAttribute('class', 'fill sidebar-left');
            $sidebar->setAttribute('style', 'display:table-cell;');

            // On importe le menu de gauche
            $domSidebar = new sidebar($this->_idMenu);
            $domSidebar = $domSidebar->getDom();

            foreach ($domSidebar->childNodes as $child) {
                $newNode = $this->_dom->importNode($child, true);
                $sidebar->appendChild($newNode);
            }

            $container->appendChild($sidebar);
        }

        // Main
        $content = $this->_dom->createElement('div');
        $content->setAttribute('id',    'main');
        $content->setAttribute('class', 'content fill');
        $content->setAttribute('role',  'first-content');
        $content->setAttribute('style', 'display:table-cell; padding-left:15px; padding-right:15px;');

        $container->appendChild($content);

        $this->_dom->appendChild($container);
    }


    /**
     * Permet d'insérer du code dans la page
     */
    public function append($object)
    {
        $xpath = new \DOMXPath($this->_dom);

        $query	= '//div[@role="first-content"]';
        $entries= $xpath->query($query);
        $form	= $entries->item(0);

        if ($object instanceof \DOMDocument) {
            $nodes = $object;
        } else {
            $nodes = $object->getDom();
        }

        foreach ($nodes->childNodes as $child) {
            $newNode = $this->_dom->importNode($child, true);
            $form->appendChild($newNode);
        }
    }


    /**
     * Retourne le code HTML généré par la page
     */
    public function rendu()
    {
        // Attention créé des problèmes d'espaces non souhaité
        //$this->_dom->formatOutput = true;

        return $this->_dom->saveHTML();
    }
}
