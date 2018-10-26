<?php
namespace core;

/**
 * Créé la table de diffusion d'un projet
 * Permet de créer la table contenant le linking des villes
 *
 * @author Daniel Gomes
 */
class diffusion
{
    /**
     * Attributs
     */
    private $_dbh;                              // Instance PDO - base de données 'viewing'
    private $_dbh_diff;                         // Instance PDO - base de données 'diffusion'

    private $_debug = false;
    private $_debugRes = array();

	private $_prefixeTables = 'diffusion_';
    private $_nomProject;                       // Nom de code du projet

    private $_infosDiffusion;                   // Récupération des informations sur la diffusion encours (objectifs à date, tableau complet)
    private $_objectifJour;                     // Objectif du jour


    /**
     * Constructeur
     */
	public function __construct(array $data)
	{
        // Instance PDO 'viewing'
		$this->_dbh = dbSingleton::getInstance();

        // Instance PDO 'diffusion'
		$this->_dbh_diff = dbSingleton::getInstance('diffusion');

        // Hydratation de la classe
        foreach ($data as $k=>$v)
        {
            $method = 'set'.ucfirst($k);

            if (method_exists($this, $method)) {
                $this->$method($v);
            }
        }

        // Timer : debut
        $dateDeb = new \DateTime();

        // Etape 1 : infos diffusion
        $this->infosDiffusion();

        // Etape 2 : Création de la table de diffusion si nécessaire
        $this->creaTableDiffusion();

        // Etape 3 : Vérification atteinte de l'objectif du jour
        $maxRang = $this->maxRang();

        // Etape 4 : Récupération du scope
        $this->checkObjectifJour();

        if ($this->_debug) {
            $this->_debugRes[] = 'Max rang : ' . $maxRang;
            $this->_debugRes[] = 'objectif : ' . $this->_objectifJour;
        }

        // Si l'objectif du jour n'est pas atteint, on lance la mise à jour
        if ($maxRang < $this->_objectifJour) {

            // Etape 5 : Création de la table de diffusion temporaire
            $this->creaTableDiffusionTmp();

            // Etape 6 : Création des données
            $this->creaDataTableDiffusion();
        }

        // Timer : fin
        $dateFin = new \DateTime();

        // Informations de suivi du déroulement du cron
        $this->affDebug($dateDeb, $dateFin);
	}


    /**
     * Setters
     */
    public function setPrefixeTables($prefixe) {
        $this->_prefixeTables = $prefixe;
    }

    public function setNomProject($nomProjet) {
        $this->_nomProject = $nomProjet;
    }


    /**
     * Récupération de du nombre de ville à diffusées en fonction de la courbe prévisionnelle
     */
    private function infosDiffusion()
    {
        $req = "SELECT 	objectif, publi_deb, publi_fin, courbe, coeff FROM projects WHERE nom_code = :nom_code";
        $sql = $this->_dbh->prepare($req);
        $sql->execute( array( 'nom_code'=>$this->_nomProject ));
        $res = $sql->fetch();

        $this->_infosDiffusion = tools::diffusionProg(
                                                        $res->publi_deb,
                                                        $res->publi_fin,
                                                        $res->courbe,
                                                        $res->objectif,
                                                        $res->coeff
                                                     );
    }


    /**
     * Création de la table de diffusion du projet si elle n'existe pas
     */
    private function creaTableDiffusion()
    {
        $table = $this->_prefixeTables . $this->_nomProject;

        $req = "CREATE TABLE IF NOT EXISTS $table (
                                        		  `rang`                  int(11)         NOT NULL AUTO_INCREMENT,
                                        		  `dns`                   varchar(52)     NOT NULL,
                                        		  `cde_postal`            varchar(7)      NOT NULL,
                                        		  `lat_deg`               float(10,6)     NOT NULL,
                                        		  `long_deg`              float(10,6)     NOT NULL,
                                        		  `rang_villes_proches`   varchar(255)    DEFAULT NULL,
                                        		  PRIMARY KEY (`rang`)
                                        		  )
                ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

        $sql = $this->_dbh_diff->query($req);
    }


    /**
     * Récupération du rang le plus haut dans la table de diffusion
     */
    private function maxRang()
    {
        $table = $this->_prefixeTables . $this->_nomProject;

        $req = "SELECT MAX(rang) AS max_rang FROM $table";
        $sql = $this->_dbh_diff->query($req);
        $res = $sql->fetch();

        return $res->max_rang;
    }

    /**
     * Récupération de l'objectif du jour
     */
    private function checkObjectifJour()
    {
        $diffusionProgrammee = $this->_infosDiffusion['diffusion'];
        $this->_objectifJour = $diffusionProgrammee[$this->_infosDiffusion['nbJoursToday']];
    }


    /**
     * Création de la table de diffusion temporaire
     */
    private function creaTableDiffusionTmp()
    {
        $tableTmp = $this->_prefixeTables . $this->_nomProject . "_tmp";

        $req = "DROP TABLE IF EXISTS $tableTmp;
    			CREATE TABLE IF NOT EXISTS $tableTmp (
                                        			  `rang`                 int(11)         NOT NULL AUTO_INCREMENT,
                                        			  `dns`                  varchar(52)     NOT NULL,
                                        			  `cde_postal`           varchar(7)      NOT NULL,
                                        			  `lat_deg`              float(10,6)     NOT NULL,
                                        			  `long_deg`             float(10,6)     NOT NULL,
                                        			  `rang_villes_proches`  varchar(255)    DEFAULT NULL,
                                        			  PRIMARY KEY (`rang`)
                                                     )
                ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

    	$sql = $this->_dbh_diff->query($req);
    }


    /**
     * Création des nouvelles données de la table de diffusion
     */
    private function creaDataTableDiffusion()
    {
        $table      = $this->_prefixeTables . $this->_nomProject;
        $tableTmp   = $table . "_tmp";

        // Récupération des informations de la ville dans la table des communes
    	$req_ville1 = "SELECT dns, cde_postal, lat_deg, long_deg FROM geo_communes WHERE rang = :rang";
    	$sql_ville1 = $this->_dbh->prepare($req_ville1);

    	// Sauvegarde des villes ayant le scope du jour dans la table temporaire
    	$req_sauv1 = "INSERT INTO $tableTmp (rang, dns, cde_postal, lat_deg, long_deg) VALUES (:rang, :dns, :cde_postal, :lat_deg, :long_deg)";
    	$sql_sauv1 = $this->_dbh_diff->prepare($req_sauv1);

    	// Récupération des informations de la ville dans la table temporaire des villes
    	$req_ville2 = "SELECT lat_deg, long_deg FROM $tableTmp WHERE rang = :rang";
    	$sql_ville2 = $this->_dbh_diff->prepare($req_ville2);

    	// Recherche des 5  villes les plus proches de rang A (rangs 1 à 41)       + de 100 000 habitants
    	// Recherche des 5  villes les plus proches de rang B (rangs 42 à 620)     de 15 000 à 100 000 habitants
    	// Recherche des 10 villes les plus proches de rang C (rangs 620 à scope)  - de 15 000 habitants
    	$req_vp = "(SELECT (((acos(sin((:FROM_LAT*pi()/180)) * sin((lat_deg*pi()/180))+cos((:FROM_LAT*pi()/180))
    		        * cos((lat_deg*pi()/180)) * cos(((:FROM_LONG-long_deg)*pi()/180))))*180/pi())*60*1.1515*1.609344) AS distance, rang, dns
    				FROM $tableTmp
    				WHERE rang < 42
    				ORDER BY distance ASC
    				LIMIT 1,5)

    				UNION

    				(SELECT (((acos(sin((:FROM_LAT*pi()/180)) * sin((lat_deg*pi()/180))+cos((:FROM_LAT*pi()/180))
    				* cos((lat_deg*pi()/180)) * cos(((:FROM_LONG-long_deg)*pi()/180))))*180/pi())*60*1.1515*1.609344) AS distance, rang, dns
    				FROM $tableTmp
    				WHERE rang >= 42 AND rang <= 620
    				ORDER BY distance ASC
    				LIMIT 1,5)

    				UNION

    				(SELECT (((acos(sin((:FROM_LAT*pi()/180)) * sin((lat_deg*pi()/180))+cos((:FROM_LAT*pi()/180))
    		        * cos((lat_deg*pi()/180)) * cos(((:FROM_LONG-long_deg)*pi()/180))))*180/pi())*60*1.1515*1.609344) AS distance, rang, dns
    				FROM $tableTmp
    				WHERE rang > 620
    				ORDER BY distance ASC
    				LIMIT 1,10)";
    	$sql_vp = $this->_dbh_diff->prepare($req_vp);

    	// Sauvegarde des villes dans la table temporaire
    	$req_sauv2 = "	UPDATE $tableTmp SET
    					rang_villes_proches = :rang_villes_proches
    					WHERE rang = :rang";
    	$sql_sauv2 = $this->_dbh_diff->prepare($req_sauv2);

        // On effectue une première boucle pour stocker les villes correspondantes au scope du jour
        for ($rang=1; $rang<=$this->_objectifJour; $rang++) {

            // Récupération des infos de la commune dans la table 'geo_communes'
            $sql_ville1->execute( array('rang'=>$rang));
            $res_ville1 = $sql_ville1->fetch();

            // Stockage des villes dans la table temporaire
            $sql_sauv1->execute( array(
                                        'rang'      => $rang,
                                        'dns'       => $res_ville1->dns,
                                        'cde_postal'=> $res_ville1->cde_postal,
                                        'lat_deg'	=> $res_ville1->lat_deg,
                                        'long_deg'	=> $res_ville1->long_deg,
                                      ));
        }

        // On effectue une seconde bouble pour stocker les villes de la table temporaires les plus proches de chaque vile
    	for ($rang=1; $rang<=$this->_objectifJour; $rang++) {

    		// Récupération des infos de la ville dans la table temporaire
    		$sql_ville2->execute( array( 'rang'=>$rang ));
    		$res_ville2 = $sql_ville2->fetch();

    		// Recherche des 10 villes les plus proches
    		$sql_vp->execute( array(
                                    'FROM_LAT'	=> $res_ville2->lat_deg,
    								'FROM_LONG'	=> $res_ville2->long_deg,
                                   ));

    		$villes_proches = array();

    		// On récupère les 5 villes de catégorie A
    		// On récupère les 5 villes de catégorie B
    		// On récupère les 10 villes de catégorie C
    		while ($res_vp = $sql_vp->fetch()) {

    			if ($res_vp->distance < 300) {
    				$villes_proches[] = $res_vp->rang;

                    if ($rang <= 5 && $this->_debug) {
                        $this->_debugRes[] = $res_vp->rang . ' : ' . $res_vp->dns . ' -> ' . $res_vp->distance;
                    }
    			}
    		}

    		$villes_proches = json_encode($villes_proches);

            // Sauvegarde des villes proches
    		$sql_sauv2->execute( array(
                                        'rang'                  => $rang,
                                        'rang_villes_proches'   => $villes_proches,
                                      ));

            if ($rang <= 5 && $this->_debug) {
                $this->_debugRes[] = 'rang : ' . $rang;
    		    $this->_debugRes[] = $villes_proches;
            }
    	}

        // Suppression de la table des villes liée au projet
    	$req = "DROP TABLE IF EXISTS $table;";
    	$sql = $this->_dbh_diff->query($req);

    	// On renomme la table temporaire avec le nom de la table en production
    	$req = "RENAME TABLE $tableTmp TO $table";
    	$sql = $this->_dbh_diff->query($req);
    }


    /**
     * Affichage des informations de débug
     */
    private function affDebug($timeDeb, $timeFin)
    {
        $interval   = $timeFin->diff($timeDeb);
        $duree      = $interval->format('%H:%I:%S');

        if (PHP_SAPI === 'cli') {

            echo "Projet : " . $this->_nomProject ." - Temps d'execution : " . $duree . chr(10);

            if ($this->_debug) {
                echo chr(10) . chr(10);
                echo implode(chr(10), $this->_debugRes);
                echo chr(10);
            }

            echo '_________________________________________' . chr(10) . chr(10) . chr(10);
        }
    }
}
