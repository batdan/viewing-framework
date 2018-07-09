<?php
namespace table;

/**
 * Bootstrap Table
 * Gestion des tables d'une base de donnée - méthode data : passage d'un tableau
 * array(
 *      array(
 *            'champ1' => 'val'
 *            'champ2' => 'val'
 *      ),
 *      array(
 *            'champ1' => 'val'
 *            'champ2' => 'val'
 *      ),
 * )
 *
 * @author Daniel Gomes
 */

class data extends ajax
{
    /**
     * Constructeur
     */
    public function __construct(array $options = array())
    {
        $options['data-side-pagination'] ='client';

        parent::__construct($options);
    }


    /**
     * Récupération de la liste des champs à partir de la requête d'initialisation
     */
    protected function setChamps()
    {
        $this->_champs = array();

        $champs = $this->_dataSet;

        foreach ($champs[0] as $k=>$v) {
            $this->_champs[] = $k;
        }
    }


    /**
     * Préparation des données pour créer le flux Json
     */
    protected function setData()
    {
        // Liste des champs de la requête
        $this->setChamps();

        $this->_rows = $this->_dataSet;
    }


    /**
     * Création d'un csv avec le tableau complet
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

        // Création du flux json
        $data['rows'] = array();

        // Fonction anonyme - Modification des données post requête
        $csvModifier = $this->_csvModifier;

        foreach ($this->_dataSet as $k=>$v) {

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
