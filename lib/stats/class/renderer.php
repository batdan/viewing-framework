<?php
namespace vw\stats;

use core\libIncluder;

/**
 * Rendu HTML
 *
 * @author Daniel Gomes
 */
class renderer
{
	/**
	 * @var base
	 */
	protected $grid;


	/**
	 * Récupération des données à afficher
	 * @param base
	 */
	public function __construct(base $grid)
	{
		$this->grid = $grid;
	}


	/**
	 * Création du rendu
	 * @return string
	 */
	public function render()
	{
		$title = $this->grid->getTitle();

		if (!empty($title)) {

			$js = <<<eof
				(function($) {
				    $(function() {
						$('title').html('$title')
				    });
				})(jQuery);
eof;

			libIncluder::add_JsScript($js);
		}


		return $this->grid->render();
	}
}
