<?php
namespace table;

/**
 * Bootstrap Table
 * Création des flux Json pour Bootstrap Table
 *
 * @author Daniel Gomes
 */

class ajax extends base
{
    /**
     * Attributs
     */
    protected $_total;      // Nombre de résultat dans le cas d'une requête SQL
    protected $_rows;       // Données pour la création du flux Json
    private   $_options;


    /**
     * Constructeur
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);

        $this->_options = $options;
    }


    /**
     * Crétion du flux json appelé au premier chargement et à toutes les actions modifiant le grid
     */
    protected function getJson()
    {
        header('Content-Type: application/json');

        // Récupération des lignes
        $this->setData();

        $data = array();

        // Récupération du nombre total de résultats
        if (isset($this->_total)) {
            $data['total'] = $this->_total;
        }

        // Création du flux json
        $data['rows'] = array();

        // Fonction anonyme - Modification des données post requête
        $jsonModifier = $this->_jsonModifier;

        if (count($this->_rows) > 0) {

            foreach ($this->_rows as $k=>$v) {

                $data['rows'][$k] = array();

                foreach ($this->_champs as $champ) {
                    if (is_object($v))  { $data['rows'][$k][$champ] = $v->$champ; }
                    if (is_array($v))   { $data['rows'][$k][$champ] = $v[$champ]; }
                }

                // jsonModifier
                $data['rows'][$k] = $jsonModifier($data['rows'][$k]);
            }
        }

        // Le total n'est pas renvoyé pour les datatables de type 'data'
        if ($this->_options['data-side-pagination'] == 'client') {
            $data = $data['rows'];
        }

        echo json_encode($data);
    }
}
