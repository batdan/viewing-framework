<?php
namespace core;

/**
 * Connexion PDO
 *
 * @author Daniel Gomes
 */
class dbSingleton
{
	/**
	 * Attributs
	 */
	private static $instance = array();


	/**
	 * Création d'une instance PDO si elle n'existe pas déjà
	 */
	public static function getInstance($name='default')
	{
		if (! array_key_exists($name, self::$instance))
		{
			$db = config::getConfig('db');

			$host = $db[$name]['host'];
			$base = $db[$name]['base'];
			$user = $db[$name]['user'];
			$pass = $db[$name]['pass'];
			$charset = $db[$name]['charset'];

			self::$instance[$name] = new \PDO("mysql:host=".$host.";charset=".$charset.";dbname=".$base, $user, $pass);
			self::$instance[$name]->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			self::$instance[$name]->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);
		}

		return self::$instance[$name];
	}


	/**
	 * Fermeture d'une instance PDO
	 */
	public static function closeInstance($name='default')
	{
		unset(self::$instance[$name]);
	}
}
