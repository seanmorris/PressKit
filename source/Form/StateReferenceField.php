<?php
namespace SeanMorris\PressKit\Form;
class StateReferenceField extends \SeanMorris\Form\Fieldset
{
	public function __construct($fieldDef, $form)
	{
		$fieldDef['_array'] = TRUE;
		$fieldDef['type']   = 'fieldset';
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
				'Unpublished' => 0
				, 'Published' => 1
			]
		];
		parent::__construct($fieldDef, $form);
	}
}
