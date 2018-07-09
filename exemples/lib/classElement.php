<?php
namespace test;

class classElement extends \form\element
{
    public function __construct($form, $type, $champ, $label=null, $options=null)
    {
        parent::__construct($form, $type, $champ, $label, $options);

        // On prévient de la modification de la méthode save
        $this->_save = true;
    }

    public function save($data)
    {
        $data['nom'] .= ' bla';

        return $data;
    }
}
