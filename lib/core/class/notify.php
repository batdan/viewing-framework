<?php
namespace core;

/**
 * Gestion des notifications
 *
 * http://goodybag.github.io/bootstrap-notify/
 *
 * @author Daniel Gomes
 */
class notify
{
    /**
     * Attribut
     */
    private $_notifyDiv;        // Div container de la notification
    private $_dom;              // Gestion en dom du code généré

    public function __construct()
    {
        $this->_dom = new \DOMDocument("1.0", "utf-8");

        $notifyDiv = $this->_dom->createElement('div');
        $notifyDiv->setAttribute('class', 'notifications bottom-right');
        $this->_dom->appendChild($notifyDiv);

        // Chargement des libs js et css
        libIncluderList::add_bootstrapNotify();
    }

    public function getDom() {
        return $this->_dom;
    }
}
