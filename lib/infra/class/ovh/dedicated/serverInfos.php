<?php
namespace vw\infra\ovh\dedicated;

use core\config;
use Ovh\Api;

/**
 * Récupération de la liste de serveur
 *
 * @author Daniel Gomes
 */
class serverInfos
{
    /**
     * Récupération de la liste des serveurs administrés
     *
     * @param   string      $serviceName        Nom du serveur
     * @return  array
     */
    public static function get($serviceName = null)
    {
        if (is_null($serviceName)) {
            throw new Exception('serviceName empty!');
        }

        $ovhConfig = config::getConfig('ovh');

        $ovh = new Api(
            $ovhConfig['appKey'],           // Application Key
            $ovhConfig['appSecret'],        // Application Secret
            $ovhConfig['endPointEu'],       // Endpoint of API OVH Europe (List of available endpoints)
            $ovhConfig['consumerKey']       // Consumer Key
        );

        return $ovh->get('/dedicated/server/' . $serviceName);
    }
}
