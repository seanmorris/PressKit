<?php
namespace SeanMorris\PressKit\Theme\Austere;
class ModelGrid extends Grid
{
	protected function preprocess(&$vars)
	{
		parent::preprocess($vars);

		$controller = $vars['_controller'];
		$actionFunctions = $controller->_actions($vars['_router']);
		$objects =& $this->vars['content'];
		$extraColumns =& $this->vars['extraColumns'];
		$path = $this->vars['path'];
		
		$this->vars['rows'] = isset($this->vars['rows'])
			? $this->vars['rows']
			: [];

		$rows =& $this->vars['rows'];
		
		// \SeanMorris\Ids\Log::debug($objects);

		if($actionFunctions)
		{
			$this->vars['columns'] = ['select' => NULL]
				+ $this->vars['columns'];
		}

		$vars['createLink'] = NULL;

		$vars['createLink'] = sprintf(
			'<a href = "/%s/create">Create</a>'
			, $path
		);

		$rows = [];
		
		foreach($objects as $object)
		{
			if($actionFunctions)
			{
				$rows[$object->id]['select'] = sprintf(
					'<input name = "models[]" type = "checkbox" value = "%s" />'
					, $object->publicId
				);
			}

			foreach($this->vars['columns'] as $column)
			{
				$rows[$object->id][$column] = $object->{$column};
			}
			
			$id = $object->publicId
				? $object->publicId
				: $object->id;

			$rows[$object->id]['view'] = sprintf(
				'<a href = "/%s/%s">View</a>'
				, $path
				, $id
			);

			$rows[$object->id]['edit'] = NULL;
			
			if($object->can('update'))
			{
				$rows[$object->id]['edit'] = sprintf(
					'<a href = "/%s/%s/edit">Edit</a>'
					, $path
					, $id
				);
			}

			$rows[$object->id]['delete'] = NULL;

			if($object->can('delete'))
			{
				$rows[$object->id]['delete'] = sprintf(
					'<a href = "/%s/%s/delete">Delete</a>'
					, $path
					, $id
				);
			}
		}

		$this->vars['columns']['view'] = NULL;
		$this->vars['columns']['edit'] = NULL;
		$this->vars['columns']['delete'] = NULL;

		if(!isset($this->vars['buttons']))
		{
			$this->vars['buttons'] = null;
		}

		$this->vars['actions'] = NULL;

		if($actionFunctions)
		{
			$this->vars['actions'] = '<label>Actions<select name = "action" class = "inline"></label>';

			foreach($actionFunctions as $action => $function)
			{
				$this->vars['actions'] .= sprintf('<option>%s</option>', $action);
			}

			$this->vars['actions'] .= '</select><input class = "inline" type = "submit" />';
		}

		$this->vars['grid'] = static::render([], 1);
	}
}
__halt_compiler();
?>
<form method = "POST" action = "/<?=$path;?>" class = "sublime"><?=$grid;?><br /><?=$createLink;?></form>
