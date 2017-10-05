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
		
		if($actionFunctions)
		{
			$this->vars['columns'] = ['select' => NULL]
				+ $this->vars['columns'];
		}

		$vars['createLink'] = NULL;

		if($controller->_modelClass()::canStatic('update'))
		{
			$vars['createLink'] = sprintf(
				'<a href = "%s/create">Create</a>'
				, $path ? '/' . $path : NULL
			);
		}

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
				'<a href = "%s/%s">View</a>'
				, $path ? '/' . $path : NULL
				, $id
			);

			$this->vars['columns']['view'] = NULL;
			
			if($object->can('update'))
			{
				$this->vars['columns']['edit'] = NULL;
				$rows[$object->id]['edit'] = sprintf(
					'<a href = "%s/%s/edit">Edit</a>'
					, $path ? '/' . $path : NULL
					, $id
				);
			}

			$rows[$object->id]['delete'] = NULL;

			if($object->can('delete'))
			{
				$this->vars['columns']['delete'] = NULL;
				$rows[$object->id]['delete'] = sprintf(
					'<a href = "%s/%s/delete">Delete</a>'
					, $path ? '/' . $path : NULL
					, $id
				);
			}
		}

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

		$this->vars['prevPageLink'] = NULL;
		$this->vars['nextPageLink'] = NULL;

		if($this->vars['page'] > 0)
		{
			$this->vars['prevPageLink'] = sprintf(
				'<a href = "?page=%d">&lt; prev</a>'
				, $this->vars['page'] - 1
			);
		}

		$this->vars['nextPageLink'] = sprintf(
			'<a href = "?page=%d">next &gt;</a>'
			, $this->vars['page'] + 1
		);
	}
}
__halt_compiler();
?>
<form method = "POST" action = "/<?=$path;?>" class = "sublime">
	<?=$grid; ?>
	<?=$createLink; ?><br />
	<?=$prevPageLink; ?>
	<?=$nextPageLink; ?><br />
	Displaying <?=$pageSize * $page + 1; ?> - <?=$pageSize * ($page + 1); ?>/<?=$count ;?> records.
</form>
