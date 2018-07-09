<?php
namespace core;

/**
 * Authentification
 *
 * @author Daniel Gomes
 */
class auth
{
    /**
     * Attributs
     */
    private $_login;
    private $_pass;

    private $_dom;


    public function init()
    {
        // JS & CSS
        libIncluderList::add_vwAuth();

        // Nom du projet
        $project = config::getConfig('project');
        $projectName = $project['name'];

        // Début DOM
        $this->_dom = new \DOMDocument("1.0", "utf-8");

        // Container
        $container = $this->_dom->createElement('div');
        $container->setAttribute('class', 'form container');

        // Marque
        $brand = $this->_dom->createElement('div');
        $brand->setAttribute('class', 'brand');

        $brandIcon = $this->_dom->createElement('div');
        $brandIcon->setAttribute('class', 'brand-icon fa fa-clone');

        $brandTxt = $this->_dom->createTextNode($projectName);

        $brand->appendChild($brandIcon);
        $brand->appendChild($brandTxt);

        $container->appendChild($brand);

        // Formulaire
        $form = $this->_dom->createElement('form');
        $form->setAttribute('id', 'auth');
        $form->setAttribute('method', 'post');

        $inputLogin = $this->_dom->createElement('input');
        $inputLogin->setAttribute('type', 'text');
        $inputLogin->setAttribute('id', 'login');
        $inputLogin->setAttribute('class', 'col-sm-12');

        $inputPass = $this->_dom->createElement('input');
        $inputPass->setAttribute('type', 'password');
        $inputPass->setAttribute('id', 'pass');
        $inputPass->setAttribute('class', 'col-sm-12');

        $submit = $this->_dom->createElement('button');
        $submit->setAttribute('type', 'submit');
        $submit->setAttribute('class', 'btn btn-danger col-sm-5 pull-right');

        $submitTxt = $this->_dom->createTextNode('Connexion');
        $submit->appendChild($submitTxt);

        // Messages liés à la connexion
        $message = $this->_dom->createElement('div');
        $message->setAttribute('id', 'message');
        $message->setAttribute('class', 'col-sm-12 message');

        $form->appendChild($inputLogin);
        $form->appendChild($inputPass);
        $form->appendChild($submit);
        $form->appendChild($message);

        $container->appendChild($form);

        $this->_dom->appendChild($container);
    }


    /**
     * Retourne le code HTML généré par la page
     */
    public function rendu()
    {
        $this->_dom->formatOutput = true;
        return $this->_dom->saveHTML();
    }
}
