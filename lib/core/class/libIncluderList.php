<?php
namespace core;

/**
 * Gestion des librairies CSS et JS / Gestion des ajouts de code CSS et JS
 * Liste des librairies disponibles
 *
 * @author Daniel Gomes
 */
class libIncluderList extends libIncluder
{
    /**
     * Chargement de JQuery 2.1.4
     * http://jquery.com/
     */
    public static function add_jQuery($optimize=false)
    {
        $js     = array("//code.jquery.com/jquery-2.1.4.min.js");

        parent::add_JsLib($js, $optimize);
    }


    /**
     * Chargement de JQuery 2.1.4
     * http://jquery.com/
     */
    public static function add_jQuery_V3($optimize=false)
    {
        $js     = array("//code.jquery.com/jquery-3.3.1.min.js");

        parent::add_JsLib($js, $optimize);
    }



    /**
     * Chargement de JQuery UI 1.11.4 dans une version limité au strict nécessaire et sans thème
     * Modules présents :
     *          - UI Core (Core, Widget, Mouse, Position), Interaction
     *          - Interactions (Draggable, Droppable, Resizable, Selectable, Sortable)
     */
    public static function add_jQueryUI($optimize=false)
    {
        self::add_jQuery();

        $js     = array("/vendor/vw/framework/libExt/js/jquery-ui/jquery-ui.min.js");

        $css    = array("/vendor/vw/framework/libExt/js/jquery-ui/jquery-ui.min.css",
                        "/vendor/vw/framework/libExt/js/jquery-ui/jquery-ui.structure.min.css");

        parent::add_JsLib($js, $optimize);
        parent::add_CssLib($css, $optimize);
    }


    /**
     * Chargement des js et css du backoffice par défaut
     */
    public static function add_vwDefault($optimize=false)
    {
        self::add_jQuery();

        $js     = array("/lib/template/js/default.js",
                        "/vendor/vw/framework/lib/core/js/ping.js",
                        "/vendor/vw/framework/lib/template/js/sidebar.js");

        $css    = array("/vendor/vw/framework/lib/template/css/sidebar.css",
                        "/vendor/vw/framework/lib/template/css/navbar.css",
                        "/lib/template/css/default.css");

        parent::add_JsLib($js, $optimize);
        parent::add_CssLib($css, $optimize);
    }


    /**
     * Chargement des js et css du backoffice pour la page d'authentification
     */
    public static function add_vwAuth($optimize=false)
    {
        self::add_jQuery();
        self::add_bootstrap();

        $js     = array("/lib/auth/js/auth.js");
        $css    = array("/lib/auth/css/auth.css");

        parent::add_JsLib($js, $optimize);
        parent::add_CssLib($css, $optimize);
    }


    /**
     * Chargement aux bibliothèques liés au module "widget" du framework
     */
    public static function add_vwWidget($optimize=false)
    {
        $css    = array("/vendor/vw/framework/lib/widget/css/widget.css");

        parent::add_CssLib($css, $optimize);
    }


    /**
     * Chargement aux bibliothèques liés au module "form" du framework
     */
    public static function add_vwForm($optimize=false)
    {
        $css    = array("/vendor/vw/framework/lib/form/css/style.css");

        parent::add_CssLib($css, $optimize);
    }


    /**
     * Chargement aux bibliothèques liés au module "form" du framework
     */
    public static function add_vwSpin($optimize=false)
    {
        self::add_jQuery();
        self::add_bootstrap();

        $js     = array("/vendor/vw/spin/lib/spin/js/tools.js",
                        "/vendor/vw/spin/lib/spin/js/rendu.js",
                        "/vendor/vw/spin/lib/spin/js/leftClick_event.js",
                        "/vendor/vw/spin/lib/spin/js/rightClick_event.js",
                        "/vendor/vw/spin/lib/spin/js/contextMenus.js",
                        "/vendor/vw/spin/lib/spin/js/contextMenus2.js",
                        "/vendor/vw/spin/lib/spin/js/modals.js",
                        "/vendor/vw/spin/lib/spin/js/tags.js",
                        "/vendor/vw/spin/lib/spin/js/actions.js");

        $css    = array("/vendor/vw/spin/lib/spin/css/rendu.css",
                        "/vendor/vw/spin/lib/spin/css/contextmenu.css",
                        "/vendor/vw/spin/lib/spin/css/modals.css");

        parent::add_JsLib($js, $optimize);
        parent::add_CssLib($css, $optimize);
    }


    public static function add_fontAwesome($optimize=false)
    {
        //$css    = array("//maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css");
        $css    = array("//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css");

        parent::add_CssLib($css, $optimize);
    }

    /**
     * Chargement de Bootstrap 3
     * http://getbootstrap.com/
     */
    public static function add_bootstrap($optimize=false)
    {
        self::add_jQuery();

        // Bootstrap 3
        // $js     = array("//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js");
        //
        // $css    = array("//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css",
        //                 "//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css");

        $js     = array("//stackpath.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js");

        $css    = array("//stackpath.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css",
                        "//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css");

        parent::add_JsLib($js, $optimize);
        parent::add_CssLib($css, $optimize);
    }


    /**
     * Chargement d'un gestionnaire de notifications
     * http://goodybag.github.io/bootstrap-notify/
     */
    public static function add_bootstrapNotify($optimize=false)
    {
        self::add_jQuery();
        self::add_bootstrap();

        $js     = array("/vendor/vw/framework/libExt/js/bootstrap-notify-master/js/bootstrap-notify.js",
                        "/vendor/vw/framework/lib/core/js/notify.js");

        $css    = array("/vendor/vw/framework/libExt/js/bootstrap-notify-master/css/bootstrap-notify.css");

        parent::add_JsLib($js, $optimize);
        parent::add_CssLib($css, $optimize);
    }


    /**
     * Chargement de Bootstrap-validator
     * http://1000hz.github.io/bootstrap-validator/
     */
    public static function add_bootstrapValidator($optimize=false)
    {
        self::add_jQuery();
        self::add_bootstrap();

        $js     = array("/vendor/vw/framework/libExt/js/bootstrap-validator-master/dist/validator.min.js");

        parent::add_JsLib($js, $optimize);
    }


    /**
     * Chargement de Bootstrap Select
     * http://silviomoreto.github.io/bootstrap-select/
     */
    public static function add_bootstrapSelect($optimize=false)
    {
        self::add_jQuery();
        self::add_bootstrap();

        $js     = array("//cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.7.3/js/bootstrap-select.min.js",
                        "//cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.7.3/js/i18n/defaults-fr_FR.min.js");

        $css    = array("//cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.7.3/css/bootstrap-select.min.css");

        parent::add_JsLib($js, $optimize);
        parent::add_CssLib($css, $optimize);
    }


    /**
     * Parser, valider, manipuler et afficher des dates
     * http://momentjs.com
     */
    public static function add_moment($optimize=false)
    {
        self::add_jQuery();
        self::add_bootstrap();

        $js     = array("//cdnjs.cloudflare.com/ajax/libs/moment.js/2.10.6/moment.min.js",
                        "//cdnjs.cloudflare.com/ajax/libs/moment.js/2.10.6/locale/fr.js");

        parent::add_JsLib($js, $optimize);
    }


    /**
     * Chargement de Bootstrap datetimepicker
     * https://eonasdan.github.io/bootstrap-datetimepicker
     */
    public static function add_bootstrapDatetimepicker($optimize=false)
    {
        self::add_jQuery();
        self::add_bootstrap();
        self::add_moment();

        $js     = array("//cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.15.35/js/bootstrap-datetimepicker.min.js");
        $css    = array("//cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.15.35/css/bootstrap-datetimepicker.min.css");

        parent::add_JsLib($js, $optimize);
        parent::add_CssLib($css, $optimize);
    }


    /**
     * Chargement de Bootstrap Table
     * http://bootstrap-table.wenzhixin.net.cn/documentation/
     */
    public static function add_bootstrapTable($optimize=false)
    {
        self::add_jQuery();
        self::add_bootstrap();

        /*
        $js     = array("//cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.8.1/bootstrap-table.min.js",
                        "//cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.8.1/locale/bootstrap-table-fr-FR.min.js");

        $css    = array("//cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.8.1/bootstrap-table.min.css",
                        "/vendor/vw/framework/lib/table/css/table.css");
        */

        $js     = array("//cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.11.1/bootstrap-table.min.js",
                        "//cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.11.1/locale/bootstrap-table-fr-FR.min.js");

        $css    = array("//cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.11.1/bootstrap-table.min.css",
                        "/vendor/vw/framework/lib/table/css/table.css");

        parent::add_JsLib($js, $optimize);
        parent::add_CssLib($css, $optimize);
    }


    /**
     * Chargement de Bootstrap Table
     * http://bootstrap-table.wenzhixin.net.cn/documentation/
     */
    public static function add_bootstrapTableEn($optimize=false)
    {
        self::add_jQuery();
        self::add_bootstrap();

        $js     = array("//cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.11.1/bootstrap-table.min.js",
                        "//cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.11.1/locale/bootstrap-table-en-US.min.js");

        $css    = array("//cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.11.1/bootstrap-table.min.css",
                        "/vendor/vw/framework/lib/table/css/table.css");

        parent::add_JsLib($js, $optimize);
        parent::add_CssLib($css, $optimize);
    }


    /**
     * Chargement de ckEditor
     * http://ckeditor.com/
     */
    public static function add_ckEditor($optimize=false)
    {
        self::add_jQuery();

        $js     = array("/vendor/vw/framework/libExt/js/ckeditor/ckeditor.js");

        parent::add_JsLib($js, $optimize);
    }


    /**
     * Chargement de mousewheel
     * Librairie nécessaire à fancybox
     */
    public static function add_mousewheel($optimize=false)
    {
        self::add_jQuery();

        $js     = array("/vendor/vw/framework/libExt/js/fancyapps-fancyBox/lib/jquery.mousewheel-3.0.6.pack.js");

        parent::add_JsLib($js, $optimize);
    }


    /**
     * Chargement fancybox V2
     * http://fancyapps.com/fancybox/
     */
    public static function add_fancybox($optimize=false)
    {
        self::add_jQuery();
        self::add_mousewheel();

        $js     = array("/vendor/vw/framework/libExt/js/fancyapps-fancyBox/source/jquery.fancybox.pack.js?v=2.1.5");
        $css    = array("/vendor/vw/framework/libExt/js/fancyapps-fancyBox/source/jquery.fancybox.css?v=2.1.5");

        parent::add_JsLib($js, $optimize);
        parent::add_CssLib($css, $optimize);
    }


    /**
     * Chargement fancybox V3
     * https://fancyapps.com/fancybox/3/
     */
    public static function add_fancybox_V3($optimize=false)
    {
        self::add_jQuery();
        self::add_mousewheel();

        $js     = array("//cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.2/dist/jquery.fancybox.min.js");
        $css    = array("//cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.2/dist/jquery.fancybox.min.css");

        parent::add_JsLib($js, $optimize);
        parent::add_CssLib($css, $optimize);
    }


    /**
     * Colorateurs syntaxiques SyntaxHighlighter
     * http://alexgorbatchev.com/SyntaxHighlighter/
     */
    public static function add_syntaxHighlighter($optimize=false)
    {
        $js     = array("/vendor/vw/framework/libExt/js/syntaxhighlighter/scripts/shCore.js");
        $css    = array("/vendor/vw/framework/libExt/js/syntaxhighlighter/styles/shCoreDefault.css");

        parent::add_JsLib($js, $optimize);
        parent::add_CssLib($css, $optimize);
    }


    /**
     * Colorateurs syntaxiques SyntaxHighlighter - XML
     * http://alexgorbatchev.com/SyntaxHighlighter/
     */
    public static function add_syntaxHighlighter_XML($optimize=false)
    {
        self::add_syntaxHighlighter();

        $js     = array("/vendor/vw/framework/libExt/js/syntaxhighlighter/scripts/shBrushXml.js");

        parent::add_JsLib($js, $optimize);
    }


    /**
     * Colorateurs syntaxiques SyntaxHighlighter - Javascript
     * http://alexgorbatchev.com/SyntaxHighlighter/
     */
    public static function add_syntaxHighlighter_JS($optimize=false)
    {
        self::add_syntaxHighlighter();

        $js     = array("/vendor/vw/framework/libExt/js/syntaxhighlighter/scripts/shBrushJScript.js");

        parent::add_JsLib($js, $optimize);
    }


    /**
     * Librairie de gestion des graphiques - Highcharts
     * http://www.highcharts.com
     */
    public static function add_highCharts($optimize=false)
    {
        self::add_jQuery();

        // $js     = array("/vendor/vw/framework/libExt/js/Highcharts/js/highcharts.js",
        //                 "/vendor/vw/framework/libExt/js/Highcharts/js/modules/exporting.js");

        $js     = array("/vendor/vw/framework/libExt/js/Highcharts-7.0.2/code/highcharts.js",
                        "/vendor/vw/framework/libExt/js/Highcharts-7.0.2/code/modules/exporting.js");

        parent::add_JsLib($js, $optimize);
    }
}
