<?php
namespace vw\stats;

/**
 * Rendu HTML
 *
 * @author Daniel Gomes
 */
class renderer
{
	/**
	 * @var ZiGrid_Base
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
