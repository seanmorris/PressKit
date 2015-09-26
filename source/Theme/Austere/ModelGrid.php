<?php
namespace SeanMorris\PressKit\Theme\Austere;
class ModelGrid extends Grid
{
	protected function preprocess()
	{
		$objects =& $this->vars['content'];
		$extraColumns =& $this->vars['extraColumns'];
		$path = $this->vars['path'];
		
		$this->vars['rows'] = isset($this->vars['rows'])
			? $this->vars['rows']
			: [];

		$rows =& $this->vars['rows'];
		
		// \SeanMorris\Ids\Log::debug($objects);

		foreach ($objects as $object)
		{
			foreach($this->vars['columns'] as $column)
			{
				$rows[$object->id][$column] = $object->{$column};
			}

			$rows[$object->id]['view'] = sprintf(
				'<a href = "/%s/%s">View</a>'
				, $path
				, $object->publicId
			);

			$rows[$object->id]['edit'] = sprintf(
				'<a href = "/%s/%s/edit">Edit</a>'
				, $path
				, $object->publicId
			);

			$rows[$object->id]['delete'] = sprintf(
				'<a href = "/%s/%s/delete">Delete</a>'
				, $path
				, $object->publicId
			);
		}

		$this->vars['columns']['view'] = NULL;
		$this->vars['columns']['edit'] = NULL;
		$this->vars['columns']['delete'] = NULL;

		if(!isset($this->vars['buttons']))
		{
			$this->vars['buttons'] = null;
		}
	}
}
