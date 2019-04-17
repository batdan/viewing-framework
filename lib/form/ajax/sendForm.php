<?php
require_once __DIR__ . '/../../../../../../bootstrap.php';

// header('Content-Type: text/html; charset=UTF-8');


// On bloque le script si un bot venait à appeler le fichier ajax ------------------------------
if (count($_POST)==0)	die();
// ---------------------------------------------------------------------------------------------

$dbh = core\dbSingleton::getInstance($_POST['bddName']);

// PDO : begin transaction
$dbh->beginTransaction();


// test ----------------------------------------------------------------------------------------
// echo json_encode($_POST);
// die();
// ---------------------------------------------------------------------------------------------

// Champ spéciaux
$chpSpeciaux = array(
    'bddName',
    'formName',
    'currentPage',
    'actionForm',
    'tableBDD',
    'clePrimaireName',
    'clePrimaireId',
    'dateCreaNameBDD',
    'dateModifNameBDD',
    'curlInSave',
    'curlAfterSave',
    'listeSaveModifier'
);

// Récupération des champs
$chps = array();
foreach ($_POST as $k => $v) {
    if (! in_array($k, $chpSpeciaux)) {
        $chps[$k] = $v;
    }
}

// Transformation des boolean (false / true) en 0/1
foreach ($chps as $k => $v) {
    if ($v == 'true')   { $chps[$k] = 1; }
    if ($v == 'false')  { $chps[$k] = 0; }
}

///////////////////////////////////////////////////////////////////////////
// Récupération des modifiers SAVE
$saveModifier = json_decode($_POST['listeSaveModifier']);

// On recréé le formulaire pour appliquer les 'saveModifier'
if (count($saveModifier) > 0) {

    $saveModifier = get_object_vars($saveModifier);

    $form = new form\form();
    $form->setBddName($_POST['bddName']);
    $form->setTable($_POST['tableBDD']);
    $form->setClePrimaireId($_POST['clePrimaireId']);
    $form->setListChamps($chps);

    // On commence par charger les champs sans modifier
    $saveChpsModifier = array();
    foreach ($saveModifier as $k => $v) {

        $elem = new $v($form, null, $k);                                // On recréé les elements modifiés
        $chpsModifier = $elem->save($form->getListChampsSaveForm());    // Récupération du save($data) de la méthode
        $saveChpsModifier[$k] = $chpsModifier[$k];                      // Sauvegarde de l'élément dans un tableau provisoire
    }

    // Reprise de la liste des champs et de leurs valeurs en surchargeant les élements modifiés
    $chps = array_merge($chps, $saveChpsModifier);
}
///////////////////////////////////////////////////////////////////////////


if ($_POST['actionForm'] == 'save'  ||  $_POST['actionForm'] == 'saveExit') {

    // Nouvelle entrée
    if (empty($_POST['clePrimaireId'])) {

        // Possibilité d'utiliser une table sans champ de type : date_crea
        $insertChpDateCreate = '';
        $insertValDateCreate = '';

        if (!empty($_POST['dateCreaNameBDD'])) {
            $insertChpDateCreate = ", " . $_POST['dateCreaNameBDD'];
            $insertValDateCreate = ", NOW()";
        }

        // Possibilité d'utiliser une table sans champ de type : date_modif
        $insertChpDateModif = '';
        $insertValDateModif = '';

        if (!empty($_POST['dateModifNameBDD'])) {
            $insertChpDateModif = ", " . $_POST['dateModifNameBDD'];
            $insertValDateModif = ", NOW()";
        }

        $req  = "INSERT INTO " . $_POST['tableBDD'];
        $req .= " (" . implode(', ', array_keys($chps)) . $insertChpDateCreate . $insertChpDateModif . ")";
        $req .= " VALUES";
        $req .= " (:" . implode(', :', array_keys($chps)) . $insertValDateCreate . $insertValDateModif . ")";

        $sql = $dbh->prepare($req);

        try {
            $sql->execute($chps);

            // Redirection -> on ajout l'id en variable GET
            $splitGet= explode('?', $_POST['currentPage']);
            if (count($splitGet) > 1) {
                $page = $splitGet[0];
                $uri  = $splitGet[1];
            } else {
                $page = $splitGet[0];
                $uri  = '';
            }
            $listGet = explode('&', $uri);
            $newGet  = array();
            foreach ($listGet as $get) {
                if ($get != '') {
                    $newGet[] = $get;
                }
            }
            $newGet[]       = 'id=' . $dbh->lastInsertId();
            $result['redir']= $page . '?' . implode('&', $newGet);
            $result['req']  = 'INSERT';

            $result['ok']   = 1;
            $result['text'] = 'Update completed';
        } catch(Exception $e) {
            $result['ok']   = 0;
            $result['text'] = $e->getMessage();
        }

    // Mise à jour d'une entrée
    } else {

        $chpUpdate = array();
        foreach ($chps as $k => $v) {
            $chpUpdate[] = $k . " = :" . $k;
        }

        $req  = "UPDATE " . $_POST['tableBDD'] . " SET" . chr(10);

        // Possibilité d'utiliser une table sans champ de type : date_modif
        if (!empty($_POST['dateModifNameBDD'])) {
            $req .= $_POST['dateModifNameBDD'] . " = NOW()," . chr(10);
        }

        $req .= implode(',' . chr(10), $chpUpdate) . chr(10);
        $req .= "WHERE " . $_POST['clePrimaireName'] . " = :id";

        $sql  = $dbh->prepare($req);

        try {
            $sql->execute( array_merge($chps, array('id'=>$_POST['clePrimaireId'])) );

            $result['redir']= $_POST['currentPage'];
            $result['req']  = 'UPDATE';
            $result['ok']   = 1;
            $result['text'] = 'Update completed';
        } catch(Exception $e) {
            $result['ok']   = 0;
            $result['text'] = $e->getMessage();
        }
    }

} else {

    // Suppression d'une entrée en base de données
    if ($_POST['actionForm'] == 'trash') {

        // Suppression d'une entrée
        $req = "DELETE FROM " . $_POST['tableBDD'] . " WHERE " . $_POST['clePrimaireName'] . " = :id";
        $sql = $dbh->prepare($req);

        try {
            $sql->execute( array('id'=>$_POST['clePrimaireId']) );
            $result['ok']   = 1;
            $result['text'] = 'Deleted';
        } catch(Exception $e) {
            $result['ok']   = 0;
            $result['text'] = $e->getMessage();
        }
    }

    if ($_POST['actionForm'] == 'exit') {
        $result['ok'] = 1;
    }
}

$result['actionForm']   = $_POST['actionForm'];


// PDO : commit transaction
$dbh->commit();

if ($_POST['actionForm'] == 'save'  ||  $_POST['actionForm'] == 'saveExit') {

    ////////////////////////////////////////////////////////////////////////////////////////
    // Méthode à la sauvegarde
    // On applique les hacks CURL à la sauvegarde (classe : form->_curlInSave)
    // Les appels se font en POST Curl
    if (isset($_POST['curlInSave'])) {

        $curlInSave = $_POST['curlInSave'];
        if (count($curlInSave) > 0) {

            // On retourne les résultats des appels Curl en JSON
            $checkCurlInSave = array();

            foreach ($curlInSave as $k=>$v) {
                $curl = curl_init();

                curl_setopt($curl, CURLOPT_URL,             $v['urlInc']);

                curl_setopt($curl, CURLOPT_FAILONERROR,     true);
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION,  true);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER,  true);
                curl_setopt($curl, CURLOPT_HEADER,          false);
                curl_setopt($curl, CURLOPT_POST,            true);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,  false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,  false);

                curl_setopt($curl, CURLOPT_POSTFIELDS,      array( 'values' => json_encode($v['values'] )));

                $checkCurlInSave[] = curl_exec($curl);

                curl_close($curl);
            }

            $result['curlInSave'] = $checkCurlInSave;
        }
    }
    ////////////////////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////////////////////
    // Méthode après la sauvegarde
    // On applique les hacks CURL après la sauvegarde (classe : form->_curlAfterSave)
    // Les appels se font en POST Curl
    if (isset($_POST['curlAfterSave'])) {

        $curlAfterSave = $_POST['curlAfterSave'];
        if (count($curlAfterSave) > 0) {

            // On retourne les résultats des appels CURL en JSON
            $checkCurlAfterSave = array();

            foreach ($curlAfterSave as $k=>$v) {
                $curl = curl_init();

                curl_setopt($curl, CURLOPT_URL,             $v['urlInc']);

                curl_setopt($curl, CURLOPT_FAILONERROR,     true);
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION,  true);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER,  true);
                curl_setopt($curl, CURLOPT_HEADER,          false);
                curl_setopt($curl, CURLOPT_POST,            true);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,  false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,  false);

                curl_setopt($curl, CURLOPT_POSTFIELDS,      array( 'values' => json_encode($v['values'] )));

                $checkCurlAfterSave[] = curl_exec($curl);

                curl_close($curl);
            }

            $result['curlAfterSave'] = $checkCurlAfterSave;
        }
    }
    ////////////////////////////////////////////////////////////////////////////////////////
}

echo json_encode($result);
