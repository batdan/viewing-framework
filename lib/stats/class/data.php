<?php
namespace vw\stats;

/**
 * Création d'une statisque sur la base d'un tableau PHP
 *
 * @author Daniel Gomes
 */
class data extends base
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
