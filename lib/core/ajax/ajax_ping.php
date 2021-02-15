<?php
require_once __DIR__ . '/../../../../../../bootstrap.php';

header('Content-Type: application/json');
http_response_code(200);

// Sécurité ------------------------------------------------------------------------------------
if (count($_POST)==0)	die();
// ---------------------------------------------------------------------------------------------


// test ----------------------------------------------------------------------------------------
// print_r($_POST);
// ---------------------------------------------------------------------------------------------

if ($_POST['action'] == 'ping') {

    $uri     = $_POST['uri'];
    $id_user = $_SESSION['auth']['id'];

    // Instance PDO
    $dbh = \core\dbSingleton::getInstance();

    // Suppression des verrouillages obsolètes
    $req = "DELETE FROM form_lock WHERE (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(date_lock)) > 185";
    $sql = $dbh->query($req);

    // Ajout ou Maj du verrouillage du formulaire par l'utilisateur
    $req = "SELECT id, id_user, date_lock FROM form_lock WHERE uri = :uri";
    $sql = $dbh->prepare($req);
    $sql->execute( array( ':uri'=>$uri ));

    if ($sql->rowCount() > 0) {

        $res = $sql->fetch();

        if ($res->id_user == $id_user) {

            $req = "UPDATE form_lock SET date_lock = NOW() WHERE uri = :uri";
            $sql = $dbh->prepare($req);
            $sql->execute( array( ':uri'=>$uri ));

        }
    }

    echo json_encode( array( 'datetime'=>date('Y-m-d H:i:s') ));
}
