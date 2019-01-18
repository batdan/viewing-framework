<?php
namespace vw\plotTime;

/**
 * Création d'une statisque sur la base d'un tableau
 */
class Stats_Array extends Stats_Bases
{
	/**
	 * @var array
	 */
	protected $data;

	protected function getDefaultOptions()
	{
		return array_merge(parent::getDefaultOptions(), array(
			'data' => array()
		));
	}

	protected function initOptions($options)
	{
		parent::initOptions($options);
		$this->type = 'array';

		$this->data = $options['data'];
	}
}
