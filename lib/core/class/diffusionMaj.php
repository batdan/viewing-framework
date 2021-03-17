<?php
namespace core;

/**
 * Création et mise à jour de la table de diffusion des projet
 * Permet de créer la table contenant le linking des villes
 *
 * @author Daniel Gomes
 */
class diffusionsMaj implements \Jenner\SimpleFork\Runnable
{
    /**
    * process entry
    *
    * @return mixed
    */
    public function run()
    {
        // Instance PDO
        $dbh = dbSingleton::getInstance();

        // Récupération des projets à mettre à jour
        $req = "SELECT      id, nom_code, cron_maj

                FROM        projects

                WHERE       activ 		= 1
                AND         cron_check  = 0
                AND         publi_deb 	IS NOT NULL AND publi_deb 	<= CURDATE()
                AND         publi_fin 	IS NOT NULL AND publi_fin 	>= CURDATE()
                AND         objectif 	IS NOT NULL AND objectif 	<> ''
                AND         courbe 		IS NOT NULL AND courbe 		<> ''
                AND         coeff 		IS NOT NULL AND coeff 		<> ''

                LIMIT       1";

        $sql = $dbh->query($req);

        if ($sql->rowCount() > 0) {

            $res = $sql->fetch();

            if ($res->cron_maj == 0) {

                // passage du cron_maj à 1
                $req2 = "UPDATE projects SET cron_check = 1, cron_maj = 1 WHERE id = :id";
                $sql2 = $dbh->prepare($req2);
                $sql2->execute( array( ':id'=>$res->id ));

                // Hydratation de la classe
                $hydrate = array( 'nomProject'=>$res->nom_code );

                // Mise à jour du projet
                new diffusion($hydrate);

            } else {

                // passage du cron_check à 1
                $req2 = "UPDATE projects SET cron_check = 1 WHERE id = :id";
                $sql2 = $dbh->prepare($req2);
                $sql2->execute( array( ':id'=>$res->id ));
            }

        } else {

            echo 'Aucune mise à jour de diffusion à effectuer' . chr(10);
        }

        // Close instance PDO
        dbSingleton::closeInstance();
    }


    public static function countDiff()
    {
        // Instance PDO
        $dbh = dbSingleton::getInstance();

        // On récupère le nombre exact de table de diffusion à créer ou checker
        $req = "SELECT      COUNT(id) AS count_id

                FROM        projects

                WHERE       activ 		= 1
                AND         publi_deb 	IS NOT NULL AND publi_deb 	<= CURDATE()
                AND         publi_fin 	IS NOT NULL AND publi_fin 	>= CURDATE()
                AND         objectif 	IS NOT NULL AND objectif 	<> ''
                AND         courbe 		IS NOT NULL AND courbe 		<> ''
                AND         coeff 		IS NOT NULL AND coeff 		<> ''";

        $sql = $dbh->query($req);
        $res = $sql->fetch();

        echo chr(10);
        echo $res->count_id;
        echo chr(10);
        echo chr(10);




        // Close instance PDO
        dbSingleton::closeInstance();

        return $res->count_id;
    }


    public static function checkDiff()
    {
        // Instance PDO
        $dbh = dbSingleton::getInstance();

        $req = "SELECT statut FROM cron WHERE name = :name";
        $sql= $dbh->prepare($req);
        $sql->execute( array( ':name'=>'diffusion' ));

        if ($sql->rowCount() > 0) {

            $res = $sql->fetch();

            if ($res->statut == 1) {
                return true;
            }
        }

        return false;

        // Close instance PDO
        dbSingleton::closeInstance();
    }


    public static function initMajDiffusion()
    {
        // Instance PDO
        $dbh = dbSingleton::getInstance();

        // On vérifie si une entrée est présente pour la gestion des diffusion
        $req = "SELECT id FROM cron WHERE name = :name";
        $sql= $dbh->prepare($req);
        $sql->execute( array( ':name'=>'diffusion' ));

        // Passage du statut du cron 'diffusion' a 1
        if ($sql->rowCount() > 0) {
            $req2 = "UPDATE cron SET statut = 1, cron_deb = NOW(), cron_fin = NULL WHERE name = :name";
        } else {
            $req2 = "INSERT INTO cron (name, statut, cron_deb, cron_fin) VALUES (:name, 1, NOW(), NULL)";
        }

        $sql2 = $dbh->prepare($req2);
        $sql2->execute( array( ':name'=>'diffusion' ));

        // Close instance PDO
        dbSingleton::closeInstance();
    }


    public static function endMajDiffusion()
    {
        // Instance PDO
        $dbh = dbSingleton::getInstance();

        // Passage du statut du cron 'diffusion' a 1
        $req = "UPDATE cron SET statut = 0, cron_fin = NOW() WHERE name = :name";
        $sql = $dbh->prepare($req);
        $sql->execute( array( ':name'=>'diffusion' ));

        // Close instance PDO
        dbSingleton::closeInstance();
    }


    public static function cleanProject()
    {
        // Instance PDO
        $dbh = dbSingleton::getInstance();

        // Passage du cron_check et cron_maj à 0
        $req = "UPDATE projects SET cron_check = 0, cron_maj = 0";
        $sql = $dbh->query($req);

        // Close instance PDO
        dbSingleton::closeInstance();
    }
}
