<?php
namespace vw\plotTime;

/**
 * Affichage HTML
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
		// ob_start();
		$i = \ZiIncluder::getInstance()->getIncludeManager();

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

			$i->addInlineScript($js);
		}

		return <<<eof
			<!DOCTYPE html>
			<html lang="fr">
			<head>
			    <meta charset="utf-8">
			    <title></title>
			    {$i->formatStylesheets()}
			</head>
			<body>
			{$this->grid->getBreadcrumb()->render()}
			{$this->grid->render()}
			{$i->formatScripts()}
			{$i->formatInlineScripts()}
			</body>
			</html>
eof;
	}
}
