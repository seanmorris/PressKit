<?php
namespace SeanMorris\PressKit\Form;
class StateReferenceField extends \SeanMorris\Form\Fieldset
{
	public function __construct($fieldDef, $form)
	{
		$fieldDef['_array'] = TRUE;
		$fieldDef['_multi'] = FALSE;
		$fieldDef['_children']['id'] = [
			'_title' => 'Id'
			, 'value' => ''
			, 'type' => 'hidden'
		];
		$fieldDef['_children']['owner'] = [
			'_title'            => 'Owner'
			, '_subtitle'       => 'Owner'
			, 'type'            => 'modelSearch'
			, '_searchEndpoint' => '/user'
			, '_keywordField'   => 'keyword'
			, '_titlePoint'     => 'username'
			, '_array'          => TRUE
			, '_multi'          => FALSE
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
