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
			'type' => 'radios',
			'value' => FALSE,
			'_options' => [
				'Yes' => TRUE
				, 'No' => FALSE
			]
		];

		$skeleton['submit'] = [
			'_title' => 'Submit',
			'type' => 'submit',
		];

		parent::__construct($skeleton);
	}
}