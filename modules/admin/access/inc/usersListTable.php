<?php
/**
 * Tableau de la liste des utilisateurs
 */
require_once ( __DIR__ . '/../../../../bootstrap.php' );

// Titre de la page
$title = 'Liste des utilisateurs';

// Instance PDO
$dbh = \core\dbSingleton::getInstance();

// Puces booleans
$on  = '<i class="fa fa-circle" aria-hidden="true" style="color:#0eb04f"></i>';
$off = '<i class="fa fa-circle" aria-hidden="true" style="color:#d41414"></i>';

// Récupération des groupes auquel est rattaché cet utilisateur
$req = "SELECT      a.name
        FROM        users_group a
        INNER JOIN  users_group_link b
        ON          a.id = b.id_group
        WHERE       b.id_user = :id_user";
$sql = $dbh->prepare($req);


// Option du Bootstrap Table
$options = array(
            'id'                            => 'usersTable',
            'urlAdd'                        => '/modules/admin/access/usersEdit.php',
            'title'                         => $title,
            'data-url'                      => '/modules/admin/access/inc/usersListTable.php',
            'width'                         => '100%',
            'data-pagination'               => 'true',

            'req'                           => "SELECT id, prenom, nom, login, activ FROM users ORDER BY prenom",

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
                                                        'name'      => 'activ',
                                                        'label'     => 'Actif',
                                                    ),
                                                    array(
                                                        'name'      => 'prenom',
                                                        'label'     => 'Prénom',
                                                    ),
                                                    array(
                                                        'name'      => 'nom',
                                                        'label'     => 'Nom',
                                                    ),
                                                    array(
                                                        'name'      => 'login',
                                                        'label'     => 'Identifiant',
                                                    ),
                                                    array(
                                                        'name'      => 'groups',
                                                        'label'     => 'Groupes',
                                                        'sortable'  => false,
                                                        'width'     => '80',
                                                        'csv'       => 'false',
                                                    ),
                                            ),

            'jsonModifier'                  => function(&$row) use ($sql, $on, $off) {

                                                    // Modification de la colonne 'Actions'
                                                    $buttonsAction = new table\buttonsAction();
                                                    $buttonsAction->setIdModal('usersModal');
                                                    $buttonsAction->setButtonEdit('/modules/admin/access/usersEdit.php?id=' . $row['id'] );
                                                    $buttonsAction->setButtonDelete($row, 'users');
                                                    $row['actions'] = $buttonsAction->getButtonsGroup();

                                                    // Utilisateur actif ?
                                                    if ($row['activ'] == 0) {
                                                        $row['activ'] = $off;
                                                    } else {
                                                        $row['activ'] = $on;
                                                    }

                                                    // Récupération des groupes auquel est rattaché cet utilisateur
                                                    $sql->execute( array( ':id_user'=>$row['id'] ));

                                                    if ($sql->rowCount() > 0) {

                                                        $listGroup = array();
                                                        while ($res = $sql->fetch()) {

                                                            $listGroup[] = $res->name;
                                                        }
                                                        $listGroup = implode(", ", $listGroup);

                                                        $row['groups'] = '<i class="fa fa-users" style="cursor:default;" title="'.$listGroup.'"></i>';

                                                    } else {

                                                        $row['groups'] = '';
                                                    }



                                                    return $row;
                                            },

            'csvModifier'                   => function(&$row) {
                                                    return $row;
                                            },
            );

// Rendu
$table = new table\sql($options);
return $table->rendu();
