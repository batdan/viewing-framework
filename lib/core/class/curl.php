<?php
namespace core;

/**
 * Appels avec la méthode php cURL
 * GET et POST
 */
class curl
{
    /**
     * Conversion string en éléments d'url
     *
     * @param       string      $url            chaîne à transformer
     * @param       array       $postfields     éléments à poster (tableau associatif)
     * @param       array       $addCurlopt     permet de passer des options supplémentaire dans cURL - ex : un header
     */
    public static function curlPost($url, $postfields, $addCurlopt=null)
	{
        // Get cURL resource
        $curl = curl_init();

        // Set some options - we are passing in a useragent too here
        $curlOption = array(
            CURLOPT_RETURNTRANSFER  => 1,
            CURLOPT_URL             => $url,
            CURLOPT_USERAGENT       => 'Mozilla/5.0 (Android 4.4; Mobile; rv:41.0) Gecko/41.0 Firefox/41.0',
            CURLOPT_POST            => 1,
            CURLOPT_POSTFIELDS      => $postfields,
        );

        // Insertion des options cURL supplémentaire
        if (! is_null($addCurlopt) && is_array($addCurlopt)){
            foreach ($addCurlopt as $k=>$v) {
                $curlOption[$k] = $v;
            }
        }

        // Gestion des appels en HTTPS
        if (stristr($url, 'https')) {
            $curlOption[CURLOPT_SSL_VERIFYHOST] = false;
            $curlOption[CURLOPT_SSL_VERIFYPEER] = false;
        }

        curl_setopt_array($curl, $curlOption);

        // Send the request & save response to $resp
        $res = curl_exec($curl);

        // Close request to clear up some resources
        curl_close($curl);

        return $res;
	}


    /**
     * Appel méthode cURL en GET
     * passage des arguments get dans un array()
     *
     * @param       string      $url            chaîne à transformer
     * @param       array       $getFields      arguments en get (tableau associatif)
     * @param       array       $addCurlopt     permet de passer des options supplémentaire dans cURL - ex : un header
     */
    public static function curlGet2($url, $getFields=array(), $addCurlopt=null)
    {
        if (count($getFields) > 0) {
            $get = array();
            foreach ($getFields as $k=>$v) {
                $get[] = $k . '=' . $v;
            }
            $url .= '?' . implode('&', $get);
        }

        return self::curlGet($url, $addCurlopt);
    }


    /**
     * Conversion string en éléments d'url
     *
     * @param       string      $url            chaîne à transformer
     * @param       array       $addCurlopt     permet de passer des options supplémentaire dans cURL - ex : un header
     */
    public static function curlGet($url, $addCurlopt=null)
	{
        // Get cURL resource
        $curl = curl_init();

        // Set some options - we are passing in a useragent too here
        $curlOption = array(
            CURLOPT_RETURNTRANSFER  => 1,
            CURLOPT_URL             => $url,
            CURLOPT_USERAGENT       => 'Mozilla/5.0 (Android 4.4; Mobile; rv:41.0) Gecko/41.0 Firefox/41.0',
        );

        // Insertion des options cURL supplémentaire
        if (! is_null($addCurlopt) && is_array($addCurlopt)){
            foreach ($addCurlopt as $k=>$v) {
                $curlOption[$k] = $v;
            }
        }

        // Gestion des appels en HTTPS
        if (stristr($url, 'https')) {
            $curlOption[CURLOPT_SSL_VERIFYHOST] = false;
            $curlOption[CURLOPT_SSL_VERIFYPEER] = false;
        }

        curl_setopt_array($curl, $curlOption);

        // Send the request & save response to $resp
        $res = curl_exec($curl);

        // Close request to clear up some resources
        curl_close($curl);

        return $res;
    }
}
