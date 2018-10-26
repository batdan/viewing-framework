<?php
namespace core;

/**
 * Outils divers du framework
 */
class tools
{
    /**
     * Conversion string en éléments d'url
     *
     * @param       string      $texte      chaîne à transformer
     * @return      string
     */
    public static function filter_url_key($texte)
	{
        $texte = strip_tags($texte);

		$texte = trim($texte);
		$texte = trim($texte, '-');
        $texte = str_replace('&nbsp;', ' ', $texte);

		// suppression des accents, tréma et cédilles + qlq autres car. spéciaux
		$aremplacer 	= 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿ&#340;&#341;';
    	$enremplacement = 'aaaaaaaceeeeiiiidnoooooouuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyrr';
		$texte = utf8_decode($texte);
		$texte = strtr($texte, utf8_decode($aremplacer), $enremplacement);
		$texte = strtolower($texte);

		// suppression des espaces et car. non-alphanumériques
		$texte = str_replace(" ",'-',$texte);
		$texte = preg_replace('/([^a-z0-9-_\/])/','-',$texte);

		// suppression des tirets multiples
		$texte = preg_replace('#([-]+)#','-',$texte);

		return trim($texte, '-');
	}


    /**
     * Conversion string en éléments d'url
     *
     * @param       string      $texte      chaîne à transformer
     * @return      string
     */
    public static function supprAccent($texte)
	{
		$texte = strip_tags($texte);

		$texte = trim($texte);
		$texte = trim($texte, '-');

		// suppression des accents, tréma et cédilles + qlq autres car. spéciaux
		$aremplacer 	= 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿ&#340;&#341;';
    	$enremplacement = 'AAAAAAACEEEEIIIIDNOOOOOOUUUUYbsaaaaaaaceeeeiiiidnoooooouuuyybyrr';
		$texte = utf8_decode($texte);
		$texte = strtr($texte, utf8_decode($aremplacer), $enremplacement);

		return trim($texte);
	}


    /**
     * Conversion string en majuscules sans accents
     *
     * @param       string      $texte      chaîne à transformer
     * @return      string
     */
    public static function strtoupperSansAccent($texte)
    {
        $texte = self::filter_url_key($texte);
        $texte = strtoupper($texte);

        return $texte;
    }


    /**
     * Permet de programmer une diffusion entre deux dates avec un objectif à atteindre
     *
     * @param       string      $dateDeb        Date de début de la diffusion
     * @param       string      $dateFin        Date de fin de la diffusion
     * @param       string      $type           Type de diffusion : 'lin' (linéaire), 'exp' (exponentielle) ou 'log' (logarithmique)
     * @param       integer     $objectif       Nombre à atteindre
     * @param       float       $coeff          Permet de faire varier la courbe de progression
     *
     * @return      array
     */
    public static function diffusionProg($dateDeb, $dateFin, $type='lin', $objectif=100, $coeff=1)
    {
        $date1 = new \DateTime($dateDeb);
        $date2 = new \DateTime($dateFin);
        $today = new \DateTime('now');

        $interval  = $date1->diff($date2);
        $interval2 = $date1->diff($today);

        // Nombre de jours atteint depuis de début de la diffusion
        if ($today->getTimestamp() < $date1->getTimestamp()) {
            $nbJoursToday = 0;
        } else {
            $nbJoursToday = ceil(($today->getTimestamp() - $date1->getTimestamp()) / 86400);    // 86400 : Nb secondes 1 jour
        }

        // On vérifie que la date de fin soit bien supérieur à la date de début
        if ($date1->getTimestamp() > $date2->getTimestamp()) {
            error_log('Courbe diffusion projet : date debut > date fin !');
        }

        $nbJours = intval($interval->format('%a'));
        $res     = array();

        // Courbe linéaire
        if ($type == 'lin') {

            for ($i=1; $i<=$nbJours; $i++) {
                $res[$i] = ceil(($objectif / $nbJours) * $i);
            }

        // Courbe exponentielle ou logarithmique
        } else {

            for ($i=1; $i<=$nbJours; $i++) {
                $base = 10;   // log 10
                $x = 1 + ((pow($base, $coeff) - 1) * $i / $nbJours);
                $y = log($x, $base);
                $res[$i] = ceil(($objectif / $coeff) * $y);
            }

            // Inversion calcul logarithmique -> exponentielle
            if ($type == 'exp') {
                rsort($res);
                for ($i=1; $i<$nbJours; $i++) {
                    $res[$i] = $objectif - $res[$i];
                }
                $res[$nbJours] = $res[0];
                unset($res[0]);
            }
        }

        return array(
            'nbJoursToday'  => $nbJoursToday,
            'diffusion'     => $res,
        );
    }
}
