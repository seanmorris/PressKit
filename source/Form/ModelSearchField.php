<?php
namespace SeanMorris\PressKit\Form;
class ModelSearchField extends \SeanMorris\Form\Fieldset
{
	public function __construct($fieldDef, $form)
	{
		$fieldDef['type'] = 'fieldset';

		$fieldDef['_multi'] = isset($fieldDef['_multi'])
			? $fieldDef['_multi']
			: FALSE
		;

		$fieldDef['_titlePoint'] = isset($fieldDef['_titlePoint'])
			? $fieldDef['_titlePoint']
			: 'title'
		;

		$fieldDef['_previewImagePoint'] = isset($fieldDef['_previewImagePoint'])
			? $fieldDef['_previewImagePoint']
			: FALSE
		;

		$fieldDef['_array'] = isset($fieldDef['_array'])
			? $fieldDef['_array']
			: FALSE
		;

		$subChildren = [];

		$subChildren = isset($fieldDef['_children'])
			? $fieldDef['_children']
			: []
		;

		$children =& $fieldDef['_children'];

		if($fieldDef['_multi'])
		{
			$fieldDef['_children'] = [
				'_title'                          => $fieldDef['_subtitle']
				, 'type'                          => 'fieldset'
				, '_array'                        => $fieldDef['_array']
				, '-PressKit-Widget'              => 'ModelSearch'
				, '-PressKit-Search-Endpoint'     => $fieldDef['_searchEndpoint']
				, '-PressKit-Title-Point'         => $fieldDef['_titlePoint']
				, '-PressKit-Preview-Image-Point' => $fieldDef['_previewImagePoint']
					? $fieldDef['_previewImagePoint']
					: null
			];

			$childFields =& $fieldDef['_children']['_children'];
		}
		else
		{
			$childFields =& $fieldDef['_children'];

			$fieldDef += [
				'-PressKit-Widget'                => 'ModelSearch'
				, '-PressKit-Search-Endpoint'     => $fieldDef['_searchEndpoint']
				, '-PressKit-Title-Point'         => $fieldDef['_titlePoint']
				, '-PressKit-Preview-Image-Point' => $fieldDef['_previewImagePoint']
					? $fieldDef['_previewImagePoint']
					: null
			];
		}

		$keywordFieldName = 'keyword';

		if(isset($fieldDef['name']) && !(isset($fieldDef['_multi']) || !$fieldDef['_multi']))
		{
			$keywordFieldName = $fieldDef['name'];
		}

		if(isset($fieldDef['_keywordField']))
		{
			$keywordFieldName = $fieldDef['_keywordField'];
		}

		$childFields[$keywordFieldName] = [
			'_title' => 'Search'
			, 'type' => 'text'
			, '-PressKit-Field' => 'search'
			, 'autocomplete' => 'off'
		];

		$fieldDef['_children']['-PressKit-Keyword-Field'] = $keywordFieldName;

		$childFields['id'] = [
			'_title' => 'id'
			, 'type' => 'text'
			, '-PressKit-Field' => 'id'
		];

		$childFields['class'] = [
			'_title' => 'class'
			, 'type' => 'text'
			, '-PressKit-Field' => 'class'
		];

		foreach($subChildren as $name => $subChild)
		{
			$childFields[$name] = $subChild;
		}

		// $childFields['selected'] = ($childFields['selected'] ?? []) + [
		// 	'class' => 'selectedModel'
		// 	, 'type' => 'fieldset'
		// 	, '-PressKit-Field' => 'indicator'
		// ];

		// $childFields['selected']['_children']['container'] = [
		// 	'type' => 'html'
		// 	, 'value' => '<div class = "selection">test</div>'
		// ];

		if(isset($fieldDef['_multi']) && $fieldDef['_multi'])
		{
			$childFields['remove'] = [
				'value' => 'Remove'
				, 'type' => 'button'
				, '-button' => 'PressKit.FieldSetWidget.remove'
			];
		}

		parent::__construct($fieldDef, $form);
	}

	public function attrs()
	{
		$attrs = parent::attrs();

		$attrs['data-endpoint']        = $this->fieldDef['_searchEndpoint'] ?? NULL;
		$attrs['data-create-endpoint'] = $this->fieldDef['_createEndpoint'] ?? NULL;

		return $attrs;
	}
}
