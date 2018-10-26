<?php
namespace core;

/**
 * Singleton
 * Chargement des fichiers de configuration d'un projet
 * Attention, le dossier "config" est par défaut au même niveau que le dossier "vendor"
 *
 * @author Daniel Gomes
 */
class config
{
	/**
	 * Attributs
	 */
	private static $_instance 	= array();
	private static $_path  		= __DIR__ . '/../../../../../../config/';


	/**
	 * Permet de modifier le chemin par défault des fichiers de configuration
	 */
	public static function setPath($path)
	{
		$this->_path = $path;
	}


	/**
	 * Chargement d'un fichier de config
	 */
	public static function getConfig($confFile)
	{
		/**
		 * Le fichier local (présent sur le serveur) surclasse le fichier de config
		 * cela permet d'avoir une version de dev différente de la prod
		 */
		if (file_exists( self::$_path . $confFile . '.local.php' )) {

			return include self::$_instance[$confFile] = self::$_path . $confFile . '.local.php';

		} elseif (file_exists( self::$_path . $confFile . '.php' )) {

			return include self::$_instance[$confFile] = self::$_path . $confFile . '.php';

		} else {

			die ('Impossible de trouver le fichier de configuration : ' . $confFile . '.php');

		}
	}
}
