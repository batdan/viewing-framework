<?php
namespace core;

/**
 * Gestion des accès aux pages du Back-office
 *
 * @author Daniel Gomes
 */
class privilege
{
    /**
     * On vérifie s'il y a bien un utilisateur connecté
     * Dans le cas contraire, retour à l'authentification
     */
    public static function checkLogin()
    {
        if ($_SERVER['REQUEST_URI'] != '' && $_SERVER['REQUEST_URI'] != '/' && $_SERVER['REQUEST_URI'] != '/lib/auth/ajax/ajax_auth.php' && !isset($_SESSION['auth']['id'])) {

            $_SESSION['page_lost'] = $_SERVER['REQUEST_URI'];

            header('Location: /');
        }
    }

    /**
     * Test l'autorisation d'accès sur une Url
     * Acces refusé = retour au dashboard
     */
    public static function checkPrivilege($id_page)
    {

    }
}
