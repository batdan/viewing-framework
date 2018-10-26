<?php
namespace form;

/**
 * Gestion des éléments de formulaire
 * Selection multiple
 *
 * @author Daniel Gomes
 */
class elemSelectMultiple extends element
{
    /**
     * Attributs
     */
    private $_bddName;          // Nom de la base de données
    private $_dbh;              // Instance PDO


    /**
     * Constructeur
     */
    public function __construct($form, $champ, $label=null, $options=null)
    {
        $type  = null;

        parent::__construct($form, $type, $champ, $label, $options);

        $this->_load = true;    // On prévient la classe 'form' de la modification de la méthode load
        $this->_save = false;   // On prévient la classe 'form' de la modification de la méthode save

        // Instance PDO
        $this->_bddName = $form->getBddName();
		$this->_dbh = \core\dbSingleton::getInstance( $this->_bddName );

        // Chargement de l'élément
        $this->chpSelectMultiple();
    }


    /**
	 * Affichage des champs 'selectMultiple'
	 */
	private function chpSelectMultiple()
	{
		// Chargement de la librairie JS
		\core\libIncluderList::add_bootstrapSelect();

		// id champ
		$id = $this->_form->getName() . '_' . $this->_champ . '_id';

		// Conteneur de champ
		$div = $this->_dom->createElement('div');
		$div->setAttribute('class', $this->_champWidth);

        $inputHidden = $this->_dom->createElement('input');
        $inputHidden->setAttribute('type', 'hidden');
        $inputHidden->setAttribute('id', 'val_' . $id);
        $inputHidden->setAttribute('value', '');

        $div->appendChild($inputHidden);

		$select = $this->_dom->createElement('select');
		$select->setAttribute('name', 	$this->_form->getName() . '_' . $this->_champ);
		$select->setAttribute('id', 	$id);
		$select->setAttribute('class',		'selectpicker form-control');
		$select->setAttribute('data-width',	'100%');
		$select->setAttribute('multiple',	'');

		// Champ opbligatoire
		if (isset($this->_options['required']) && $this->_options['required'] === 'true') {
			$select->setAttribute('required', true);
		}

        // Récupération des options
        if ($this->_options['table_2']) {

            $table2 = $this->_options['table_2'];

            $req = "SELECT " . $table2['key'] . ", " . $table2['chp'] . " FROM " . $table2['table'];
            $sql = $this->_dbh->query($req);

            if ($sql->rowCount() > 0) {

                while ($res = $sql->fetch()) {

                    $option = $this->_dom->createElement('option');
    				$option->setAttribute('value', $res->{$table2['key']});

    				$text = $this->_dom->createTextNode($res->{$table2['chp']});

    				$option->appendChild($text);
    				$select->appendChild($option);
                }
            }
        }

		$div->appendChild($select);

		// Message d'erreur
		if (isset($this->_options['required']) && $this->_options['required'] === 'true') {
			$error = $this->_dom->createElement('div');
			$error->setAttribute('class', 'help-block with-errors');
			$div->appendChild($error);
		}

		$this->_container->appendChild($div);
	}


    /**
     * On surclasse la méthode load
     */
    public function load($data)
    {
        if (! empty($this->_form->getClePrimaireId())) {

            $idFiche = $this->_form->getClePrimaireId();

            // Récupération des options séléctionnés
            $tableL = $this->_options['table_L'];

            $req = "SELECT " . $tableL['cle_2'] . " FROM " . $tableL['table'] . " WHERE " . $tableL['cle_1'] . " = :idFiche ORDER BY " . $tableL['cle_2'] . " ASC";

            $sql = $this->_dbh->prepare($req);
            $sql->execute(array( ':idFiche' => $idFiche ));

            $activOnLoad = array();
            while ($res = $sql->fetch()) {
                $activOnLoad[] = $res->{$tableL['cle_2']};
            }

            $activOnLoad = implode(", ", $activOnLoad);
            $idChamp     = $this->_form->getName() . '_' . $this->_champ . '_id';


            ///////////////////////////////////////////////////////////////////////
            // Méthode JS 'onload' pour récupérer les options selected au chargement
            // Méthode JS 'onchange' pour stocker les changements de valeurs en temps réel
            ///////////////////////////////////////////////////////////////////////
            $js = <<<eof
$(window).on('load', function () {
    $('#val_$idChamp').val('$activOnLoad');
    $('#$idChamp').selectpicker('val', [$activOnLoad]);
});
$(window).on('change', function () {
    $('#val_$idChamp').val($('#$idChamp').selectpicker('val'));
});
eof;
            \core\libIncluder::add_JsScript($js);
            ///////////////////////////////////////////////////////////////////////


            ///////////////////////////////////////////////////////////////////////
            // Ajout code CURL a exécuter au "save" ///////////////////////////////
            ///////////////////////////////////////////////////////////////////////
            $idChamp = $this->_form->getName() . '_' . $this->_champ . '_id';

            $tableL = $this->_options['table_L'];
            $table2 = $this->_options['table_2'];

            if ( $_SERVER['SERVER_PORT'] == 80 ) {
                $proto = 'http';
            } else {
                $proto = 'https';
            }

            $curlInSave = array(
                            'urlInc' => $proto . '://' . $_SERVER['HTTP_HOST'] . '/vendor/vw/framework/lib/form/inc/elemSelectMultiple.php',
                            'values' => array(
                                    'bddName'   => $this->_bddName,
                                    'idFiche'   => $idFiche,
                                    'idChp'     => '#val_' .$idChamp,
                                    'valChp'    => '||| + ' . "$('#val_$idChamp').val()" . ' + |||',  // les ||| seront remplacé par un double quote après le json_encode dans la classe form
                                    'tableL'    => $tableL,
                                    'table2'    => $table2,
                             )
                        );

            $this->_form->setCurlInSave($curlInSave);
            /*
            echo '<pre>';
            print_r()
            echo '</pre>';
            */
            ///////////////////////////////////////////////////////////////////////
        }

        return $data;
    }


    /**
     * On surclasse la méthode save
     */
    public function save($data)
    {

        return $data;
    }
}
