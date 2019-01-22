<?php
namespace geoIp2;

/**
 * Informations permettant de télécharger le fichier geoIp2 City
 * le plus récent
 *
 * @author  Daniel Gomes
 * @since   2019-01-15
 */
class checkFile
{
    /**
     * Récupération des informations sur le dernier fichier geoIp2 disponible
     * (date & path)
     *
     * @return  string      flux JSON
     */
    public static function jsonFileInfos()
    {
        // Vérifie si la variable get "key" est correct
        if (self::secureKey() === false) {
            $res = array(
                'error'  => true,
                'msg'    => 'Invalid key!',
            );

            return json_encode($res);
        }

        // Récupération du dossier contenant le fichier geoIp2 le plus à jour
        $lastfolderDate = self::lastfolderDate();

        // Protocol http ou https
        $protocol = ($_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';

        // Chemin vers le fichier
        $pathFile = 'files/' . $lastfolderDate . '/GeoIP2-Country.mmdb';

        $pathfileLocal = __DIR__ . '/../' . $pathFile;
        $pathfileUrl   = $protocol . '://' . $_SERVER['SERVER_NAME'] . '/geoIp2/' . $pathFile;

        // Check existance du fichier
        if ($lastfolderDate === false || !file_exists($pathfileLocal)) {
            $res = array(
                'error'  => true,
                'msg'    => 'Missing file!'
            );
        } else {
            $res = array(
                'error'  => false,
                'result' => array(
                    'date'      => $lastfolderDate,
                    'path'      => $pathfileUrl,
                    'md5_file'  => md5_file($pathfileUrl),
                )
            );
        }

        return json_encode($res);
    }


    /**
     * Vérifie si la variable get "key" est absente
     * ou ne correspond pas à celle attendu
     *
     * Un page blanche est renvoyée le cas échéant
     */
    private static function secureKey()
    {
        // Clé sécurisant le téléchargement du fichier
        $key = md5(gmdate('Ymd') . 'geoIp2');

        // Comparaison de la clé
        if (!isset($_GET['key']) || $_GET['key'] != $key) {
            return false;
        }
    }


    /**
     * Récupération du chemin vers la dernière version du fichier geoIp2
     * @return string
     */
    private static function lastfolderDate()
    {
        // Liste des dossiers contenant des fichiers de base geoIp2
        $dirList = array();

        $path = __DIR__ . '/../files/';

        // On liste tous les dossiers datés dans le path geoIp2
        if (is_dir($path)) {

            // si il contient quelque chose
            if ($dh = opendir($path)) {

                // boucler tant que quelque chose est trouve
                while (($dirDate = readdir($dh)) !== false) {

                    if ($dirDate != '.' && $dirDate != '..' && is_dir($path . $dirDate)) {

                        if (file_exists($path . $dirDate . '/GeoIP2-Country.mmdb') && file_exists($path . $dirDate . '/md5_file')) {

                            $md5File = file($path . $dirDate . '/md5_file');
                            $md5File = trim($md5File[0]);

                            if (md5_file($path . $dirDate . '/GeoIP2-Country.mmdb') == $md5File) {
                                $dirList[] = (int) $dirDate;
                            }
                        }
                    }
                }

                // Fermeture de la connection
                closedir($dh);
            }
        }

        if (count($dirList) == 0) {
            return false;
        }

        // Classement ASC des dossiers de bases geoIp2
        sort($dirList);

        // Dossier contenant le fichier de base geoIp2 est nommé par la date de ce dernier
        return end($dirList);
    }
}
