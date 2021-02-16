<?php
namespace tpl;

use core\tree;
use core\dbSingleton;
use core\libIncluder;

/**
 * Barre de menus de gauche
 *
 * @author Daniel Gomes
 */
class sidebar
{
    /**
     * Liste des attributs
     */
    private $_dbh;              // Instance PDO
    private $_dom;				// Gestion en dom du code généré

    private $_leftMenu;         // Récupération des menus de gauche
    private $_treeUrl;          // Récupération de l'arborescence de l'url en cours

    private $_idMenu;           // Permet de relier une page à un élémént de menu


    /**
     * Constructeur
     */
    public function __construct($idMenu=null)
    {
        // PDO
        $this->_dbh = dbSingleton::getInstance();

        // Permet de relier une page à un élémént de menu
        $this->_idMenu = $idMenu;

        // domDocument
        $this->_dom = new \DOMDocument("1.0", "utf-8");

        // Données sur l'arbre des menus de gauche
        $tree = new tree($this->_idMenu);

        // Récupération de l'arborescence de l'url en cours
        $this->_treeUrl = $tree->getTreeUrl();

        // Récupération des menus de gauche
        if (isset($_SESSION['tree']) && !empty($idMenu)) {
            $idTop = $this->_treeUrl[0]['id'];
            $this->_leftMenu = $_SESSION['tree'][$idTop]['sub'];
        } else {
            $this->_leftMenu = $tree->getLeftMenus($idMenu);
        }

        // On déplie les menus liés à la page en cours
        $this->openTree();

        $this->init();
        $this->loadSidebar();

        // Hauteur du menu de gauche
        $js = <<<eof
/*
majHeightLeftSideBar();
setTimeout( function() {
    majHeightLeftSideBar();
}, 800);
*/
eof;
        libIncluder::add_JsScript($js);
    }


    /**
     * Initialisation du menu de gauche
     */
    private function init()
    {
        $sidebar = $this->_dom->createElement('nav');
        $sidebar->setAttribute('class', 'nav-collapse sidebar');
        $sidebar->setAttribute('role',  'navigation');

        $this->_dom->appendChild($sidebar);
    }


    /**
     * Chargement des menus de gauche
     */
    private function loadSidebar()
    {
        $xpath = new \DOMXPath($this->_dom);

        $query      = '//nav[@class="nav-collapse sidebar"]';
        $entries    = $xpath->query($query);

        if ($entries->length > 0) {

            $sidebar	= $entries->item(0);

            // Titre de la sidebar
            $titleSidebar = array();
            if (isset($this->_treeUrl[0])) {
                if (! empty($this->_treeUrl[0]['label'])) {
                    $titleSidebar[] = $this->_treeUrl[0]['label'];
                } else {
                    $titleSidebar[] = $this->_treeUrl[0]['name'];
                }
            }
            if (isset($this->_treeUrl[1]) && $this->_treeUrl[1]['position'] == 'top') {
                if (! empty($this->_treeUrl[1]['label'])) {
                    $titleSidebar[] = $this->_treeUrl[1]['label'];
                } else {
                    $titleSidebar[] = $this->_treeUrl[1]['name'];
                }
            }

            // Bloc de menus du niveau 0
            $ul = $this->_dom->createElement('ul');

            // Affichage du titre de la sidebar
            if (count($titleSidebar) > 0) {
                $titleSidebar = implode(" / ", $titleSidebar);

                $header = $this->_dom->createElement('div');
                $header->setAttribute('class', 'headerSidebar');

                $iconeHeader = $this->_dom->createElement('div');
                $iconeHeader->setAttribute('class', 'icone fa fa-chevron-circle-right');
                $iconeHeader->setAttribute('style', 'font-size:14px;');

                $headerTxt = $this->_dom->createTextNode($titleSidebar);

                $header->appendChild($iconeHeader);
                $header->appendChild($headerTxt);

                $sidebar->appendChild($header);
            }

            if (count($this->_leftMenu) > 0) {

                foreach ($this->_leftMenu as $key => $menu) {

                    $li = $this->_dom->createElement('li');
                    $li->setAttribute('class', 'li0');
                    $li->setAttribute('id', 'li_' . $key);

                    $a = $this->_dom->createElement('a');
                    if (!empty($menu['path'])) {
                        $a->setAttribute('href', $menu['path']);
                    } else {
                        if (isset($menu['sub']) && is_array($menu['sub']) && count($menu['sub'])>0) {
                            $a->setAttribute('href', 'javascript:openOrCloseMenu(\'sidebarFleche_' . $key . '\')');
                        }
                    }
                    $a->setAttribute('class', 'link');

                    // Fond du premier niveau de menu de gauche
                    if (isset($this->_treeUrl[$menu['level']]) && $this->_treeUrl[$menu['level']]['path'] == $menu['path']) {
                        $li->setAttribute('class', 'li0_activ');
                    }

                    // Page en cours
                    if (!empty($menu['path']) && (strstr($_SERVER['REQUEST_URI'], $menu['path']) || $key == $this->_idMenu)) {
                        $a->setAttribute('class', 'link activ');
                        $li->setAttribute('class', 'li0_activ on');
                    }

                    $fleche = $this->_dom->createElement('div');

                    // Flèche de navigation
                    if (is_array($menu['sub'])  &&  count($menu['sub']) > 0) {
                        $fleche->setAttribute('class', 'fleche fa fa-angle-right');
                        $fleche->setAttribute('id', 'sidebarFleche_' . $key);
                        $fleche->setAttribute('role', 'sidebarFleche');
                    } else {
                        $fleche->setAttribute('class', 'fleche_off fa fa-angle-right');
                    }

                    // Icône du menu
                    if (! empty($menu['icone'])) {
                        $icone = $this->_dom->createElement('div');
                        $icone->setAttribute('class', 'icone ' . $menu['icone']);

                        $a->appendChild($icone);
                    }

                    // Label
                    $aTxt = $this->_dom->createTextNode($menu['label']);

                    // Insertion de la fleche et du lien avec le label
                    $li->appendChild($fleche);
                    $a->appendChild($aTxt);
                    $li->appendChild($a);

                    // Séparateur
                    $liSep = $this->_dom->createElement('li');
                    $liSep->setAttribute('class', 'liSep');

                    $ul->appendChild($li);
                    $ul->appendChild($liSep);
                }

                $sidebar->appendChild($ul);

                // Insertion des enfants (s'il y en a)
                foreach ($this->_leftMenu as $key => $menu) {
                    if (is_array($menu['sub'])  &&  count($menu['sub']) > 0) {
                        $this->loadSidebarAux($menu['sub'], $key);
                    }
                }
            }
        }
    }


    /**
     * Fonction auxiliaire récursive permettant de récupérer
     * les sous-menus du menu de gauche
     *
     * @param 	array 	  $subMenu 		Branche de menus à afficher
	 * @param 	integer   $id_parent
	 * @param 	integer   $levelLeft0   Niveau de la première branche du menu de gauche
     */
    private function loadSidebarAux($subMenu, $id_parent)
    {
        $xpath = new \DOMXPath($this->_dom);

        $query      = '//li[@id="li_'.$id_parent.'"]';
        $entries    = $xpath->query($query);
        $liParent   = $entries->item(0);

        // Bloc de menus du niveau 0
        $ul = $this->_dom->createElement('ul');

        foreach ($subMenu as $key => $menu) {

            $li = $this->_dom->createElement('li');
            $li->setAttribute('class', 'li');
            $li->setAttribute('id', 'li_' . $key);
            $li->setAttribute('idParent', 'li_' . $id_parent);
            $li->setAttribute('style', 'display:none;');

            $a = $this->_dom->createElement('a');
            if (!empty($menu['path'])) {
                $a->setAttribute('href', $menu['path']);
            } else {
                if (isset($menu['sub']) && is_array($menu['sub']) && count($menu['sub'])>0) {
                    $a->setAttribute('href', 'javascript:openOrCloseMenu(\'sidebarFleche_' . $key . '\')');
                }
            }
            $a->setAttribute('class', 'link');

            // Page en cours
            if (!empty($menu['path']) && (strstr($_SERVER['REQUEST_URI'], $menu['path']) || $key == $this->_idMenu)) {
                $a->setAttribute('class', 'link activ');
            }

            $fleche = $this->_dom->createElement('div');

            // Flèche de navigation
            if (is_array($menu['sub'])  &&  count($menu['sub']) > 0) {
                $fleche->setAttribute('class', 'fleche fa fa-angle-right');
                $fleche->setAttribute('id', 'sidebarFleche_' . $key);
                $fleche->setAttribute('role', 'sidebarFleche');
            } else {
                $fleche->setAttribute('class', 'fleche_off fa fa-angle-right');
            }

            // Icone du menu
            if (! empty($menu['icone'])) {
                $icone = $this->_dom->createElement('div');
                $icone->setAttribute('class', 'icone ' . $menu['icone']);

                $a->appendChild($icone);
            }

            $aTxt = $this->_dom->createTextNode($menu['label']);

            // Insertion de la fleche et du lien avec le label
            $li->appendChild($fleche);
            $a->appendChild($aTxt);
            $li->appendChild($a);

            $ul->appendChild($li);
        }

        $liParent->appendChild($ul);

        // Insertion des enfants (s'il y en a)
        foreach ($subMenu as $key => $menu) {

            if (is_array($menu['sub'])  &&  count($menu['sub']) > 0) {
                $this->loadSidebarAux($menu['sub'], $key);
            }
        }
    }


    /**
     * On déplie les menus liés à la page en cours
     */
    private function openTree()
    {
        // Code JS
		$js = [];

        if ($this->_treeUrl !== false) {

            $feuilleParent = false;
            foreach ($this->_treeUrl as $level => $menu) {

                if ($menu['position'] == 'left') {

                    // if (empty($menu['path']) || (!empty($menu['path']) && !strstr($_SERVER['REQUEST_URI'], $menu['path']))) {
                        $js[] = "$('#sidebarFleche_" . $menu['id'] . "').attr('class', 'fleche fa fa-angle-down');";
                        $js[] = "$('#sidebarFleche_" . $menu['id'] . "').parent().attr('class', 'li0_activ on');";
                    // }

                    $js[] = "$('#li_" . $menu['id'] . ">ul>li').removeAttr('style');";
                }
            }
        }

        // die;

        // On rend visible tous les éléments du niveau de la sélection

        // Dernier niveau
        $page = end($this->_treeUrl);
        $id_parent = $page['id_parent'];
        $listChilds = $this->searchChields($_SESSION['tree'], $id_parent);
        foreach ($listChilds as $k => $v) {
            if (!empty($v['path']) && !strstr($_SERVER['REQUEST_URI'], $v['path'])) {
                $js[] = "$('#li_" . $k . "').removeAttr('style');";
            }
        }

        // niveau n-1
        if (isset($this->_treeUrl[count($this->_treeUrl) -3])) {
            $page = $this->_treeUrl[count($this->_treeUrl) -2];
            if ($page['level']) {
                $id_parent = $page['id_parent'];
                $listChilds = $this->searchChields($_SESSION['tree'], $id_parent);
                foreach ($listChilds as $k => $v) {
                    if (!empty($v['path']) && !strstr($_SERVER['REQUEST_URI'], $v['path'])) {
                        $js[] = "$('#li_" . $k . "').removeAttr('style');";
                    }
                }
            }
        }

        // niveau n-2
        if (isset($this->_treeUrl[count($this->_treeUrl) -3])) {
            $page = $this->_treeUrl[count($this->_treeUrl) -3];
            if ($page['level']) {
                $id_parent = $page['id_parent'];
                $listChilds = $this->searchChields($_SESSION['tree'], $id_parent);
                foreach ($listChilds as $k => $v) {
                    if (!empty($v['path']) && !strstr($_SERVER['REQUEST_URI'], $v['path'])) {
                        $js[] = "$('#li_" . $k . "').removeAttr('style');";
                    }
                }
            }
        }

        // Si nous sommes sur une page hors menu (feuille), on ne met pas la flèche du menu parent vers le bas
        if (isset($feuilleParent)  &&  $feuilleParent !== false) {

            $deleteKey = false;
            foreach($js as $k => $v) {
                if ($v == "$('#sidebarFleche_" . $this->_treeUrl[$feuilleParent]['id'] . "').attr('class', 'fleche fa fa-angle-down');") {
                    $deleteKey = $k;
                }
            }

            if ($deleteKey !== false) {
                unset($js[$deleteKey]);
            }
        }

        $js = implode(chr(10), $js);

        libIncluder::add_JsScript($js);
    }


    /**
     * Récupération les enfants d'un menu
     */
    private function searchChields($menus, $id_parent)
    {
        $i=0;

        foreach ($menus as $k => $v) {
            if ($k == $id_parent) {
                $i++;
                return $v['sub'];
            }
        }

        if ($i==0) {
            foreach ($menus as $k => $v) {
                if (is_array($v['sub'])) {
                    $res = $this->searchChields($v['sub'], $id_parent);
                    $lastPage = end($res);
                    if ($lastPage['id_parent'] == $id_parent) {
                        break;
                    }
                }
            }
        }

        if (isset($res)) {
            return $res;
        }

        return [];
    }


    /**
     * Retourne le code HTML généré par le menu de gauche
     */
    public function getDom()
    {
        return $this->_dom;
    }
}
