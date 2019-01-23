<?php
namespace vw\infra\ovh\dedicated;

use core\config;
use Ovh\Api;

/**
 * Récupération de la liste de serveur
 *
 * @author Daniel Gomes
 */
class server
{
    /**
     * Récupération de la liste des serveurs administrés
     *
     * @return array
     */
    public static function get()
    {
        $ovhConfig = config::getConfig('ovh');

        $ovh = new Api(
            $ovhConfig['appKey'],           // Application Key
            $ovhConfig['appSecret'],        // Application Secret
            $ovhConfig['endPointEu'],       // Endpoint of API OVH Europe (List of available endpoints)
            $ovhConfig['consumerKey']       // Consumer Key
        );

        return $ovh->get('/dedicated/server');
    }
}
