<?php
require_once __DIR__ . '/../../../../../../bootstrapNoAuth.php';

// header('Content-Type: text/html; charset=UTF-8');

// Instance PDO
$dbh = core\dbSingleton::getInstance();


// On bloque le script si un bot venait à appeler le fichier ajax ------------------------------
if (count($_POST)==0)	die();
// ---------------------------------------------------------------------------------------------


// test ----------------------------------------------------------------------------------------
//echo json_encode($_POST);
//die();
// ---------------------------------------------------------------------------------------------


// Données postées -----------------------------------------------------------------------------
$values = json_decode($_POST['values']);

$idFiche= $values->idFiche;                         // Identifiant de la fiche impactée
$tableL = $values->tableL;                          // Table de liaison
$table2 = $values->table2;                          // Table liée
$idChp  = $values->idChp;                           // Id champ
$valChp = str_replace(" ", "", $values->valChp);    // Valeurs retournées
// ---------------------------------------------------------------------------------------------


// On vérifie s'il y a une différence entre les données postées et celles sauvegardées ---------
$req = "SELECT " . $tableL->cle_2 . " FROM " . $tableL->table . " WHERE " . $tableL->cle_1 . " = :idFiche ORDER BY " . $tableL->cle_2 . " ASC";
$sql = $dbh->prepare($req);
$sql->execute( array( ':idFiche' => $idFiche ) );

$listeCle2 = array();
while ($res = $sql->fetch()) {
    $listeCle2[] = $res->{$tableL->cle_2};
}
$listeCle2 = implode(",", $listeCle2);

if ($listeCle2 != $valChp) {

    // Suppression des entrées pour cet id
    $req = "DELETE FROM " . $tableL->table . " WHERE " . $tableL->cle_1 . " = :idFiche ";
    $sql = $dbh->prepare($req);
    $sql->execute( array( ':idFiche'=>$idFiche ));

    // Ajout des nouvelles entrées

    $valChpArray = explode(",", $valChp);

    if (count($valChpArray) > 0) {
        $req = "INSERT INTO " . $tableL->table . " (" . $tableL->cle_1 . ", " . $tableL->cle_2 . ") VALUES (:idFiche, :cle_2)";
        $sql = $dbh->prepare($req);
        foreach($valChpArray as $cle_2_val) {
            $sql->execute( array( ':idFiche'=>$idFiche, ':cle_2'=>$cle_2_val ));
        }
    }
    echo $idChp . ' : les valeurs ont changée, on sauvegarde - values : ' . $valChp;
} else {
    echo $idChp . ' : les valeurs n\'ont pas changées';
}
// ---------------------------------------------------------------------------------------------
