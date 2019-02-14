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
        // Configuration du tableau
        $options['data-side-pagination'] ='server';
        parent::__construct($options);

        // Connexion à une collection MongoDb
        $this->connectCollection();
    }


    /**
     * Création d'une instance MongoDb et connexion à une bdd.collection
     */
    private function connectCollection()
    {
        try {
			$mongoInstance = mongoDbSingleton::getInstance($this->_mongoConf);
            $this->_connectCollection = $mongoInstance->{$this->_mongoCollection};
		} catch (\Exception $e) {
			echo $e->getMessage;
		}
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
        // print_r($this->_champs);
        // echo '</pre>';
    }


    /**
     * Préparation des données pour créer le flux Json
     */
    protected function setData()
    {
        // Récupération de la liste des champs demandés de la requête
        $this->setChamps();

        // Moteur de recherche multi-critères
        // $this->setSearch();

        // Comptage du nombre de lignes
        $this->countResult();

        // ORDER BY
        // $parsed['ORDER'][0] = $this->setOrder();

        // LIMIT
        $this->offsetLimitResult();

        // Mise en forme des résultats
        $this->getResults();

        echo '<pre>';
        error_log(json_encode($this->_req));
        echo '</pre>';
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
    }


    /**
     * Autocomplétion de la requête pour la gestion du ORDER BY
     */
    private function setOrder()
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
    private function setSearch()
    {
        if (isset($this->_search) && $this->_search != '') {

            // Like %search% sur tous les champs de la requête
            $allLikes = array();
            foreach ($this->_champs as $champ) {
                $allLikes[$champ] =  '/' . $this->_search . '/';
            }

            // Ajout de la recherche multi-critères
            $addMatch = array(
                '$or' => array(
                    $allLikes
                )
            );

            // Boucle pour retrouver le bloc '$match' s'il existe
            $matchUpdate = false;


            foreach ($this->_req as $key => $val) {

                foreach ($val as $k => $v) {

                    // On complète le bloc '$match'
                    if ($k == '$match') {
                        $this->_req[$key]['$match'][] = $addMatch;
                        $matchUpdate = true;
                    }
                }
            }

            // Le bloc '$match' n'existait pas, on l'ajoute
            if ($matchUpdate === false) {
                $this->_req[]['$match'][] = $addMatch;
            }

        }

        error_log(json_encode($this->_req));
    }


    /**
     * Retourne un nombre de lignes limité par l'offset et le limit
     */
    private function offsetLimitResult()
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
     * Execution & formatage des résultats de la requête
     */
    private function getResults()
    {
        // Exécution de la requête
        $res = $this->_connectCollection->aggregate(
            $this->_req,
            $this->_mongoOptions
        );

        // Formatage des résultats
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
