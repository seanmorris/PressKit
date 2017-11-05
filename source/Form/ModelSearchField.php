<?php
namespace SeanMorris\PressKit\Form;
class ModelSearchField extends \SeanMorris\Form\Fieldset
{
	public function __construct($fieldDef, $form)
	{
		$fieldDef['type'] = 'fieldset';
		
		$fieldDef['_multi'] = isset($fieldDef['_multi'])
			? $fieldDef['_multi']
			: true
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

		$fieldDef['_array'] = isset($fieldDef['_array'])
			? $fieldDef['_array']
			: []
		;

		$children =& $fieldDef['_children'];

		$fieldDef['_children'] = [
			'_title' => $fieldDef['_subtitle']
			, 'type' => 'fieldset'
			, '_array' => $fieldDef['_array']
			, '-PressKit-Widget' => 'ModelSearch'
			, '-PressKit-Search-Endpoint' => $fieldDef['_searchEndpoint']
			, '-PressKit-Title-Point' => $fieldDef['_titlePoint']
			, '-PressKit-Preview-Image-Point' => $fieldDef['_previewImagePoint']
				? $fieldDef['_previewImagePoint']
				: null
		];

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

		$children['_children']['selected']['_children']['clear'] = [
			'type' => 'button'
			, 'value' => 'Clear'
		];

		*/

		$keywordFieldName = 'keyword';

		if(!$fieldDef['_multi'])
		{
			$keywordFieldName = $fieldDef['name'];
		}

		if(isset($fieldDef['_keywordField']))
		{
			$keywordFieldName = $fieldDef['_keywordField'];
		}
		
		$children['_children'][$keywordFieldName] = [
			'_title' => 'Search'
			, 'type' => 'text'
			, '-PressKit-Field' => 'search'
			, 'autocomplete' => 'off'
		];

		$fieldDef['_children']['-PressKit-Keyword-Field'] = $keywordFieldName;

		$children['_children']['id'] = [
			'_title' => 'id'
			, 'type' => 'hidden'
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
		else
		{
			$children = [$children];
		}

		parent::__construct($fieldDef, $form);
	}
}