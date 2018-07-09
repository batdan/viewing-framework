<?php
/**
 * Tableau de la liste des groupe d'utilisateurs
 */
require_once ( __DIR__ . '/../../../../bootstrap.php' );

// Titre de la page
$title = 'Liste des groupe d\'utilisateurs';

// Instance PDO
$dbh = \core\dbSingleton::getInstance();

// Option du Bootstrap Table
$options = array(
            'id'                            => 'groupTable',
            'urlAdd'                        => '/modules/admin/access/groupEdit.php',
            'title'                         => $title,
            'data-url'                      => '/modules/admin/access/inc/groupListTable.php',
            'width'                         => '700px',
            'data-pagination'               => 'true',

            'req'                           => "SELECT id, name FROM users_group ORDER BY name",

            'fields'                        => array(
                                                    array(
                                                        'name'      => 'actions',
                                                        'label'     => 'Actions',
                                                        'sortable'  => false,
                                                        'width'     => '81',
                                                        'style'     => 'padding:0;',
                                                        'csv'       => 'false',
                                                    ),
                                                    array(
                                                        'name'      => 'id',
                                                        'label'     => 'id',
                                                        'width'     => '80',
                                                    ),
                                                    array(
                                                        'name'      => 'name',
                                                        'label'     => 'Groupe',
                                                    ),
                                            ),

            'jsonModifier'                  => function(&$row) {

                                                    // Modification de la colonne 'Actions'
                                                    $buttonsAction = new table\buttonsAction();
                                                    $buttonsAction->setIdModal('groupModal');
                                                    $buttonsAction->setButtonEdit('/modules/admin/access/groupEdit.php?id=' . $row['id'] );
                                                    //$buttonsAction->setButtonDelete($row, 'users_group');
                                                    $row['actions'] = $buttonsAction->getButtonsGroup();

                                                    return $row;
                                            },

            'csvModifier'                   => function(&$row) {
                                                    return $row;
                                            },
            );

// Rendu
$table = new table\sql($options);
return $table->rendu();
