<?php
namespace tpl;

/**
 * Barre de navigation
 *
 * @author Daniel Gomes
 */
class navbar
{
    /**
     * Liste des attributs
     */
    private $_dbh;              // Instance PDO
    private $_dom;				// Gestion en dom du code généré

    private $_tree;             // Récupération des menus de gauche

    private $_idMenu;           // Permet de relier une page à un élémént de menu


    /**
     * Constructeur
     */
    public function __construct($idMenu = null)
    {
        // PDO
        $this->_dbh = \core\dbSingleton::getInstance();

        // Permet de relier une page à un élémént de menu
        $this->_idMenu = $idMenu;

        $this->_tree = $_SESSION['tree'];

        // domDocument
        $this->_dom = new \DOMDocument("1.0", "utf-8");

        $this->init();          // Chargement de barre de menus
        $this->loadNavbar();    // Chargement des menus
        $this->activeUrl();     // Gestion des états "boutons enfoncés"
    }


    /**
     * Initialisation de la barre de navigation
     */
    private function init()
    {
        // Nom du projet
        $project = \core\config::getConfig('project');
        $projectName = $project['name'];

        $navbar = $this->_dom->createElement('nav');
    	$navbar->setAttribute('class', 'navbar navbar-inverse navbar-fixed-top my-navbar');

        $container = $this->_dom->createElement('div');
    	$container->setAttribute('class', 'top-navbar');
        $container->setAttribute('role', 'container-navbar-header');

        $navbarHeader = $this->_dom->createElement('div');
        $navbarHeader->setAttribute('class', 'navbar-header');

        // Marque
        $navbarBrand = $this->_dom->createElement('a');
        $navbarBrand->setAttribute('class', 'navbar-brand brand navbar-level-1');
        $navbarBrand->setAttribute('href', '/modules/dashboard/dashboard.php');

        $iBrand = $this->_dom->createElement('div');
        $iBrand->setAttribute('class', 'fa fa-clone i-brand');

        $textBrand = $this->_dom->createTextNode($projectName);

        $navbarBrand->appendChild($iBrand);
        $navbarBrand->appendChild($textBrand);
        $navbarHeader->appendChild($navbarBrand);
        $container->appendChild($navbarHeader);

        $navbar->appendChild($container);

        // Menu top
        $this->_dom->appendChild($navbar);
    }


    /**
     * Chargement des menus de la navbar
     */
    private function loadNavbar()
    {
        $xpath = new \DOMXPath($this->_dom);

        $query      = '//div[@role="container-navbar-header"]';
        $entries    = $xpath->query($query);
        $container	= $entries->item(0);

        $navbar = $this->_dom->createElement('div');
        $navbar->setAttribute('class', 'collapse navbar-collapse navbar-collapse-1');

        $ul = $this->_dom->createElement('ul');
        $ul->setAttribute('class', 'nav navbar-nav');

        foreach($this->_tree as $k0=>$v0) {

            $li = $this->_dom->createElement('li');
            $li->setAttribute('id', $k0);

            // On vérifie si ce menu a des enfants de niveau 1
            if (is_array($v0['sub']) && count($v0['sub']) > 0) {

                $li->setAttribute('class', 'dropdown');

                $a = $this->_dom->createElement('a');
                $a->setAttribute('href', '#');
                $a->setAttribute('id', 'navbar_lev0_' . $k0);
                $a->setAttribute('class', 'sidebar-level-1 dropdown-toggle');
                $a->setAttribute('style', 'line-height:35px;');
                $a->setAttribute('data-toggle', 'dropdown');
                $a->setAttribute('role', 'button');
                $a->setAttribute('aria-haspopup', 'true');
                $a->setAttribute('aria-expanded', 'false');

                if (! empty($v0['icone'])) {
                    $icone = $this->_dom->createElement('div');
                    $icone->setAttribute('class', $v0['icone'] . ' icon-l0');
                    $a->appendChild($icone);
                }

                $div = $this->_dom->createElement('div');
                $div->setAttribute('style', 'position:relative; top:2px;');

                $text = $this->_dom->createTextNode($v0['label']);

                $caret = $this->_dom->createElement('span');
                $caret->setAttribute('class', 'caret');

                $div->appendChild($text);
                $div->appendChild($caret);

                $a->appendChild($div);

                $ulDropdown = $this->_dom->createElement('ul');
                $ulDropdown->setAttribute('class', 'dropdown-menu');

                $i=0;
                foreach($v0['sub'] as $k1=>$v1) {

                    if ($v1['position'] == 'top') {

                        $liDropdown = $this->_dom->createElement('li');
                        $liDropdown->setAttribute('id', $k1);
                        $liDropdown->setAttribute('id-parent', 'L1_' . $v1['id_parent']);

                        $aDropdown = $this->_dom->createElement('a');
                        $aDropdown->setAttribute('id', 'navbar_lev1_' . $k1);
                        $aDropdown->setAttribute('href', $v1['path']);

                        $sep = '';
                        if (! empty($v1['icone'])) {
                            $iconeL1 = $this->_dom->createElement('div');
                            $iconeL1->setAttribute('class', $v1['icone'] . ' icon-l1');
                            $aDropdown->appendChild($iconeL1);
                            $sep = ' ';
                        }

                        $textDropdown = $this->_dom->createTextNode($sep . $v1['label']);

                        $aDropdown->appendChild($textDropdown);
                        $liDropdown->appendChild($aDropdown);
                        $ulDropdown->appendChild($liDropdown);

                        $i++;
                    }
                }

                if ($i > 0) {
                    $li->appendChild($a);
                    $li->appendChild($ulDropdown);
                    $ul->appendChild($li);

                    $noSub = false;

                } else {
                    $noSub = true;
                }
            } else {
                $noSub = true;
            }

            if ($noSub === true) {
                $a = $this->_dom->createElement('a');
                $a->setAttribute('id', 'navbar_lev0_' . $k0);
                $a->setAttribute('href', $v0['path']);
                $a->setAttribute('class', 'sidebar-level-1');
                $a->setAttribute('style', 'line-height:40px;');

                if (! empty($v0['icone'])) {
                    $icone = $this->_dom->createElement('div');
                    $icone->setAttribute('class', $v0['icone'] . ' icon-l0');
                    $a->appendChild($icone);
                }

                $text = $this->_dom->createTextNode($v0['label']);

                $a->appendChild($text);
                $li->appendChild($a);
                $ul->appendChild($li);
            }
        }

        $navbar->appendChild($ul);
        $container->appendChild($navbar);
    }


    /**
     * Gére l'état actif des éléments de menu de la navbar
     */
    private function activeUrl()
    {
        $tree = new \core\tree($this->_idMenu);
        $treeUrl = $tree->getTreeUrl();

        if ($treeUrl !== false) {

    		$xpath = new \DOMXPath($this->_dom);

            // Etat actif du niveau 0
            $query      = '//a[@id="navbar_lev0_' . $treeUrl[0]['id'] . '"]';
    		$entries    = $xpath->query($query);
            $entry      = $entries->item(0);

            $entry->parentNode->setAttribute('class', 'active');

            // Etat actif du niveau 1
            if (isset($treeUrl[1]) && $treeUrl[1]['position'] == 'top') {

                $query      = '//a[@id="navbar_lev1_' . $treeUrl[1]['id'] . '"]';
        		$entries    = $xpath->query($query);
                $entry      = $entries->item(0);

                $entry->parentNode->setAttribute('class', 'active');
            }
        }
    }


    /**
     * Retourne le code HTML généré par la barre de navigation
     */
    public function getDom()
    {
        return $this->_dom;
    }
}
