<?php
require_once ( __DIR__ . '/../../bootstrap.php' );

// Titre de la page
$title = 'Exemple Bootstrap Table Array';

// Requête pour collecter un tableau de données (test)
$dbh = \core\dbSingleton::getInstance();

$req = "SELECT id, nom, prenom, statut FROM rh_salaries";
$sql = $dbh->query($req);
$dataSet = $sql->fetchAll();

// Option du Bootstrap Table
$options = array(
            'id'                            => 'myTable',                       // id du tableau                                    facultatif          'table'
            'dataSet'                       => $dataSet,                        // Requête initiale                                 obligatoire         null
            'urlAdd'                        => '/exemples/editArray.php',            // Url du formulaire                                facultatif          null
            'title'                         => $title,                          // Titre du tableau                                 facultatif          null
            'width'                         => '1000px',                        // Forcer la largeur du tableau                     facultatif          100%
            'data-url'                      => 'inc/list-array-conf.php',           // Chemin vers le flux json                         obligatoire         null
            'data-page-size'                => '10',                            // Nb de résultat / page au charchement             facultatif          1000px
            'data-show-columns'             => 'true',                          // Bouton : activer/désactiver des colonnes         facultatif          'true'
            'data-show-refresh'             => 'true',                          // Bouton : refresh                                 facultatif          'true'

            'fields'                        => array(                           // Liste des champs et conf d'affichage             obligatoire         null
                                                    array(
                                                        'name'      => 'actions',
                                                        'label'     => 'Actions',
                                                        'sortable'  => false,
                                                        'halign'    => 'center',
                                                        'align'     => 'center',
                                                        'width'     => '81',
                                                        'style'     => 'padding:0;',
                                                        'csv'       => 'false',
                                                    ),
                                                    array(
                                                        'name'      => 'id',
                                                        'label'     => 'id',
                                                        'sortable'  => true,
                                                        'halign'    => 'center',
                                                        'align'     => 'center',
                                                    ),
                                                    array(
                                                        'name'      => 'nom',
                                                        'label'     => 'Nom',
                                                        'sortable'  => true,
                                                        'halign'    => 'center',
                                                        'align'     => 'center',
                                                    ),
                                                    array(
                                                        'name'      => 'prenom',
                                                        'label'     => 'Prénom',
                                                        'sortable'  => true,
                                                        'halign'    => 'center',
                                                        'align'     => 'center',
                                                    ),
                                                    array(
                                                        'name'      => 'statut',
                                                        'label'     => 'Statut',
                                                        'sortable'  => true,
                                                        'halign'    => 'center',
                                                        'align'     => 'center',
                                                        'visible'   => 'false',
                                                    )
                                            ),

            'jsonModifier'                  => function(&$row) {                // Modifieur pour l'affichage dans le tableau       facultatif          null

                                                    $row['nom'] = strtoupper($row['nom']);

                                                    // Modification de la colonne 'Actions'
                                                    $buttonsAction = new table\buttonsAction();
                                                    $buttonsAction->setIdModal('myModal');
                                                    $buttonsAction->setButtonEdit('/exemples/editArray.php?id=' . $row['id'] );
                                                    // $buttonsAction->setButtonDelete($row, 'rh_salaries');
                                                    $row['actions'] = $buttonsAction->getButtonsGroup();

                                                    return $row;
                                                },

            'csvModifier'                   => function(&$row) {                // Modifieur pour l'export CSV                      facultatif          null
                                                    $row['actions'] = 'bla ' . $row['id'];
                                                    $row['id'] .= ' test';

                                                    return $row;
                                                },
            );

// Rendu
$tableClass = new table\data($options);
return $tableClass->rendu();
