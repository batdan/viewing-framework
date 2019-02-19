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
     * Type de configuration de la requête :
     *      - 'simple' : filtre simple sur la liste des champs retournés
     *          - $_mongoFields  : array contenant la liste des champs à retourner
     *
     *      - 'custom' : Création d'une requête Mongo 'find' avec ses filtres et options
     *          - $_mongoFilters : array contenant les filtres de la requête 'find'
     *          - $_mongoOptions : array contenant les options de la requête 'find'
     * @var string
     */
    protected $_mongoReqType;

    /**
     * Tableau contenant les champs à retournés
     * Nécessaire si $this->_mongoReqType est en mode 'simple'
     * @var array
     */
    protected $_mongoFields;

    /**
     * Filtres de la requête 'find' MongoDb
     * Nécessaire si $this->_mongoReqType est en mode 'custom'
     * @var array
     */
    protected $_mongoFilters;

    /**
     * Options de la requête 'find' MongoDb
     * Nécessaire si $this->_mongoReqType est en mode 'custom'
     * @var array
     */
    protected $_mongoOptions;


    /**
     * Constructeur
     */
    public function __construct(array $options = array())
    {
        // Configuration du datatable
        $options['data-side-pagination'] ='server';
        parent::__construct($options);

        // Instance MongoDb
        try {
			$mongoInstance = mongoDbSingleton::getInstance($this->_mongoConf);
            $this->_connectCollection = $mongoInstance->{$this->_mongoCollection};
		} catch (\Exception $e) {
			echo $e->getMessage;
		}

        // Construction de la requête et des lignes du tableau
        $this->setData();
    }


    /**
     * Type de configuration du Datatable MongoDb : 'simple' ou 'custom'
     */
    private function checkMongoReqType()
    {
        if (empty($this->_mongoReqType) || ($this->_mongoReqType != 'simple' && $this->_mongoReqType != 'custom')) {
            echo '$this->_mongoReqType = ' . $this->_mongoReqType . '<br>';
            echo '$this->_mongoReqType n\'est pas correctement difinit !<br>';
        }
    }


    /**
     * Récupération de la liste des champs à partir de la requête d'initialisation
     */
    private function setChamps()
    {
        switch ($this->_mongoReqType)
        {
            case 'simple' :
                if (is_array($this->_mongoFields) && count($this->_mongoFields) > 0) {
                    $this->_champs = $this->_mongoFields;
                }

                break;

            case 'custom' :

                if (is_array($this->_mongoOptions) && is_array($this->_mongoOptions['projection']) && count($this->_mongoOptions['projection']) > 0) {

                    $this->_champs = array();

                    foreach ($this->_mongoOptions['projection'] as $name => $val) {
                        if ($val == 1) {
                            $this->_champs[] = $name;
                        }
                    }

                } else {

                    if (!is_array($this->_mongoOptions)) {
                        echo '$this->_mongoOptions doit être un tableau !<br>';
                    }

                    if (!is_array($this->_mongoOptions['projection']) || count($this->_mongoOptions['projection']) == 0) {
                        echo '$this->_mongoOptions[\'projection\'] doit être un tableau et doit contenir les champs à retourner<br>';
                    }
                }

                break;
        }

        // echo '<pre>';
        // print_r($this->_champs);
        // echo '</pre>';
    }


    /**
     * Préparation des données pour créer le flux Json
     *
     * @param   boolean     $csv        Certaines actions ne sont pas nécessaires pour l'exprot CSV
     */
    protected function setData($csv = false)
    {
        // Vérification de la configuration du type de requête : 'simple' ou 'custom'
        $this->checkMongoReqType();

        // Liste des champs demandés de la requête
        $this->setChamps();

        // Initialisation des filtres de la requête
        $this->initFilters();

        // Initialisation des options de la requête
        $this->initOptions();

        if ($csv === false) {
            // Comptage du nombre de lignes - A VIRER APRES AVOIR DECOMMENTE 'SEARCH'
            $this->countResult();

            // Gestion de l'ordre sur un champ ascendant ou descendant
            $this->setOrder();

            // Gestion de l'offset et du limit : résultats à afficher par page
            $this->offsetLimitResult();
        }

        // Mise en forme des résultats
        $this->getRows();

        // echo '<pre>';
        // print_r($this->_rows);
        // echo '</pre>';
    }


    /**
     * Initialisation des filtres de la requête
     */
    private function initFilters()
    {
        switch ($this->_mongoReqType)
        {
            case 'simple' :

                // Configuration de la regex pour l'utilisation moteur de recherche multi-critères
                if ($this->_search != '') {

                    $regex  = '^.*' . $this->_search . '.*$';

                    // Regex Mongo insensible à la casse
                    $mongoRegex = array(
                        '$regex' => $regex,
                        '$options' => '-i'
                    );

                    $or = array();
                    foreach ($this->_champs as $champ) {
                        $or[] = array($champ => $mongoRegex);
                    }

                    $this->_mongoFilters = array('$or' => $or);
                }

                break;

            case 'custom' :
                break;
        }

        // echo '<pre>';
        // print_r($this->_mongoFilters);
        // echo '</pre>';
    }


    /**
     * Initialisation des options de la requête
     */
    private function initOptions()
    {
        switch ($this->_mongoReqType)
        {
            case 'simple' :
                $projection = array('_id' => 0);
                foreach ($this->_champs as $champ) {
                    $projection[$champ] = 1;
                }

                $this->_mongoOptions = array(
                    'projection' => $projection
                );
                break;

            case 'custom' :
                break;
        }

        // echo '<pre>';
        // print_r($this->_mongoOptions);
        // echo '</pre>';
    }


    /**
     * Comptage du nombre de résultats
     */
    private function countResult()
    {
        $this->_total = $this->_connectCollection->count($this->_mongoFilters);

        // echo '<pre>';
        // echo 'Count : ' . $this->_total;
        // echo '</pre>';
    }


    /**
     * Autocomplétion de la requête pour la gestion du ORDER BY
     */
    private function setOrder()
    {
        if (empty($this->_sort)) {

            $order = 1;
            $sort  = $this->_champs[0];

        } else {

            switch($this->_order) {
                case 'asc'  :   $order =  1;    break;
                case 'desc' :   $order = -1;    break;
                default     :   $order =  1;
            }

            $sort  = $this->_sort;
        }

        $this->_mongoOptions['sort'] = array(
            $sort => $order
        );
    }


    /**
     * Retourne un nombre de lignes limité par l'offset et le limit
     */
    private function offsetLimitResult()
    {
        if (is_int($this->_offset) && is_int($this->_limit)) {
            $this->_mongoOptions['skip']  = $this->_offset;
            $this->_mongoOptions['limit'] = $this->_limit;
        }

        // echo '<pre>';
        // print_r($this->_mongoOptions);
        // echo '</pre>';
    }


    /**
     * Retourne un nombre de lignes limité par l'offset et le limit
     */
    private function getRows()
    {
        $res = $this->_connectCollection->find(
            $this->_mongoFilters,
            $this->_mongoOptions
        );

        // Formatage des résultats
        $i=0;
        $this->_rows = array();
        foreach ($res as $k => $v) {
            foreach ($v as $k2 => $v2) {

                $val = $v2;

                // Formatage automatique des champs Timestamp en DateTime
                if (is_object($v2) && get_class($v2) == 'MongoDB\BSON\UTCDateTime') {
                    $val = $v2->toDateTime()->format('Y-m-d H:i:s');
                }

                $this->_rows[$i][$k2] = $val;
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

                $label = '"' . $field['label'] . '"';

                if (isset($field['csv'])) {
                    if ($field['csv'] == 'true' || $field['csv'] === true) {
                        $ligne[] = $label;
                    }
                } else {
                    $ligne[] = $label;
                }
            }
        }

        $csv .= implode('; ', $ligne) . chr(10);

        // Récupération des données
        $this->setData(true);

        // Fonction anonyme - Modification des données post requête
        $csvModifier = $this->_csvModifier;

        foreach ($this->_rows as $k=>$v) {

            $this->_rows[$k] = array();

            foreach ($this->_champs as $champ) {
                if (is_object($v))  { $this->_rows[$k][$champ] = $v->$champ; }
                if (is_array($v))   { $this->_rows[$k][$champ] = $v[$champ]; }
            }

            // csvModifier
            if (! empty($csvModifier)) {
                $this->_rows[$k] = $csvModifier($this->_rows[$k]);
            }
        }

        foreach ($this->_rows as $k=>$v) {

            $ligne = array();
            foreach ($this->_fields as $field) {

                if (isset($field['csv'])) {
                    if ($field['csv'] == 'true' || $field['csv'] === true) {
                        $ligne[] = '"' . str_replace('"', '\"', $v[$field['name']]) . '"';
                    }
                } else {
                    $ligne[] = '"' . str_replace('"', '\"', $v[$field['name']]) . '"';
                }
            }
            $csv .= implode('; ', $ligne) . chr(10);
        }

        echo $csv;
    }
}
