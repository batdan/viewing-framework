<?php
namespace core;

/**
 * Chargement des fichiers de configuration d'un projet
 * Cette classe peut récupérer les fichiers de configuration en PHP et YML
 *
 * Attention : si deux fichiers portent le même nom (en php et yml),
 * seul le premier sera récupéré
 *
 * @author Daniel Gomes
 */
class config
{
	/**
	 * Attributs
	 */
	private static $_instance 	= array();


	/**
	 * Chargement d'un fichier de config
	 */
	public static function getConfig($confFile)
	{
		// Récupération du chemin vers le dossier "config"
		$path = self::getPath();

		// Récupération de l'extension du fichier de "config"
		$extension = self::getExtension($path, $confFile);

		$pathConfFile = self::publicOrPrivateFile($path, $confFile, $extension);

		// Récupération d'une configuration en PHP
		if ($extension == 'php') {
			return include self::$_instance[$confFile] = $pathConfFile;

		// Récupération d'une configuration en PHP
		} elseif ($extension == 'yml' || $extension == 'yaml') {

			if (! function_exists('yaml_parse_file')) {
				throw new \Exception("Fonction 'yaml_parse_file' absente : http://php.net/manual/fr/function.yaml-parse-file.php", 1);
			}

			return self::$_instance[$confFile] = \yaml_parse_file($pathConfFile);

		} else {
			throw new \Exception("Seuls les fichiers php, yml & yaml sont pris en charge", 1);
		}
	}


	/**
	 * Le fichier local (présent sur le serveur) surclasse le fichier de config
	 * cela permet d'avoir une version de dev différente de la prod
	 *
 	 * @param  string 	$path     	Chemin vers le dossier de config
 	 * @param  string 	$confFile 	nom du fichier
 	 * @return string|null
	 */
	private function publicOrPrivateFile($path, $confFile, $extension)
	{
		if (file_exists( $path . $confFile . '.local.' . $extension )) {
			return $path . $confFile . '.local.' . $extension;
		} elseif (file_exists( $path . $confFile . '.' . $extension )) {
			return $path . $confFile . '.' . $extension;
		}
	}


	/**
	 * Permet de calculer automatiquement le chemin par défaut
	 * des fichiers de configuration
	 *
	 * @param  	string|null 	$recursiv		Récursivité pour retrouver le dossier "config"
	 * @param  	integer			$loop			Compteur du nombre de boucle pour retrouver le path (max:8)
	 * @return
	 */
	private static function getPath($recursiv = null, $loop = 0)
	{
		$folder 	= '/config/';
		$checkPath 	= __DIR__ . $recursiv . $folder;

		if ($loop == 8) {
			throw new \Exception("Trop de boucle pour retrouver le dossier config", 1);
		}

		$loop++;

		if (file_exists($checkPath) && is_dir($checkPath)) {
			return __DIR__ . $recursiv . $folder;
		} else {
			return self::getPath($recursiv . '/..', $loop);
		}
	}


	/**
	 * Récupération de l'extension du fichier de configuration
	 *
	 * @param  string 	$path     	Chemin vers le dossier de config
 	 * @param  string 	$confFile 	nom du fichier
	 * @return string
	 */
	private static function getExtension($path, $confFile)
	{
		// Récupération de nom du fichier de conf sans l'extension
		$expConfFile = explode('/', $confFile);
		$confFile = end($expConfFile);

		// On lis le contenu du dossier "config"
		$dh = opendir($path);

		// Boucle sur tous les éléments trouvés dans le dossier
		while (($file = readdir($dh)) !== false) {

			// Récupération des fichiers du dossier
			if ( $file != '.' && $file != '..' && !is_dir($file)) {

				$expFile = explode('.', $file);

				if ($expFile[0] == $confFile) {
					$extension = strtolower(end($expFile));

					break;
				}
			}
		}

		if (!isset($extension)) {
			throw new \Exception("Fichier de configuration absent", 1);
		}

		// on ferme la connection
		closedir($dh);

		return $extension;
	}
}
