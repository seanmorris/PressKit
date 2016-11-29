<?php
namespace SeanMorris\PressKit\Form;
class StateReferenceField extends \SeanMorris\Form\Fieldset
{
	public function __construct($fieldDef, $form)
	{
		$fieldDef['_array'] = true;
		$fieldDef['_children']['id'] = [
			'_title' => 'Id'
			, 'value' => ''
			, 'type' => 'hidden'
		];
		$fieldDef['_children']['state'] = [
			'_title' => 'State'
			, 'value' => ''
			, 'type' => 'select'
			, '_options' => [
				0 => 'Unpublished'
				, 1 => 'Published'
			]
		];
		parent::__construct($fieldDef, $form);
	}
}
