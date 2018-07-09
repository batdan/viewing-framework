<?php
namespace modal;

/**
 * Ajout d'une modal Bootstrap
 * Modal de suppression d'une entrée en base de données
 *
 * @author Daniel Gomes
 */
class deleteEntry extends base
{
    /**
     * Constructeur
     */
    public function __construct(array $options=array())
    {
        parent::__construct($options);

        // Remplissage de la modal générique
        $this->initExtends();
    }


    /**
     * Profil de suppresion d'entrée utilisé par Boostrap table
     */
    protected function initExtends()
    {
        $xpath = new \DOMXPath($this->_dom);

        // Ajout des champs cachés pour récupérer la table et l'id de l'entrée à supprimer
        $query	= '//div[@id="' . $this->_idModal. '"]';
        $entries= $xpath->query($query);
        $modal	= $entries->item(0);

        $inputTable = $this->_dom->createElement('input');
        $inputTable->setAttribute('type', 'hidden');
        $inputTable->setAttribute('id', $this->_idModal . '_inputTableBDD');

        $inputId = $this->_dom->createElement('input');
        $inputId->setAttribute('type', 'hidden');
        $inputId->setAttribute('id', $this->_idModal . '_inputIdBDD');

        $modal->appendChild($inputTable);
        $modal->appendChild($inputId);

        // Ajout du titre de la modal
        $query	= '//h4[@id="' . $this->_idModal. '_modalTitle"]';
        $entries= $xpath->query($query);
        $h4	= $entries->item(0);

        $h4Txt = $this->_dom->createTextNode('Suppression');
        $h4->appendChild($h4Txt);

        // Ajout de la question -> P1
        $query	= '//p[@id="' . $this->_idModal. '_P1"]';
        $entries= $xpath->query($query);
        $p1	= $entries->item(0);

        $p1Txt = $this->_dom->createTextNode('Voulez-vous supprimer cette fiche ?');
        $p1->appendChild($p1Txt);

        // Configuration du bouton d'action
        $query	= '//button[@id="' . $this->_idModal . '_ButtonAction"]';
        $entries= $xpath->query($query);
        $button	= $entries->item(0);

        $button->setAttribute('class', 'btn btn-danger');

        $buttonTxt = $this->_dom->createTextNode('Supprimer');
        $button->appendChild($buttonTxt);

        // Code JS
        $js = <<<eof
$('#{$this->_idModal}_ButtonAction').click(function() {

    $.post("/vendor/vw/framework/lib/modal/ajax/deleteEntry.php",
    {
        table : $('#{$this->_idModal}_inputTableBDD').val(),
        id : $('#{$this->_idModal}_inputIdBDD').val()
    },
    function success(data)
    {
        // console.log(data);
        if (data.result == 'true') {
            $('#{$this->_idCallBack}').bootstrapTable('refresh');
            myNotify('info', 'Entrée supprimée');
        }
    }, 'json');
});
eof;

        \core\libIncluder::add_JsScript($js);
    }
}
