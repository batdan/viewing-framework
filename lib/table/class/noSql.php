<?php
namespace table;

use core\mongoDbSingleton;

/**
 * Bootstrap Table
 * Gestion des tables d'une base de données
 * Méthode NoSQL MongoDb
 *
 * @author Daniel Gomes
 */

class noSql extends ajax
{
    /**
     * Choix de la configuration MongoDb (non renseigné = default)
     * @var string
     */
    protected $_mongoConf;

    /**
     * Nom de la collection MongoDb
     * @var string
     */
    protected $_mongoCollection;

    /**
     * Instance de connexion à une collection
     * @var object
     */
    protected $_connectCollection;

    /**
     * Options MongoDb
     * @var array
     */
    protected $_mongoOptions;

    /**
     * Requête d'initialisation
     * @var array
     */
    protected $_req;


    /**
     * Constructeur
     */
    public function __construct(array $options = array())
    {
        $options['data-side-pagination'] ='server';
        parent::__construct($options);

        // Instance MongoDb
        try {
			$mongoInstance = mongoDbSingleton::getInstance($this->_mongoConf);
            $this->_connectCollection = $mongoInstance->{$this->_mongoCollection};
		} catch (\Exception $e) {
			echo $e->getMessage;
		}

        // Exécution de la requête | pipeline
        // $res = $this->_connectCollection->aggregate(
        //     $this->_req,
        //     $this->_mongoOptions
        // );

        // $result = array();
        // $i = 0;
        // foreach ($res as $k => $v) {
        //     foreach ($v as $k2 => $v2) {
        //         $result[$i][$k2] = $v2;
        //     }
        //
        //     $i++;
        // }

        // echo '<pre>';
        //     echo $this->_mongoConf . chr(10) . '<br>';
        //     print_r($this->_req);
        //     print_r($this->_mongoOptions);
        //     print_r($result);
        // echo '</pre>';

        // $this->countResult();
        // $this->setChamps();
        // $this->offsetLimitResult();
        // $this->setData();
    }


    /**
     * Récupération de la liste des champs à partir de la requête d'initialisation
     */
    protected function setChamps()
    {
        foreach ($this->_req as $key => $val) {
            foreach ($val as $k => $v) {
                if ($k == '$project') {
                    $this->_champs = array_keys($this->_req[$key]['$project']);
                    break;
                }
            }
        }

        // echo '<pre>';
        //     print_r($this->_champs);
        // echo '</pre>';
    }


    /**
     * Préparation des données pour créer le flux Json
     */
    protected function setData()
    {
        // Liste des champs demandés de la requête
        $this->setChamps();

        // SEARCH
        // if (! empty($this->_search) || (isset($parsed['WHERE']) && count($parsed['WHERE']) > 0)) {
        //     if (isset($parsed['WHERE']) && count($parsed['WHERE']) > 0) {
        //         $where = $parsed['WHERE'];
        //     } else {
        //         $where = array();
        //     }
        //
        //     $parsed['WHERE'] = $this->setSearch($where);
        //
        //     $creator = new PHPSQLCreator($parsed);
        //     $req = $creator->created;
        //
        //     // Comptage du nombre de lignes
        //     $this->countResult($parsed, $req);
        //
        // } else {
        //
        //     // Comptage du nombre de lignes
        //     $this->countResult();
        // }

        // Comptage du nombre de lignes - A VIRER APRES AVOIR DECOMMENTE 'SEARCH'
        $this->countResult();
        ////////////////////////////////////////////////////////////////////////

        // ORDER BY
        // $parsed['ORDER'][0] = $this->setOrder();

        // LIMIT
        $this->offsetLimitResult();

        // Mise en forme des résultats
        $res = $this->_connectCollection->aggregate(
            $this->_req,
            $this->_mongoOptions
        );

        $i=0;
        foreach ($res as $k => $v) {
            foreach ($v as $k2 => $v2) {
                $this->_rows[$i][$k2] = $v2;
            }

            $i++;
        }

        // echo '<pre>';
        // print_r($this->_rows);
        // echo '</pre>';
    }


    /**
     * Comptage du nombre de résultats
     */
    private function countResult()
    {
        // Bloc '$project' pour le comptage des résultats
        $blocProject = array ('_id' => 0);

        // Bloc '$group' pour le comptage des résultats
        $blocGroup = array (
            "_id" => null,
            "myCount" => array(
                '$sum' => 1
            )
        );

        $groupUpdate = false;

        foreach ($this->_req as $key => $val) {

            foreach ($val as $k => $v) {

                // On surclasse le bloc '$project'
                if ($k == '$project') {
                    $reqCount[$key]['$project'] = $blocProject;

                // On surclasse le bloc '$group'
                } elseif ($k == '$group') {
                    $reqCount[$key]['$group'] = $blocGroup;
                    $groupUpdate = true;

                // Ajout de tous les autres blocs
                } else {
                    $reqCount[$key][$k] = $v;
                }
            }
        }

        // Le bloc '$group' n'existait pas, on l'ajoute
        if ($groupUpdate === false) {
            $reqCount[]['$group'] = $blocGroup;
        }

        // Exécution de la requête | pipeline
        $res = $this->_connectCollection->aggregate($reqCount, $this->_mongoOptions)->toArray();

        // Nombre de résultats
        $this->_total = $res[0]['myCount'];

        // echo '<pre>';
        //     print_r($reqCount);
        //     echo 'Total résultats : ' . $this->_total . chr(10) . '<br>';
        // echo '</pre>';
    }


    /**
     * Autocomplétion de la requête pour la gestion du ORDER BY
     */
    protected function setOrder()
    {
        if (empty($this->_sort)) {
            $order = 'ASC';
            $sort  = $this->_champs[0];
        } else {
            $order = $this->_order;
            $sort  = $this->_sort;
        }
        return array (
                      'expr_type' => 'colref',
                      'base_expr' => $sort,
                      'no_quotes' => array  (
                                            'delim' => false,
                                            'parts' => array ($sort),
                                            ),
                      'sub_tree'  => false,
                      'direction' => $order,
                    );
    }


    /**
     * Autocomplétion de la requête pour le moteur de recherche multi-champs
     */
    protected function setSearch($where)
    {
        // Création de la partie de la close where pour le moteur de recherche
        $addWhere = array();

        $i=0;
        $a=1;
        $addWhereTxt = '';

        foreach ($this->_champs as $k=>$v) {

            $addWhereTxt .= $v;
            $addWhere[$i] = array (
                                'expr_type' => 'colref',
                                'base_expr' => $v,
                                'no_quotes' => array (
                                                    'delim' => false,
                                                    'parts' => array ($v),
                                ),
                                'sub_tree' => false,
            );
            $i++;

            $addWhereTxt .= " LIKE ";
            $addWhere[$i] = array (
                'expr_type' => 'operator',
                'base_expr' => 'LIKE',
                'sub_tree'  => false,
            );
            $i++;

            $addWhereTxt .= "'%" . $this->_search . "%'";
            $addWhere[$i] = array (
                'expr_type' => 'const',
                'base_expr' => "'%" . $this->_search . "%'",
                'sub_tree'  => false,
            );
            $i++;

            if ($a < count($this->_champs)) {

                $addWhereTxt .= " OR ";
                $addWhere[$i] = array (
                    'expr_type' => 'operator',
                    'base_expr' => 'OR',
                    'sub_tree'  => false,
                );
                $i++;
            }

            $a++;
        }

        // On vérifie si une close where était déjà présente, auquel cas il faut concaténer
        $countWhere = count($where);

        if ($countWhere > 0) {

            $i=$countWhere;

            $where[$i] = array (
                'expr_type' => 'operator',
                'base_expr' => 'AND',
                'sub_tree'  => false,
            );
            $i++;

            $where[$i] = array (
                'expr_type' => 'bracket_expression',
                'base_expr' => '(' . $addWhereTxt . ')',
                'sub_tree'  => $addWhere,
            );

        } else {

            $i=0;
            $where = $addWhere;
        }

        return $where;
    }


    /**
     * Retourne un nombre de lignes limité par l'offset et le limit
     */
    protected function offsetLimitResult()
    {
        if (is_int($this->_offset) && is_int($this->_limit)) {

            $skipUpdate  = false;
            $limitUpdate = false;

            foreach ($this->_req as $key => $val) {

                foreach ($val as $k => $v) {

                    // On surclasse le bloc '$project'
                    if ($k == '$skip') {
                        $this->_req[$key]['$skip'] = $this->_offset;
                        $skipUpdate = true;

                    // On surclasse le bloc '$group'
                    } elseif ($k == '$limit') {
                        $this->_req[$key]['$limit'] = $this->_limit;
                        $limitUpdate = true;

                    // Ajout de tous les autres blocs
                    } else {
                        $this->_req[$key][$k] = $v;
                    }
                }
            }

            // Le bloc '$skip' n'existait pas, on l'ajoute
            if ($skipUpdate === false) {
                $this->_req[]['$skip'] = $this->_offset;
            }

            // Le bloc '$limit' n'existait pas, on l'ajoute
            if ($limitUpdate === false) {
                $this->_req[]['$limit'] = $this->_limit;
            }
        }

        // echo '<pre>';
        // print_r($this->_req);
        // echo '</pre>';
    }


    /**
     * Crétion d'un csv avec le tableau complet
     */
    protected function getCsv()
    {
        header('Content-Type: application/csv; utf-8');
        header("Content-Disposition: attachment; filename=" . $this->_id . ".csv");

        $csv = '';

        // Liste des champs de la requête
        $this->setChamps();

        // Libellés
        $ligne = array();
        foreach ($this->_fields as $field) {

            if (isset($field['label'])) {
                if (empty($field['csv'])) {
                    $ligne[] = '"' . $field['label'] . '"';
                } else {
                    if ($field['csv'] == 'true') {
                        $ligne[] = '"' . $field['label'] . '"';
                    }
                }
            } else {
                $ligne[] = '';
            }
        }
        $csv .= implode('; ', $ligne) . chr(10);

        $data = array();

        $sql = $this->_dbh->query($this->_req);
        $res = $sql->fetchAll();

        // Création du flux json
        $data['rows'] = array();

        // Fonction anonyme - Modification des données post requête
        $csvModifier = $this->_csvModifier;

        foreach ($res as $k=>$v) {

            $data['rows'][$k] = array();

            foreach ($this->_champs as $champ) {
                if (is_object($v))  { $data['rows'][$k][$champ] = $v->$champ; }
                if (is_array($v))   { $data['rows'][$k][$champ] = $v[$champ]; }
            }

            // csvModifier
            if (! empty($csvModifier)) {
                $data['rows'][$k] = $csvModifier($data['rows'][$k]);
            }
        }


        foreach ($data['rows'] as $k=>$v) {

            $ligne = array();
            foreach ($this->_fields as $field) {

                if (empty($field['csv'])) {
                    $ligne[] = '"' . str_replace('"', '\"', $v[$field['name']]) . '"';
                } else {
                    if ($field['csv'] == 'true') {
                        $ligne[] = '"' . str_replace('"', '\"', $v[$field['name']]) . '"';
                    }
                }
            }
            $csv .= implode('; ', $ligne) . chr(10);
        }

        echo $csv;
    }
}
