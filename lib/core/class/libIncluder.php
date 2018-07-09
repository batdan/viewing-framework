<?php
namespace core;

/**
 * Gestion des librairies CSS et JS / Gestion des ajouts de code CSS et JS
 *
 * @author Daniel Gomes
 */
class libIncluder
{
    /**
     * Création des variables de session pour le chargement du code et des librairies
     */
    public static function getInstance()
    {
        // Librairies
        $_SESSION['addJslibs']  = array();
        $_SESSION['addCsslibs'] = array();

        // Chargement de scripts
        $_SESSION['addJsScripts']  = array();
        $_SESSION['addCssScripts'] = array();
    }


    /**
     * Affichage des librairies JS
     */
    public static function get_addJslibs()
    {
        $dom = new \DOMDocument("1.0", "utf-8");

        foreach ($_SESSION['addJslibs'] as $libUrl) {
            $script = $dom->createElement('script');
            $script->setAttribute('src', $libUrl);

            $br = $dom->createTextNode(chr(10));

            $dom->appendChild($script);
            $dom->appendChild($br);
        }

        echo $dom->saveHTML();
        return true;
    }


    /**
     * Affichage des librairies CSS
     */
    public static function get_addCsslibs()
    {
        $dom = new \DOMDocument("1.0", "utf-8");

        foreach ($_SESSION['addCsslibs'] as $libUrl) {
            $link = $dom->createElement('link');
            $link->setAttribute('rel', 'stylesheet');
            $link->setAttribute('href', $libUrl);

            $br = $dom->createTextNode(chr(10));

            $dom->appendChild($link);
            $dom->appendChild($br);
        }

        echo $dom->saveHTML();
        return true;
    }


    /**
     * Affichage des scripts JS
     */
    public static function get_addJsScripts()
    {
    	if (count($_SESSION['addJsScripts']) > 0) {

	    	$html = '<script type="text/javascript">' . chr(10);

	        foreach ($_SESSION['addJsScripts'] as $code) {
                $html .= $code . chr(10);
	        }

	        $html .= '</script>' . chr(10);
	        echo $html;
    	}

        return true;
    }


    /**
     * Affichage des scripts CSS
     */
    public static function get_addCssScripts()
    {
        if (count($_SESSION['addCssScripts']) > 0) {

            $html = '<style type="text/css">' . chr(10);

	        foreach ($_SESSION['addCssScripts'] as $code) {
                $html .= $code . chr(10);
	        }

            $html .= '</style>' . chr(10);
	        echo $html;
        }

        return true;
    }


    /**
     * Stockage dans un tableau des url de librairies JS
     */
    public static function add_JsLib($js)
    {
        if (is_array($js)) {
            foreach ($js as $lib) {
                if (! in_array($lib, $_SESSION['addJslibs'])) {
                    $_SESSION['addJslibs'][] = $lib;
                }
            }
        } else {
            if (! in_array($js, $_SESSION['addJslibs'])) {
                $_SESSION['addJslibs'][] = $js;
            }
        }
    }


    /**
     * Stockage dans un tableau des url de librairies CSS
     */
    public static function add_CssLib($css)
    {
        if (is_array($css)) {
            foreach ($css as $lib) {
                if (! in_array($lib, $_SESSION['addCsslibs'])) {
                    $_SESSION['addCsslibs'][] = $lib;
                }
            }
        } else {
            if (! in_array($css, $_SESSION['addCsslibs'])) {
                $_SESSION['addCsslibs'][] = $css;
            }
        }
    }


    /**
     * Stockage dans un tableau des url des scripts JS
     */
    public static function add_JsScript($js)
    {
        $_SESSION['addJsScripts'][] = $js;
    }


    /**
     * Stockage dans un tableau des url des scripts CSS
     */
    public static function add_CssScript($css)
    {
        $_SESSION['addCssScripts'][] = $css;
    }
}
