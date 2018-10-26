<?php
namespace table;

/**
 * Bootstrap Table
 * Création des boutons d'action sur les ligne d'un tableau
 *
 * Attention : La modal de suppression d'une entrée est créé dans la classe 'tableBase'
 *
 * @author Daniel Gomes
 */

class buttonsAction
{
    /**
     * Attributs
     */
    private $_dom;

    private $_idModal;
    private $_buttonGroup;

    public function __construct()
    {
        // DOM
        $this->_dom = new \DOMDocument("1.0", "utf-8");

        $this->_buttonGroup = $this->_dom->createElement('div');
        $this->_buttonGroup->setAttribute('class', 'btn-group btn-group-sm');
    }


    public function setIdModal($idModal)
    {
        $this->_idModal = $idModal;
    }


    /**
     * Edition de l'entrée de la table
     *
     * @param   string      $link
     * @param   string      $class
     */
    public function setButtonEdit($link, $class=null, $title='Editer')
    {
        $buttonEdit = $this->_dom->createElement('button');
        $buttonEdit->setAttribute('type', 'button');
        $buttonEdit->setAttribute('class', 'btn btn-default');
        $buttonEdit->setAttribute('onclick', "document.location.href='" . $link . "'");
        $buttonEdit->setAttribute('aria-label', '');
        $buttonEdit->setAttribute('title', $title);

        $iconEdit = $this->_dom->createElement('i');
        $iconEdit->setAttribute('class', 'fa fa-pencil');

        $buttonEdit->appendChild($iconEdit);

        $this->_buttonGroup->appendChild($buttonEdit);
    }


    /**
     * Envoi vers une nouvelle liste de résultats
     *
     * @param   string      $link
     * @param   string      $class
     */
    public function setButtonList($link, $class=null, $title='Liste')
    {
        $buttonList = $this->_dom->createElement('button');
        $buttonList->setAttribute('type', 'button');
        $buttonList->setAttribute('class', 'btn btn-default');
        $buttonList->setAttribute('onclick', "document.location.href='" . $link . "'");
        $buttonList->setAttribute('aria-label', '');
        $buttonList->setAttribute('title', $title);

        $iconList = $this->_dom->createElement('i');
        $iconList->setAttribute('class', 'fa fa-bars');

        $buttonList->appendChild($iconList);

        $this->_buttonGroup->appendChild($buttonList);
    }


    /**
     * Suppression de l'entrée de la table
     *
     * @param   integer     $row       Numéro de la ligne
     * @param   string      $class
     */
    public function setButtonDelete($row, $tableBDD, $title='Supprimer')
    {
        $rowValues  = array_values($row);
        $p2Txt      = $rowValues[0] . ' - ' . $rowValues[1];

        $onclick    = array();
        $onclick[]  = "$('#" . $this->_idModal . "_P2').html('" . $p2Txt . "');";
        $onclick[]  = "$('#" . $this->_idModal . "_inputTableBDD').attr('value', '" . $tableBDD . "');";
        $onclick[]  = "$('#" . $this->_idModal . "_inputIdBDD').attr('value', '" . $row['id'] . "');";

        $buttonDelete = $this->_dom->createElement('button');
        $buttonDelete->setAttribute('type', 'button');
        $buttonDelete->setAttribute('class', 'btn btn-danger');
        $buttonDelete->setAttribute('onclick', implode(' ', $onclick));
        $buttonDelete->setAttribute('data-target', '#' . $this->_idModal);
        $buttonDelete->setAttribute('data-toggle', 'modal');
        $buttonDelete->setAttribute('aria-label', '');
        $buttonDelete->setAttribute('title', $title);

        $iconEdit = $this->_dom->createElement('i');
        $iconEdit->setAttribute('class', 'fa fa-trash');

        $buttonDelete->appendChild($iconEdit);

        $this->_buttonGroup->appendChild($buttonDelete);
    }


    /**
     * Appel des boutons d'action d'un entrée
     */
    public function getButtonsGroup()
    {
        $this->_dom->appendChild($this->_buttonGroup);
        return $this->_dom->saveHTML();
    }
}
