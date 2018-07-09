<?php
namespace widget;

/**
 * Gestion des accès aux pages du Back-office
 *
 * @author Daniel Gomes
 */
class widget
{
    /**
     * Attribut
     */
    private $_dbh;              // Instance PDO
    private $_dom;              // Gestion en dom du code généré


    /**
     * Constructeur
     */
    public function __construct()
    {
        // Initialisation DOMDocument
        $this->_dom = new \DOMDocument("1.0", "utf-8");

        // Instance PDO
		$this->_dbh = \core\dbSingleton::getInstance();

        // Création de la zone et appel des widgets
        $this->init();
    }


    /**
     * Initialisation des Widgets
     */
    private function init()
    {
        $container = $this->_dom->createElement('div');
        $container->setAttribute('class', 'widget');

        $tr1 = $this->_dom->createElement('div');
        $tr1->setAttribute('class', 'row');

        $cell1 = $this->_dom->createElement('div');
        $cell1->setAttribute('id', 'widgetLine1');
        $cell1->setAttribute('class', 'col-sm-12 widget-icon line1');

        $tr1->appendChild($cell1);

        $tr2 = $this->_dom->createElement('div');
        $tr2->setAttribute('class', 'row');

        $cell2 = $this->_dom->createElement('div');
        $cell2->setAttribute('id', 'widgetLine2');
        $cell2->setAttribute('class', 'col-sm-12 line2');

        $tr2->appendChild($cell2);

        $container->appendChild($tr1);
        $container->appendChild($tr2);

        $this->_dom->appendChild($container);

        // Chargement des widget
        $this->loadWiget();

        // Chargement du bouton de déconnexion
        $logOut = $this->_dom->createElement('div');
        $logOut->setAttribute('class', 'glyphicon glyphicon-off widget-icon');
        $logOut->setAttribute('onclick', "document.location.href = '/';");
        $cell1->appendChild($logOut);

        // Récupération du nom de la personne connecté
        $req = "SELECT nom, prenom FROM users WHERE id = :id";
        $sql = $this->_dbh->prepare($req);
        $sql->execute( array( ':id'=>$_SESSION['auth']['id'] ));

        if ($sql->rowCount() > 0) {
            $res = $sql->fetch();

            $userName = $this->_dom->createTextNode($res->prenom . ' ' . strtoupper($res->nom));
            $cell2->appendChild($userName);
        }
    }


    /**
     * Récupération des widgets additionnels affectés à l'utilisateur
     */
    private function loadWiget()
    {

    }


    /**
     * Récupération du nom de l'utisateur connecté
     */
    private function userName()
    {

    }


    public function getDom() {
        return $this->_dom;
    }
}
