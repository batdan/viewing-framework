<?php
require_once __DIR__ . '/../../../../../../bootstrap.php';

// Sécurité ------------------------------------------------------------------------------------
if (count($_POST)==0)	die();
// ---------------------------------------------------------------------------------------------


// test ----------------------------------------------------------------------------------------
// print_r($_POST);
// ---------------------------------------------------------------------------------------------

if ($_POST['action'] == 'verrouOff') {

    $uri     = $_POST['uri'];
    $id_user = $_SESSION['auth']['id'];

    // Instance PDO
    $dbh = \core\dbSingleton::getInstance();

    // Suppression du verrouillage du formulaire
    $req = "DELETE FROM form_lock WHERE id_user = :id_user AND uri = :uri";
    $sql = $dbh->prepare($req);
    $sql->execute( array( ':id_user'=>$id_user, ':uri'=>$uri ));
}
