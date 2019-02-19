<?php
namespace table;

use core\dbSingleton;
use PHPSQLParser\PHPSQLParser;
use PHPSQLParser\PHPSQLCreator;

/**
 * Bootstrap Table
 * Gestion des tables d'une base de données
 * Méthode MySQL
 *
 * @author Daniel Gomes
 */

class sql extends ajax
{
    /**
     * Attributs
     */
    protected $_dbh;                                // Instance PDO
    protected $_bddName;                            // Nom de la base de données utilisée
    protected $_req;                                // Requête d'initialisation


    /**
     * Constructeur
     */
    public function __construct(array $options = array())
    {
        $options['data-side-pagination'] ='server';
        parent::__construct($options);

        // Instance PDO
		$this->_dbh = dbSingleton::getInstance($this->_bddName);
    }


    /**
     * Récupération de la liste des champs à partir de la requête d'initialisation
     */
    protected function setChamps()
    {
        $parser = new PHPSQLParser($this->_req);
        $parsed = $parser->parsed;


        $champs = array();
        foreach ($parsed['SELECT'] as $k=>$v) {
            $champs[] = $v['base_expr'];
        }

        $this->_champs = $champs;
    }


    /**
     * Préparation des données pour créer le flux Json
     */
    protected function setData()
    {
        // Instance de PHPSQLParser
        $parser = new PHPSQLParser($this->_req);
        $parsed = $parser->parsed;

        // Liste des champs de la requête
        $this->setChamps();

        // Instance PHPSQLCreator
        $creator = new PHPSQLCreator($parsed);

        // SEARCH
        if (! empty($this->_search) || (isset($parsed['WHERE']) && count($parsed['WHERE']) > 0)) {
            if (isset($parsed['WHERE']) && count($parsed['WHERE']) > 0) {
                $where = $parsed['WHERE'];
            } else {
                $where = array();
            }

            $parsed['WHERE'] = $this->setSearch($where);

            $creator = new PHPSQLCreator($parsed);
            $req = $creator->created;

            // Comptage du nombre de lignes
            $this->countResult($parsed, $req);

        } else {

            // Comptage du nombre de lignes
            $this->countResult($parsed);
        }

        // ORDER BY
        $parsed['ORDER'][0] = $this->setOrder();

        // LIMIT
        $parsed['LIMIT'] = array(
                                'offset'    => $this->_offset,
                                'rowcount'  => $this->_limit,
        );

        $creator = new PHPSQLCreator($parsed);
        $req = $creator->created;
        $sql = $this->_dbh->query($req);

        $this->_rows = $sql->fetchAll();
    }


    /**
     * Comptage du nombre de résultats
     */
    private function countResult($parsed, $baseReq = null)
    {
        $tableName = $parsed['FROM'][0]['table'];
        $firstChp  = $parsed['SELECT'][0]['base_expr'];

        $req = "SELECT COUNT($firstChp) AS myCount FROM $tableName";

        if (!is_null($baseReq)) {

            $expReq = explode('WHERE', $baseReq);
            $req .= " WHERE" . $expReq[1];
        }

        $sql = $this->_dbh->query($req);
        $res = $sql->fetch();

        $this->_total = $res->myCount;
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
                if (empty($field['csv']) && $field['csv'] != 'false' && $field['csv'] !== false) {
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

                if (empty($field['csv']) && $field['csv'] != 'false' && $field['csv'] !== false) {
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
