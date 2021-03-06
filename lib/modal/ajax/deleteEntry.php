<?php
require_once __DIR__ . '/../../../../../../bootstrap.php';

header('Content-Type: text/html; charset=UTF-8');

use core\dbSingleton;

if (!empty($_POST['bddName'])) {
    $dbh = dbSingleton::getInstance($_POST['bddName']);
} else {
    $dbh = dbSingleton::getInstance();
}

// On bloque le script si un bot venait à appeler le fichier ajax ------------------------------
if (count($_POST)==0)	die();
// ---------------------------------------------------------------------------------------------


// test ----------------------------------------------------------------------------------------
// echo json_encode($_POST);
// die();
// ---------------------------------------------------------------------------------------------

$req = "DELETE FROM " . $_POST['table'] . " WHERE id=:id";
$sql = $dbh->prepare($req);
$sql->execute(array( ':id' => $_POST['id'] ));

echo json_encode(array('result'=>'true'));
