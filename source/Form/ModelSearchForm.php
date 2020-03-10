<?php
namespace SeanMorris\PressKit\Form;
class ModelSearchForm extends \SeanMorris\PressKit\Form\Form
{
	public function __construct()
	{
		$skeleton = [];

		$skeleton['_method'] = 'POST';

		$skeleton['id'] = [
			'type' => 'hidden'
		];

		$skeleton['publicId'] = [
			'type' => 'hidden'
		];

		$skeleton['keyword'] = [
			'type' => 'hidden'
		];

		parent::__construct($skeleton);
	}
}
