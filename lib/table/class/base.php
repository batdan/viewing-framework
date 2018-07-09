<?php
namespace table;

/**
 * Bootstrap Table
 * Base permettant l'affichage et le fonctionnement d'un Bootstrap Table
 *
 * http://bootstrap-table.wenzhixin.net.cn/documentation/
 *
 * @author Daniel Gomes
 */

class base
{
    /**
     * Attributs
     */
    protected $_id;                               // Attribut id du tableau

    protected $_buttonAdd;                        // Ajouter une entrée - boutton additionnel
    protected $_urlAdd;                           // Url du formulaire

    protected $_buttonCsv;                        // Boolean : Export CSV - boutton additionnel

    protected $_title;                            // Titre du tableau

    protected $_fields;                           // Configuration de l'affichage des champs
    protected $_jsonModifier;                     // Permet de modifier des valeurs après la requête SQL dans le retour JSON
    protected $_csvModifier;                      // Permet de modifier des valeurs après la requête SQL dans le retour CSV
    protected $_champs;                           // Liste des champs récupérés depuis la requête

    protected $_width;                            // Largeur du tableau

    protected $_dataUrl;                          // Url pour le flux JSON
    protected $_dataPagination;                   // Boolean : Activation pagination
    protected $_dataSidePagination;               // Méthode de récupération du flux JSON -> Ajax : 'server'
    protected $_dataPageList;                     // Array : choix nb résultats / page
    protected $_dataPageSize;                     // Nombre de résultats par page au chargement
    protected $_dataSearch;                       // Boolean : Activation ou non du moteur de recherche
    protected $_dataSortName;                     // Forcer un champs pour le tri au chargement
    protected $_dataSortOrder;                    // Ordre de tri ( asc | desc ) -> par défaut 'asc'
    protected $_showColumns;                      // Activation de la gestion du masquage de colonnes
    protected $_showRefresh;                      // Affichage ou non du bouton 'refresh'

    protected $_tableExport;                      // Possibilité ou non d'exporter le tableau ( true | false )

    protected $_typeFlux;                         // JSON ou html

    protected $_limit;                            // Limit (json requête)
    protected $_offset;                           // Offset (à partir de)
    protected $_order;                            // champ order
    protected $_sort;                             // asc | desc
    protected $_search;                           // Moteur de recherche

    protected $_dom;							  // Gestion en dom du code généré
    protected $_container;						  // Conteneur de l'élément de formulaire


    /**
     * Constructeur
     */
    public function __construct(array $options = array())
    {
        // Retourne le tableau ou ses datas en JSON
        if (isset($_GET['json'])) {

            $this->_typeFlux = 'json';

            if (isset($_GET['limit'])) {
                $this->_limit   = $_GET['limit'];
            }
            if (isset($_GET['offset'])) {
                $this->_offset  = $_GET['offset'];
            }
            if (isset($_GET['order'])) {
                $this->_order   = $_GET['order'];
            }
            if (isset($_GET['sort'])) {
                $this->_sort    = $_GET['sort'];
            }

            if (isset($_GET['search'])) {
                $this->_search  = $_GET['search'];
            }
        }

        // Retourne un csv du tableau
        if (isset($_GET['csv'])) {
            $this->_typeFlux = 'csv';
        }

        // Retourne le tableau ou ses datas en JSON
        if (! isset($_GET['json']) && ! isset($_GET['csv'])) {
            // DOM
            $this->_dom = new \DOMDocument("1.0", "utf-8");
            $this->_typeFlux = 'html';
        }

        $options = array_merge($this->getDefaultOptions(), $options);
        $this->setOptions($options);
    }


    /**
     * Initialisation des options par défaut
     */
    protected function getDefaultOptions()
    {
        return array(
                    'id'                            => 'table',

                    'buttonAdd'                     => true,
                    'urlAdd'                        => '',

                    'buttonCsv'                     => true,

                    'title'                         => '',

                    'req'                           => '',
                    'dataSet'                       => '',

                    'fields'                        => '',
                    'jsonModifier'                  => '',
                    'csvModifier'                   => '',

                    'width'                         => '',

                    'data-url'                      => '',
                    'data-pagination'               => 'true',
                    'data-side-pagination'          => 'server',
                    'data-page-list'                => '[5, 10, 20, 50, 100, 200]',
                    'data-page-size'                => '10',
                    'data-search'                   => 'true',
                    'data-sort-name'                => '',
                    'data-sort-order'               => 'asc',
                    'data-show-columns'             => 'false',
                    'data-show-refresh'             => 'false',

                    'data-table-export'             => '',
        );
    }


    /**
     * Récupération des options du data table
     */
    protected function setOptions($options)
    {
        $this->_id                      = $options['id'];

        $this->_title                   = $options['title'];

        $this->_buttonAdd               = $options['buttonAdd'];
        $this->_urlAdd                  = $options['urlAdd'];

        $this->_buttonCsv               = $options['buttonCsv'];

        $this->_req                     = $options['req'];
        $this->_dataSet                 = $options['dataSet'];

        $this->_fields                  = $options['fields'];
        $this->_jsonModifier            = $options['jsonModifier'];
        $this->_csvModifier             = $options['csvModifier'];

        $this->_width                   = $options['width'];

        $this->_dataUrl                 = $options['data-url'];
        $this->_dataPagination          = $options['data-pagination'];
        $this->_dataSidePagination      = $options['data-side-pagination'];
        $this->_dataPageList            = $options['data-page-list'];
        $this->_dataPageSize            = $options['data-page-size'];
        $this->_dataSearch              = $options['data-search'];
        $this->_dataSortName            = $options['data-sort-name'];
        $this->_dataSortOrder           = $options['data-sort-order'];
        $this->_showColumns             = $options['data-show-columns'];
        $this->_showRefresh             = $options['data-show-refresh'];

        $this->_tableExport             = $options['data-table-export'];
    }


    /**
     * Permet de filtrer l'action de la classe, générer le code HTML ou le flux de data en JSON
     */
    public function rendu()
    {
        if ($this->_typeFlux == 'html') {
            return $this->getHtml();
        }
        if ($this->_typeFlux == 'json') {
            return $this->getJson();
        }
        if ($this->_typeFlux == 'csv') {
            return $this->getCsv();
        }
    }


    /**
     * Création du code HTML permettant l'initialisation du tableau
     */
    protected function getHtml()
    {
        // Container
        $container = $this->_dom->createElement('div');

        // Permière ligne
        if ($this->_buttonCsv === true || ! empty($this->_title)) {

            $firstLine = $this->_dom->createElement('div');
            $firstLine->setAttribute('class', 'col-lg-8');
            $firstLine->setAttribute('style', 'position:absolute; margin-top:0; margin-left:0');

            // Boutons supplémentaire (possibiliter d'ajouter de nouveaux boutons)
            if ($this->_buttonAdd === true || $this->_buttonCsv === true) {

                $buttonsGrp = $this->_dom->createElement('div');
                //$buttonsGrp->setAttribute('style', ' margin-right:10px;');
                $buttonsGrp->setAttribute('class', 'btn-group');
                $buttonsGrp->setAttribute('style', 'position:relative; top:-11px; left:-15px;');

                if ($this->_buttonAdd === true) {

                    $buttonAdd = $this->_dom->createElement('button');
                    $buttonAdd->setAttribute('type', 'button');
                    $buttonAdd->setAttribute('class', 'btn btn-default');
                    $buttonAdd->setAttribute('onclick', "document.location.href = '" . $this->_urlAdd . "';");
                    $buttonAdd->setAttribute('title', 'Ajouter une entrée');

                    $buttonAddIcon = $this->_dom->createElement('i');
                    $buttonAddIcon->setAttribute('class', 'fa fa-plus');

                    $buttonAdd->appendChild($buttonAddIcon);
                    $buttonsGrp->appendChild($buttonAdd);
                }

                if ($this->_buttonCsv === true) {

                    $buttonCsv = $this->_dom->createElement('button');
                    $buttonCsv->setAttribute('type', 'button');
                    $buttonCsv->setAttribute('class', 'btn btn-default');

                    if (strstr($this->_dataUrl, '?')) {
                        $sepGetCsv = '&';
                    } else {
                        $sepGetCsv = '?';
                    }

                    $buttonCsv->setAttribute('onclick', "window.open('" . $this->_dataUrl . $sepGetCsv . "csv');");
                    $buttonCsv->setAttribute('title', 'Export CSV');

                    $buttonCsvIcon = $this->_dom->createElement('i');
                    $buttonCsvIcon->setAttribute('class', 'fa fa-download');

                    $buttonCsv->appendChild($buttonCsvIcon);
                    $buttonsGrp->appendChild($buttonCsv);
                }

                $firstLine->appendChild($buttonsGrp);
            }

            // Titre du tableau
            if (! empty($this->_title)) {
                $titleTable = $this->_dom->createElement('div');
                $titleTable->setAttribute('style', 'display:inline-block; position:relative; top:-5px;');
                $titleTableh3 = $this->_dom->createElement('h3');
                $titleTableTxt = $this->_dom->createTextNode($this->_title);

                $titleTable->appendChild($titleTableh3);
                $titleTableh3->appendChild($titleTableTxt);
                $firstLine->appendChild($titleTable);
            }

            $container->appendChild($firstLine);
        }

        // Largeur du tableau
        if (! empty($this->_width)) {
            $container->setAttribute('style', 'width:' . $this->_width);
        }

        // Table
        $table = $this->_dom->createElement('table');
        $table->setAttribute('id',                          $this->_id);
        $table->setAttribute('class',                       'table table-striped table-condensed');
        $table->setAttribute('data-toggle',                 'table');

        if (strpos($this->_dataUrl, '?') !== false) {
            $expUrl = explode('?', $this->_dataUrl);
            $table->setAttribute('data-url',                $expUrl[0] . '?json&' . $expUrl[1]);
        } else {
            $table->setAttribute('data-url',                $this->_dataUrl . '?json');
        }

        $table->setAttribute('data-pagination',             $this->_dataPagination);
        $table->setAttribute('data-side-pagination',        $this->_dataSidePagination);
        $table->setAttribute('data-page-list',              $this->_dataPageList);
        $table->setAttribute('data-page-size',              $this->_dataPageSize);
        $table->setAttribute('data-search',                 $this->_dataSearch);
        $table->setAttribute('data-sort-name',              $this->_dataSortName);
        $table->setAttribute('data-sort-order',             $this->_dataSortOrder);
        $table->setAttribute('data-show-columns',           $this->_showColumns);
        $table->setAttribute('data-show-refresh',           $this->_showRefresh);

        if (! empty($this->_dataHeight)) {
            // $table->setAttribute('data-height',      '700');

        }

        $thead = $this->_dom->createElement('thead');

        $tr = $this->_dom->createElement('tr');

        // Options par défault d'affichage d'une colonne / champ
        $defaultOptionsChamp = array('label'    => '',
                                     'sortable' => true,
                                     'halign'   => 'center',
                                     'align'    => 'center',
                                     'width'    => '',
                                     'visible'  => 'true',
        );

        // Boucle sur les libellés du tableau
        foreach ($this->_fields as $field) {

            $optionsChamps = array_merge($defaultOptionsChamp, $field);

            $th = $this->_dom->createElement('th');
            $th->setAttribute('data-field',     $optionsChamps['name']);
            $th->setAttribute('data-sortable',  $optionsChamps['sortable']);
            $th->setAttribute('data-halign',    $optionsChamps['halign']);
            $th->setAttribute('data-align',     $optionsChamps['align']);
            $th->setAttribute('data-width',     $optionsChamps['width']);
            $th->setAttribute('data-visible',   $optionsChamps['visible']);

            $thText = $this->_dom->createTextNode($optionsChamps['label']);
            $th->appendChild($thText);
            $tr->appendChild($th);
        }

        $thead->appendChild($tr);
        $table->appendChild($thead);
        $container->appendChild($table);
        $this->_dom->appendChild($container);

        $this->_dom->formatOutput = true;

        // Permet de rendre tooltip utilisable après chargement du tableau
        // DOMSubtreeModified deprecated -> si ne marche plus, trouver une solution avec DOM MutationObserver
        $js = <<<eof
var numberOfRows = $("#{$this->_id}>tbody>tr").length;
$("#{$this->_id}").bind("DOMSubtreeModified", function() {
    if ($("#{$this->_id}>tbody>tr").length !== numberOfRows) {
        numberOfRows = $("#{$this->_id}>tbody>tr").length;
        $('[title]').tooltip({ container: 'body' });
    }
});
eof;
        \core\libIncluder::add_JsScript($js);

        return $this->_dom->saveHTML();
    }
}
