<?php
namespace SeanMorris\PressKit\Form;
class ImageSearchForm extends \SeanMorris\Form\Form
{
	public function __construct()
	{
		$skeleton = [];

		$skeleton['_method'] = 'GET';

		$skeleton['search'] = [
			'_title' => 'Search'
			, 'type' => 'fieldset'
		];

		$skeleton['search']['_children']['keyword'] = [
			'_title' => 'Title'
			, 'type' => 'text'
		];

		$skeleton['search']['_children']['id'] = [
			'type' => 'hidden'
		];

		$skeleton['submit'] = [
			'_title' => 'Submit'
			, 'type' => 'submit'
		];

		parent::__construct($skeleton);
	}
}