<?php
namespace SeanMorris\PressKit\Form;
class ModelReferenceField extends \SeanMorris\Form\Fieldset
{
	public function __construct($fieldDef, $form)
	{
		$fieldDef['type'] = 'fieldset';

		$fieldDef['_multi'] = isset($fieldDef['_multi'])
			? $fieldDef['_multi']
			: false
		;

		$fieldDef['_titlePoint'] = isset($fieldDef['_titlePoint'])
			? $fieldDef['_titlePoint']
			: 'title'
		;

		$fieldDef['_previewImagePoint'] = isset($fieldDef['_previewImagePoint'])
			? $fieldDef['_previewImagePoint']
			: false
		;

		$subChildren = isset($fieldDef['_children'])
			? $fieldDef['_children']
			: []
		;

		$fieldDef['_array'] = TRUE;

		if($fieldDef['_multi'])
		{
			$children =& $fieldDef['_children'];

			$children = [
				'_title' => $fieldDef['_subtitle']
				, 'type' => 'fieldset'
				, '_array' => TRUE
				/*
				, '-PressKit-Widget' => 'ModelSearch'
				, '-PressKit-Search-Endpoint' => $fieldDef['_searchEndpoint']
				, '-PressKit-Title-Point' => $fieldDef['_titlePoint']
				, '-PressKit-Preview-Image-Point' => $fieldDef['_previewImagePoint']
					? $fieldDef['_previewImagePoint']
					: null
				*/
			];
		}
		else
		{
			$children =& $fieldDef;
		}

		$children['_children']['selected'] = [
			'class' => 'selectedModel'
			, 'type' => 'fieldset'
			, '-PressKit-Field' => 'indicator'
		];

		$children['_children']['selected']['_children']['container'] = [
			'type' => 'html'
			, 'value' => '<div class = "selection">test</div>'
		];

		/*
		$children['_children']['keyword'] = [
			'_title' => 'Search'
			, 'type' => 'text'
			, '-PressKit-Field' => 'search'
			, 'autocomplete' => 'off'
		];
		*/

		$children['_children']['id'] = [
			'_title' => 'id'
			, 'type' => 'text'
			, '-PressKit-Field' => 'id'
		];

		$children['_children']['class'] = [
			'_title' => 'class'
			, 'type' => 'hidden'
			, '-PressKit-Field' => 'class'
		];

		foreach($subChildren as $name => $subChild)
		{
			$children['_children'][$name] = $subChild;
		}

		if($fieldDef['_multi'])
		{
			$children['_children']['remove'] = [
				'value' => 'Remove'
				, 'type' => 'button'
				, '-button' => 'PressKit.FieldSetWidget.remove'
			];
		}

		parent::__construct($fieldDef, $form);
	}
}
