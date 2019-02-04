<?php
namespace core;

/**
 * Connexion aux bases de données MongoDb
 *
 * @author Daniel Gomes
 */
class mongoDbSingleton
{
	/**
	 * Attributs
	 */
	private static $instance = array();


	/**
	 * Création d'une instance MongoDb si elle n'existe pas déjà
	 */
	public static function getInstance($name='default', $options=array(), $timeZone='Europe/Paris')
	{
		if (! array_key_exists($name, self::$instance))
		{
			// Déclaration du fuseau horaire et récupération du décalage horaire
			// date_default_timezone_set($timeZone);
	        // $dateTimeZone   = new \DateTimeZone($timeZone);
	        // $dateTime       = new \DateTime("now", $dateTimeZone);
	        // $gmt = '+0' . ($dateTimeZone->getOffset($dateTime) / 3600) . ':00';

			// $charset = $db[$name]['charset'];

			try {
				// Récupération de la configuration de connexion MongoDb
				$mongo = config::getConfig('mongo');

				$mongoHost = $mongo[$name]['mongoHost'];
				$mongoBase = $mongo[$name]['mongoBase'];
				$mongoUser = $mongo[$name]['mongoUser'];
				$mongoPass = $mongo[$name]['mongoPass'];
				$mongoPort = $mongo[$name]['mongoPort'];

				// Options de connections
				$defaultOptions = array("socketTimeoutMS" => 1000000);
				$connectOptions = array_merge($defaultOptions, $options);

				// Connexion à la base de données
			    $connect = '';
			    if (!empty($mongoUser) && !empty($mongoPass)) {
			        $connect = $mongoUser . ':' . $mongoPass . '@';
			    }

				$client = new \MongoDB\Client(
			        'mongodb://' . $connect . $mongoHost . ':' . $mongoPort,
			        $connectOptions
			    );

			    self::$instance[$name] = $client->{$mongoBase};

			} catch (\Exception $e) {

			    echo $e->getMessage();
			}
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
