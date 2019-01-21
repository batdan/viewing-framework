<?php
namespace vw\plotTime;

/**
 * Rendu HTML
 *
 * @author Daniel Gomes
 */
class Stats_PageRenderer
{
	/**
	 * @var ZiGrid_Base
	 */
	protected $grid;


	/**
	 * Récupération des données à afficher
	 * @param Stats_Bases
	 */
	public function __construct(Stats_Bases $grid)
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
			$jsonTitle = json_encode(array('command'=>'setTitle', 'title'=>$title));

			$js = <<<eof
				(function($) {
				    $(function() {
				        ZiFramework.parentMessage($jsonTitle);
				    });
				})(jQuery);
eof;
		}

		return $this->grid->render();
	}
}
