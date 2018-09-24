<?php
namespace SeanMorris\PressKit\Form;
class ModelDeleteForm extends \SeanMorris\PressKit\Form\Form
{
	public function __construct()
	{
		$skeleton = [];

		$skeleton['_method'] = 'POST';

		$skeleton['del'] = [
			'_title' => 'Delete?',
			'type' => 'fieldset',
		];

		$skeleton['del']['_children']['delete'] = [
			'_title' => NULL,
			'type' => 'select',
			'value' => '0',
			'_options' => [
				'Yes'  => 1
				, 'No' => 0
			]
		];

		$skeleton['submit'] = [
			'_title' => 'Submit',
			'type' => 'submit',
		];

		parent::__construct($skeleton);
	}
}