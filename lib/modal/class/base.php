<?php
namespace modal;

/**
 * Ajout d'une modal Bootstrap
 * Stucture de base
 *
 * @author Daniel Gomes
 */
class base
{
    /**
     * Liste des attributs
     */
    protected $_dom;                        // Gestion en dom du code généré

    protected $_bddName;                    // Nom de la base de données

    protected $_idModal;                    // Id de la modal
    protected $_idCallBack;                 // Id d'un élément lié pour toute action en JS

    protected $_modalTitle;                 // Titre de la modal

    protected $_P1txt;                      // modalBody paragraphe 1
    protected $_P2txt;                      // modalBody paragraphe 2

    protected $_buttonActionTxt;            // Texte du bouton d'action
    protected $_buttonActionClass;          // Classe du bouton d'action ( default | primary | warning | danger )

    private $_profil;                       // Profils de modals prédéfinis :
                                            //                       - tableDeleteEntry


    /**
     * Constructeur
     */
    protected function __construct(array $options=array())
    {
        $this->_dom = new \DOMDocument("1.0", "utf-8");

        $options = array_merge($this->getDefaultOptions(), $options);
        $this->setOptions($options);

        // Création d'une modale générique
        $this->init();
    }


    /**
     * Initialisation des options par défaut
     */
    protected function getDefaultOptions()
    {
        return array(
                    'bddName'           => '',

                    'idModal'           => 'myModal',
                    'idCallBack'        => '',

                    'modalTitle'        => '',

                    'P1txt'             => '',
                    'P2txt'             => '',

                    'buttonActionTxt'   => '',
                    'buttonActionClass' => 'primary',
        );
    }


    /**
     * Récupération des options du data table
     */
    protected function setOptions($options)
    {
        $this->_bddName             = $options['bddName'];

        $this->_idModal             = $options['idModal'];
        $this->_idCallBack          = $options['idCallBack'];

        $this->_modalTitle          = $options['modalTitle'];

        $this->_P1txt               = $options['P1txt'];
        $this->_P2txt               = $options['P2txt'];

        $this->_buttonActionTxt     = $options['buttonActionTxt'];
        $this->_buttonActionClass   = $options['buttonActionClass'];
    }


    /**
     * Initialisation du conteneur
     */
    protected function init()
    {
        $modal = $this->_dom->createElement('div');
        $modal->setAttribute('aria-labelledby', 'tableModalLabel');
        $modal->setAttribute('role',            'dialog');
        $modal->setAttribute('tabindex',        '-1');
        $modal->setAttribute('id',              $this->_idModal);
        $modal->setAttribute('class',           'modal fade');

        $modalDialog = $this->_dom->createElement('div');
        $modalDialog->setAttribute('class', 'modal-dialog');

        //////////////////////////////////////////////////////////
        // Content
        $modalContent = $this->_dom->createElement('div');
        $modalContent->setAttribute('class', 'modal-content');

        //////////////////////////////////////////////////////////
        // Header
        $modalHeader = $this->_dom->createElement('div');
        $modalHeader->setAttribute('class', 'modal-header');

        $buttonClose = $this->_dom->createElement('button');
        $buttonClose->setAttribute('type',          'button');
        $buttonClose->setAttribute('class',         'close');
        $buttonClose->setAttribute('aria-label',    'Close');
        $buttonClose->setAttribute('data-dismiss',  'modal');

        $spanClose = $this->_dom->createElement('span');
        $spanClose->setAttribute('aria-hidden', 'true');

        $txtClose = $this->_dom->createTextNode('×');

        $spanClose->appendChild($txtClose);
        $buttonClose->appendChild($spanClose);

        //////////////////////////////////////////////////////////
        // Title
        $h4 = $this->_dom->createElement('h4');
        $h4->setAttribute('id', $this->_idModal . '_modalTitle');
        $h4->setAttribute('class', 'modal-title');

        $h4Txt = $this->_dom->createTextNode( $this->_modalTitle );

        $h4->appendChild($h4Txt);

        $modalHeader->appendChild($buttonClose);
        $modalHeader->appendChild($h4);

        //////////////////////////////////////////////////////////
        // Body
        $modalBody = $this->_dom->createElement('div');
        $modalBody->setAttribute('class', 'modal-body');

        $p1Body = $this->_dom->createElement('p');
        $p1Body->setAttribute('id', $this->_idModal . '_P1');

        $p1BodyTxt = $this->_dom->createTextNode( $this->_P1txt );
        $p1Body->appendChild($p1BodyTxt);

        $p2Body = $this->_dom->createElement('p');
        $p2Body->setAttribute('id', $this->_idModal . '_P2');

        $p2BodyTxt = $this->_dom->createTextNode( $this->_P2txt );
        $p2Body->appendChild($p2BodyTxt);

        $modalBody->appendChild($p1Body);
        $modalBody->appendChild($p2Body);

        //////////////////////////////////////////////////////////
        // Footer
        $modalFooter = $this->_dom->createElement('div');
        $modalFooter->setAttribute('class', 'modal-footer');

        //////////////////////////////////////////////////////////
        // Champs input hidden
        $inputTableBDD = $this->_dom->createElement('input');
        $inputTableBDD->setAttribute('type', 'hidden');
        $inputTableBDD->setAttribute('id', $this->_idModal . '_InputTableBDD');

        $inputIdBDD = $this->_dom->createElement('input');
        $inputIdBDD->setAttribute('type', 'hidden');
        $inputIdBDD->setAttribute('id', $this->_idModal . '_InputIdBDD');

        //////////////////////////////////////////////////////////
        // Bouton annuler
        $buttonCancel = $this->_dom->createElement('button');
        $buttonCancel->setAttribute('type',         'button');
        $buttonCancel->setAttribute('class',        'btn btn-default');
        $buttonCancel->setAttribute('data-dismiss', 'modal');

        $buttonCancelTxt = $this->_dom->createTextNode('Annuler');
        $buttonCancel->appendChild($buttonCancelTxt);

        //////////////////////////////////////////////////////////
        // Bouton d'action
        $buttonAction = $this->_dom->createElement('button');
        $buttonAction->setAttribute('id',           $this->_idModal . '_ButtonAction');
        $buttonAction->setAttribute('type',         'button');
        $buttonAction->setAttribute('class',        'btn btn-' . $this->_buttonActionClass );
        $buttonAction->setAttribute('data-dismiss', 'modal');

        $buttonActionTxt = $this->_dom->createTextNode( $this->_buttonActionTxt );
        $buttonAction->appendChild($buttonActionTxt);

        $modalFooter->appendChild($inputTableBDD);
        $modalFooter->appendChild($inputIdBDD);
        $modalFooter->appendChild($buttonCancel);
        $modalFooter->appendChild($buttonAction);

        $modalContent->appendChild($modalHeader);
        $modalContent->appendChild($modalBody);
        $modalContent->appendChild($modalFooter);

        $modalContent->appendChild($modalFooter);
        $modalDialog->appendChild($modalContent);
        $modal->appendChild($modalDialog);

        $this->_dom->appendChild($modal);
    }


    /**
     * Permet d'insérer du code dans la page
     */
    protected function append($element)
    {

    }


    /**
     * Retourne le code HTML généré par la page
     */
    public function getDom()
    {
        return $this->_dom;
    }
}
