<?php
namespace form;

/**
 * Gestion des éléments de formulaire
 * Gestion du chiffrement des mots de passe (réversible)
 *
 * @author Daniel Gomes
 */
class elemPassCrypt extends element
{
    /**
     * Attributs
     */
    private $_dbh;              // Instance PDO

    private $_crypt;            // Chiffrement réversible


    /**
     * Constructeur
     */
    public function __construct($form, $type, $champ, $label=null, $options=null)
    {
        $type = null;

        parent::__construct($form, $type, $champ, $label, $options);

        $this->_load = true;    // On prévient la classe 'form' de la modification de la méthode load
        $this->_save = true;    // On prévient la classe 'form' de la modification de la méthode save

        // Instance PDO
		$this->_dbh = \core\dbSingleton::getInstance();

        // Chargement de l'élément
        $this->chpPassCrypt();

        // Chiffrement
        $this->_crypt = new \core\crypt();

        // Toogle affichage en clair du mot de passe
        $this->affPassword();
    }


    /**
	 * Affichage des champs 'passCrypt'
	 */
	private function chpPassCrypt()
	{
        $name = $this->_form->getName() . '_' . $this->_champ;
        $id   = $name . '_id';

        // Conteneur de champ
        $div = $this->_dom->createElement('div');
        $div->setAttribute('class', $this->_champWidth);

        // Input group
        $inputGroup = $this->_dom->createElement('div');
        $inputGroup->setAttribute('class', 'input-group');

        $input = $this->_dom->createElement('input');
        $input->setAttribute('name', 	$name);
        $input->setAttribute('id', 		$id);
        $input->setAttribute('type', 	'password');
        $input->setAttribute('class', 	$this->_champWidth . ' form-control has-feedback');
        $input->setAttribute('aria-describedby', $name . '_eye_id');

        // Chargement des attributs
        if (! is_null($this->_options)) {
            foreach ($this->_options as $k=>$v) {
                if (! in_array($k, $this->_optionsSpe)) {
                    $input->setAttribute($k, $v);
                }
            }
        }

        // Champ opbligatoire
        if (isset($this->_options['required']) && $this->_options['required'] === 'true') {
            $input->setAttribute('required', true);
        }

        $eyeAddon = $this->_dom->createElement('div');
        $eyeAddon->setAttribute('id', $name . '_eye_id');
        $eyeAddon->setAttribute('class', 'input-group-addon');
        $eyeAddon->setAttribute('onclick', "typeInputPass('".$id."');");

        $eyeAddonIcon = $this->_dom->createElement('i');
        $eyeAddonIcon->setAttribute('class', 'eye-password fa fa-eye');

        $eyeAddon->appendChild($eyeAddonIcon);

        $inputGroup->appendChild($input);
        $inputGroup->appendChild($eyeAddon);

        $div->appendChild($inputGroup);

        // Message d'erreur
        if (isset($this->_options['required']) && $this->_options['required'] === 'true') {
            $error = $this->_dom->createElement('div');
            $error->setAttribute('class', 'help-block with-errors');
            $div->appendChild($error);
        }

        $this->_container->appendChild($div);
    }


    /**
	 * Affichage ou non du mot de passe en clair
	 */
    private function affPassword()
    {
        $js = <<<eof
function typeInputPass(id)
{
    if ($('#' + id).attr('type') == 'password') {
        $('#' + id).attr('type', 'text');
    } else {
        $('#' + id).attr('type', 'password');
    }
}
eof;
        \core\libIncluder::add_JsScript($js);
    }


    /**
     * On surclasse la méthode load
     */
    public function load($data)
    {
        $data[$this->_champ] = $this->_crypt->decrypt($data[$this->_champ]);

        return $data;
    }


    /**
     * On surclasse la méthode save
     */
    public function save($data)
    {
        $data[$this->_champ] = $this->_crypt->encrypt($data[$this->_champ]);

        return $data;
    }
}
