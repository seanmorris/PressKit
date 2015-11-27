<?php
namespace SeanMorris\PressKit\Form;
class StateReferenceField extends ModelReferenceField
{
	public function __construct($fieldDef, $form)
	{
		$fieldDef['id']['type'] = 'hidden';

		parent::__construct($fieldDef, $form);
	}
}
