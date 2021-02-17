<?php
namespace core;

/**
 * Gestion des menus et des droits associés
 *
 * @author Daniel Gomes
 */
class tree
{
    /**
     * Attributs
     */
    private $_dbh;                      // Instance PDO

    private $_tree;                     // Arborescence complète
    private $_treeUrl = array();        // Arborescence directe d'une url (tous ses parents)

    private $_idMenu;                   // Permet de relier une page à un élémént de menu


    /**
     * Constructeur
     */
    public function __construct($idMenu = null)
    {
        // Instance PDO
		$this->_dbh = \core\dbSingleton::getInstance();

        // Permet de relier une page à un élémént de menu
        $this->_idMenu = $idMenu;
    }


    /**
     * Méthode récursive
     * Récupération de l'arbre complet des menus autorisés pour un utilisateur
     *
     * @param 	integer 	$id_parent
	 * @param 	integer 	$level
     * @return  array
     */
    public function getTree($id_parent=null, $level=0)
    {
        $this->_dbh->beginTransaction();

        $tree = array();
        $tree = $this->getTreeAux( $tree, $id_parent, $level );

        $this->_dbh->commit();

        return $tree;
    }


    /**
     * Fonction auxilière à getTree()
     *
     * @param 	array 	     $tree
     * @param 	integer      $id_parent
	 * @param 	integer 	 $level
     * @return  array
     */
    public function getTreeAux( &$tree, $id_parent=null, $level=0 )
    {
        $result = false;

        $addReq  = '';
        $dataReq = array(':level' => $level);

        if (! is_null($id_parent)) {
            $addReq = " AND id_parent = :id_parent";
            $dataReq[':id_parent'] = $id_parent;
        }

        $req = "SELECT      id, id_parent, name, label, top, path, target, id_webservice, icone, heritage, activ

                FROM        tree

                WHERE       level = :level $addReq
                AND         activ = 1

                ORDER BY    ordre ASC";

        $sql = $this->_dbh->prepare($req);
        $sql->execute($dataReq);

        $reqTest = str_replace (':id_parent', $id_parent, $req);
        $reqTest = str_replace (':level', $level, $reqTest);

        if ($sql->rowCount() > 0) {

            $result = array();

            while ($res = $sql->fetch()) {

                // Vérification des droits d'accès à cette page
                $authorized = $this->checkPageRight($res->id, $res->heritage, $id_user=null);
                if ($authorized === false) {
                    continue;
                }

                // Vérification de la présence d'un webservice pour les sous-menus
                $sub = false;
                if ($res->id_webservice && !empty($res->id_webservice)) {

                    $req_ws = "SELECT lien_interne, url FROM tree_ws WHERE id = :id";
                    $sql_ws = $this->_dbh->prepare($req_ws);
                    $sql_ws->execute( array( ':id'=>$res->id_webservice ));

                    if ($sql_ws->rowCount() > 0) {

                        $res_ws  = $sql_ws->fetch();

                        if ($res_ws->lien_interne == 1) {

                            $sub = projectMenus::getMenus($res->id);

                        } else {

                            $url = $res_ws->url . '?id_project=' . $res->id;

                            $json_ws = file( $res_ws->url . '?id_project=' . $res->id );
                            $json_ws = $json_ws[0];

                            $sub = json_decode($json_ws, true);
                        }
                    }
                }

                if ($sub === false) {
                    $sub = $this->getTreeAux($tree, intval($res->id), intval($level + 1));
                }

                $result[$res->id] = array(
                                        'id_parent' => $id_parent,
                                        'level'     => $level,
                                        'name'      => $res->name,
                                        'label'     => $res->label,
                                        'position'  => $this->position($res->top, $level),
                                        'path'      => $res->path,
                                        'target'    => $res->target,
                                        'icone'     => $res->icone,
                                        'heritage'  => $res->heritage,
                                        'activ'     => $res->activ,
                                        'id_ws'     => $res->id_webservice,
                                        'sub'       => $sub,
                );
            }
        }

        return $result;
    }


    /**
     * Vérifie si un menu s'affiche en haut ou dans la barre de gauche
     *
     * @param 	integer 	$top
	 * @param 	integer 	$level
     * @return  string
     */
    private function position($top, $level)
    {
        if ($level == 0) {
            return 'top';
        } elseif ($level > 1) {
            return 'left';
        } else {
            if ($top == 1) {
                return 'top';
            } else {
                return 'left';
            }
        }
    }


    /**
     * Méthode récursive - récupère l'arborescence complète d'une Url
     *
     * @return  array
     */
    public function getTreeUrl()
    {
        // Récupération des informations sur le menu sélectionné
        if (isset($_SESSION['tree'])) {
            $tree = $_SESSION['tree'];
        } else {
            $tree = $this->getTree();
        }

        // Récupération de l'élément de menu sélectionné
        $treeUrl = [];

        if (!is_null($this->_idMenu)) {
            $this->getTreeUrl_aux($tree, $treeUrl, $this->_idMenu);
        } else {
            $this->getTreeUrl_aux($tree, $treeUrl);
        }

        // Premier élément trouvé
        if (count($treeUrl) > 0) {

            // On remote tous les menus jusqu'au niveau 0
            while ($treeUrl[ count($treeUrl) - 1 ]['level'] > 0) {
                $id_parent = $treeUrl[ count($treeUrl) - 1 ]['id_parent'];
                $this->getTreeUrl_aux($tree, $treeUrl, $id_parent);
            }

            // Inversion du tableau
            $nbMenus = count($treeUrl) - 1;

            $result = array();
            $j=0;
            for ($i=$nbMenus; $i>=0; $i--) {
                $result[$j] = $treeUrl[$i];
                $j++;
            }

            return $result;
        }
    }


    /**
     * Fonction auxiliaire récursive de la méthode getTreeUrl()
     */
    private function getTreeUrl_aux($tree, &$treeUrl, $id=null)
    {
        foreach ($tree as $k=>$v) {

            // Si un id de menu est passé, on fait une recherche par ID
            if (! is_null($id)) {

                if ($k == $id) {

                    $treeUrl[] = array(
                                        'id'        => $k,
                                        'id_parent' => $tree[$k]['id_parent'],
                                        'name'      => $tree[$k]['name'],
                                        'label'     => $tree[$k]['label'],
                                        'path'      => $tree[$k]['path'],
                                        'position'  => $tree[$k]['position'],
                                        'level'     => $tree[$k]['level'],
                    );
                }

                if (is_array($v['sub']) && count($v['sub']) > 0) {
                    $this->getTreeUrl_aux($v['sub'], $treeUrl, $id);
                }

            // Sinon, on recherche à l'aide du path
            } else {

                // Infos de la page courante
                $path1 = explode('?', $_SERVER['REQUEST_URI']);
                $path1 = $path1[0];

                $path2 = $_SERVER['REQUEST_URI'];

                if ($path1 == $v['path'] || $path2 == $v['path']) {
                    $treeUrl[] = array(
                                        'id'        => $k,
                                        'id_parent' => $tree[$k]['id_parent'],
                                        'name'      => $tree[$k]['name'],
                                        'label'     => $tree[$k]['label'],
                                        'path'      => $tree[$k]['path'],
                                        'position'  => $tree[$k]['position'],
                                        'level'     => $tree[$k]['level'],
                    );
                }

                if (is_array($v['sub']) && count($v['sub']) > 0) {
                    $this->getTreeUrl_aux($v['sub'], $treeUrl);
                }
            }
        }
    }


    /**
     * Récupération de l'id du premier parent de la navbar (top)
     *
     * @return  integer
     */
    private function topParent()
    {
        $treeUrl = $this->getTreeUrl();

        if ($treeUrl !== false) {

            if (isset($treeUrl[1]) && $treeUrl[1]['position'] == 'top') {
                $level = 1;
                $topParent = $treeUrl[1];
            } else {
                $level = 0;
                $topParent = $treeUrl[0];
            }

            $topParent['level'] = $level;

            return $topParent;

        } else {

            return false;
        }
    }


    /**
     * Récupération du menu de gauche en fonction de la page encours
     *
     * @return  integer
     */
    public function getLeftMenus($idMenu=null)
    {
        $topParent = $this->topParent();

        if ($topParent !== false) {
            return $this->getTree(
                $topParent['id'],
                $topParent['level'] + 1
            );
        } else {
            return [];
        }
    }


    /**
     * Méthode vérifiant l'accès à une page
     *
     * @param 	integer     $id_tree
     * @param 	integer     $heritage
     * @param 	integer     $id_user
     * @return  boolean
     */
    private function checkPageRight($id_tree, $heritage, $id_user)
    {
        if ($id_user === null) {
            $id_user = $_SESSION['auth']['id'];
        }

        // Vérification de l'héritage (attention un héritage activé prend le pas sur les autres droits alloués)
        // On remonte jusqu'au parent précisant les droits de la branche
        if ($heritage == 1) {

            $id_parentHeritage = $this->checkParentRight($id_tree);
            $access = $this->checkRightReq($id_parentHeritage, $id_user);

        } else {

            $access = $this->checkRightReq($id_tree, $id_user);
        }

        return $access;
    }


    /**
     * Méthode récursive permettant de retrouver le premier parent
     * ou les droits sont affecté par groups/users et non par héritage
     *
     * @param 	integer     $id_tree
     * @return 	integer
     */
    private function checkParentRight($id_tree)
    {
        $req = "SELECT      id, heritage
                FROM        tree
                WHERE       id IN (SELECT id_parent FROM tree WHERE id=:id_tree)";

        $sql = $this->_dbh->prepare($req);
        $sql->execute( array( ':id_tree'=>$id_tree ));

        if ($sql->rowCount() > 0) {

            $res = $sql->fetch();

            if ($res->heritage == 1) {
                $id_parent = $this->checkParentRight($res->id);
            } else {
                $id_parent = $res->id;
            }
        }

        return $id_parent;
    }


    /**
     * Vérifie l'accès ou non d'une page pour l'utilisateur
     *
     * @param 	integer     $id_tree
     * @param 	integer     $id_user
     * @return 	boolean
     */
    private function checkRightReq($id_tree, $id_user)
    {
        $checkAcces = false;

        // Utilisateurs autorisés à accéder à cette page
        $req = "SELECT id_user FROM tree_users_link WHERE id_tree=:id_tree AND id_user <> 0";
        $sql = $this->_dbh->prepare($req);
        $sql->execute( array( ':id_tree'=>$id_tree ));

        if ($sql->rowCount() > 0) {
            while ($res = $sql->fetch()) {
                if ($res->id_user == $id_user) {
                    $checkAcces = true;
                    break;
                }
            }
        }

        // Groupe autorisés à accéder à cette page
        if ($checkAcces === false) {

            // Récupération des groupes auquel est rattaché l'utilisateur
            $req = "SELECT      a.id
                    FROM        users_group a
                    INNER JOIN  users_group_link b
                    ON          a.id = b.id_group
                    WHERE       b.id_user = :id_user";
            $sql = $this->_dbh->prepare($req);
            $sql->execute( array( ':id_user'=>$id_user ));
            $lisGroupUser = array();
            if ($sql->rowCount() > 0) {
                while ($res = $sql->fetch()) {
                    $lisGroupUser[] = $res->id;
                }
            }

            // Comparaison aux groupes autorisés à acceder à cette page
            $req = "SELECT id_group FROM tree_group_link WHERE id_tree=:id_tree AND id_group <> 0";
            $sql = $this->_dbh->prepare($req);
            $sql->execute( array( ':id_tree'=>$id_tree ));
            $lisGroupTree = array();
            if ($sql->rowCount() > 0) {
                while ($res = $sql->fetch()) {
                    $lisGroupTree[] = $res->id_group;
                }
            }

            foreach ($lisGroupUser as $goupUser) {
                if (in_array($goupUser, $lisGroupTree)) {
                    $checkAcces = true;
                    break;
                }
            }
        }

        return $checkAcces;
    }


    private function menuLevel($id)
    {
        $req = "SELECT level FROM tree WHERE id = :id";
        $sql = $this->_dbh->prepare($req);
        $sql->execute( array( ':id'=>$id ));

        if ($sql->rowCount() > 0) {
            $res = $sql->fetch();
            return $res->level + 1;
        }
    }
}
