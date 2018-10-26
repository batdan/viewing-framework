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
        $_SESSION['addJslibs_optimize']  = array();

        $_SESSION['addCsslibs'] = array();
        $_SESSION['addCsslibs_optimize'] = array();

        // Chargement de scripts
        $_SESSION['addJsScripts']  = array();
        $_SESSION['addCssScripts'] = array();
    }


    /**
     * Affichage des librairies JS
     */
    public static function get_addJslibs()
    {
        if (count($_SESSION['addJslibs']) > 0) {

            // Récupération du protocole utilisé par le site
            if ($_SERVER['SERVER_PORT'] == 80) {
                $proto  = 'http';
            } else {
                $proto  = 'https';
            }

            $libs   = chr(10);
            $script = chr(10) . '<script type="text/javascript">';

            foreach ($_SESSION['addJslibs'] as $key => $libUrl) {

                $js = '';

                if (strstr($libUrl, 'min') || $_SESSION['addJslibs_optimize'][$key] === false) {

                    if (preg_match('`//`',$libUrl) || $_SERVER['SERVER_ADDR']=='127.0.0.1' || $_SERVER['SERVER_ADDR']=='::1') {
                        $v = '';
                    } else {
                        if      (file_exists('.' .          $libUrl))   { $v = '?v=' . filemtime('.' .          $libUrl);  }
                        elseif  (file_exists('..' .         $libUrl))   { $v = '?v=' . filemtime('..' .         $libUrl); }
                        elseif  (file_exists('../..' .      $libUrl))   { $v = '?v=' . filemtime('../..' .      $libUrl); }
                        elseif  (file_exists('../../..' .   $libUrl))   { $v = '?v=' . filemtime('../../..' .   $libUrl); }
                        else {
                            //die ('Impossible d\'atteindre la librairie : ' . $libUrl);
                            $v = '';
                        }
                    }

                    $libs .= '<script type="text/javascript" src="' . $libUrl . $v . '"></script>' . chr(10);

                } else {

                    if (substr($libUrl, 0, 2) == '//') {
                        $libUrl = $proto . '://' . substr($libUrl, 2, strlen($libUrl) - 2);
                        echo $libUrl.'<br>';
                        $includeMethod = 'externe';
                    } elseif (substr($libUrl, 0, 1) == '/' && substr($libUrl, 1, 1) != '/') {
                        //$libUrl = $proto . '://' . $_SERVER['HTTP_HOST'] . $libUrl;
                        $includeMethod = 'locale';
                    } else {
                        $includeMethod = 'locale';
                    }

                    $script .= chr(10) . '/* ' . basename($libUrl) . ' */' . chr(10);

                    if ($includeMethod == 'externe') {
                        $js = file($libUrl);
                        $js = implode(chr(10), $js);
                    } else {

                        $file = explode('?', basename($libUrl));
                        $file = $file[0];
                        $extension = pathinfo( $file, PATHINFO_EXTENSION);

                        if ($extension == 'js') {
                            $js = file( __DIR__ . '/../../../../../..' . $libUrl);
                            $js = implode(chr(10), $js);
                        }
                        if ($extension == 'php') {
                            $js = include( __DIR__ . '/../../../../../..' . $libUrl );
                        }
                    }

                    $js = str_replace(chr(10).chr(10), chr(10), $js);

                    $js = \JSMin\JSMin::minify($js);

                    $script .= $js . chr(10);
                }
            }

            $script .= '</script>' . chr(10) . chr(10);

            return $libs . $script;
        }

        return '';
    }


    /**
     * Affichage des librairies CSS
     */
    public static function get_addCsslibs()
    {
        if (count($_SESSION['addCsslibs']) > 0) {

            // Récupération du protocole utilisé par le site
            if ($_SERVER['SERVER_PORT'] == 80) {
                $proto  = 'http';
            } else {
                $proto  = 'https';
            }

            $cssMinify = new \Minify_CSSmin();

            $libs   = chr(10);
            $style  = chr(10) . '<style type="text/css">';

            foreach ($_SESSION['addCsslibs'] as $key => $libUrl) {

                $css = '';

                if (strstr($libUrl, 'min') || $_SESSION['addCsslibs_optimize'][$key] === false) {

                    if ($_SESSION['addCsslibs_optimize'][$key] === false) {

                        if (preg_match('`//`',$libUrl) || $_SERVER['SERVER_ADDR']=='127.0.0.1' || $_SERVER['SERVER_ADDR']=='::1') {
                            $v = '';
                        } else {
                            if      (file_exists('.' .          $libUrl))   { $v = '?v=' . filemtime('.' .          $libUrl);  }
                            elseif  (file_exists('..' .         $libUrl))   { $v = '?v=' . filemtime('..' .         $libUrl); }
                            elseif  (file_exists('../..' .      $libUrl))   { $v = '?v=' . filemtime('../..' .      $libUrl); }
                            elseif  (file_exists('../../..' .   $libUrl))   { $v = '?v=' . filemtime('../../..' .   $libUrl); }
                            else {
                                //die ('Impossible d\'atteindre la librairie : ' . $libUrl);
                                $v = '';
                            }
                        }

                        $libs .= '<link href="' . $libUrl . $v . '" rel="stylesheet">' . chr(10);

                    } else  {

                        if (substr($libUrl, 0, 2) == '//') {
                            $libUrl = $proto . '://' . substr($libUrl, 2, strlen($libUrl) - 2);
                            $includeMethod = 'externe';
                        } elseif (substr($libUrl, 0, 1) == '/' && substr($libUrl, 1, 1) != '/') {
                            // $libUrl = $proto . '://' . $_SERVER['HTTP_HOST'] . $libUrl;
                            $includeMethod = 'locale';
                        }

                        $style .= chr(10) . '/* ' . basename($libUrl) . ' */' . chr(10);

                        if ($includeMethod == 'externe') {
                            $css = file($libUrl);
                            $css = implode(chr(10), $css);
                        } else {

                            $file = explode('?', basename($libUrl));
                            $file = $file[0];
                            $extension = pathinfo($file, PATHINFO_EXTENSION);

                            if ($extension == 'css') {
                                $css = file( __DIR__ . '/../../../../../..' . $libUrl);
                                $css = implode(chr(10), $css);
                            }
                            if ($extension == 'php') {
                                $css = include( __DIR__ . '/../../../../../..' . $libUrl );
                            }
                        }

                        $css = str_replace(chr(10).chr(10), chr(10), $css);

                        $style .= $css . chr(10);
                    }

                } else {

                    if (substr($libUrl, 0, 2) == '//') {
                        $libUrl = $proto . '://' . substr($libUrl, 2, strlen($libUrl) - 2);
                        $includeMethod = 'externe';
                    } elseif (substr($libUrl, 0, 1) == '/' && substr($libUrl, 1, 1) != '/') {
                        // $libUrl = $proto . '://' . $_SERVER['HTTP_HOST'] . $libUrl;
                        $includeMethod = 'locale';
                    }

                    $style .= chr(10) . '/* ' . basename($libUrl) . ' */' . chr(10);

                    if ($includeMethod == 'externe') {
                        $css = file($libUrl);
                        $css = implode(chr(10), $css);
                    } else {

                        $file = explode('?', basename($libUrl));
                        $file = $file[0];
                        $extension = pathinfo($file, PATHINFO_EXTENSION);

                        if ($extension == 'css') {
                            $css = file( __DIR__ . '/../../../../../..' . $libUrl);
                            $css = implode(chr(10), $css);
                        }
                        if ($extension == 'php') {
                            $css = include( __DIR__ . '/../../../../../..' . $libUrl );
                        }
                    }

                    $css = str_replace(chr(10).chr(10), chr(10), $css);

                    $css = $cssMinify->minify($css);

                    $style .= $css . chr(10);
                }
            }

            $style .= '</style>' . chr(10) . chr(10);

            return $libs . $style;
        }

        return '';
    }


    /**
     * Affichage des scripts JS
     */
    public static function get_addJsScripts($optimize=true)
    {
    	if (count($_SESSION['addJsScripts']) > 0) {

	    	$html = '<script type="text/javascript">' . chr(10);

	        foreach ($_SESSION['addJsScripts'] as $code) {

                if ( $optimize === true ) {
                    $html .= \JSMin\JSMin::minify($code) . chr(10);
                } else {
                    $html .= $code . chr(10);
                }
	        }

	        $html .= '</script>' . chr(10);

            return $html;
    	}

        return '';
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

            return $html;
        }

        return '';
    }


    /**
     * Stockage dans un tableau des url de librairies JS
     *
     * @param   mixed       $js             Soit une chaine avec une librairie JS, soit un tableau de librairies JS
     * @param   boolean     $optimize       A true, le code sera minifié
     */
    public static function add_JsLib($js, $optimize=true)
    {
        if (is_array($js)) {
            foreach ($js as $lib) {
                if (! in_array($lib, $_SESSION['addJslibs'])) {
                    $_SESSION['addJslibs'][] = $lib;
                    $_SESSION['addJslibs_optimize'][] = $optimize;
                }
            }
        } else {
            if (! in_array($js, $_SESSION['addJslibs'])) {
                $_SESSION['addJslibs'][] = $js;
                $_SESSION['addJslibs_optimize'][] = $optimize;
            }
        }
    }


    /**
     * Stockage dans un tableau des url de librairies CSS
     *
     * @param   mixed       $css            Soit une chaine avec une librairie CSS, soit un tableau de librairies CSS
     * @param   boolean     $optimize       A true, le code sera minifié
     */
    public static function add_CssLib($css, $optimize=true)
    {
        if (is_array($css)) {
            foreach ($css as $lib) {
                if (! in_array($lib, $_SESSION['addCsslibs'])) {
                    $_SESSION['addCsslibs'][] = $lib;
                    $_SESSION['addCsslibs_optimize'][] = $optimize;
                }
            }
        } else {
            if (! in_array($css, $_SESSION['addCsslibs'])) {
                $_SESSION['addCsslibs'][] = $css;
                $_SESSION['addCsslibs_optimize'][] = $optimize;
            }
        }
    }


    /**
     * Stockage dans un tableau des url des scripts JS
     *
     *  @param   string      $js
     */
    public static function add_JsScript($js)
    {
        $_SESSION['addJsScripts'][] = $js;
    }


    /**
     * Stockage dans un tableau des url des scripts CSS
     *
     *  @param   string      $css
     */
    public static function add_CssScript($css)
    {
        $_SESSION['addCssScripts'][] = $css;
    }
}
