<?php
namespace SeanMorris\PressKit\Form;
class ModelDeleteForm extends \SeanMorris\PressKit\Form\Form
{
	public function __construct()
	{
		$skeleton = [];

		$skeleton['_method'] = 'POST';

		$skeleton['delete'] = [
			'_title' => 'Delete?',
			'type' => 'select',
			'value' => 0,
			'_options' => [
				NULL     => NULL
				, 'Yes'  => 1
				, 'No'   => 0
			]
		];

		$skeleton['submit'] = [
			'_title'  => 'Submit'
			, 'value' => 'Submit'
			, 'type'  => 'submit'
		];

		parent::__construct($skeleton);
	}
}
