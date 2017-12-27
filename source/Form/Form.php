<?php
namespace SeanMorris\PressKit\Form;
class Form extends \SeanMorris\Form\Form
{
	protected static
		$typesToClasses = [
			'modelSearch' => 'SeanMorris\PressKit\Form\ModelSearchField'
			, 'modelReference' => 'SeanMorris\PressKit\Form\ModelReferenceField'

		]
	;

	protected $originalSkeleton = [];

	public function __construct(array $skeleton = [])
	{
		$user = \SeanMorris\Access\Route\AccessRoute::_currentUser();

		if(isset($skeleton['_access']))
		{
			$role = key($skeleton['_access']);
			$alt = current($skeleton['_access']);

			if(!$user->hasRole($role))
			{
				$skeleton = $alt;
			}
		}

		$this->originalSkeleton = $skeleton;
		$skeleton = $this->processPermissions($skeleton);

		parent::__construct($skeleton);
	}

	protected function processPermissions($skeleton)
	{
		$user = \SeanMorris\Access\Route\AccessRoute::_currentUser();

		foreach($skeleton as $name => &$fieldDef)
		{
			if($name[0] === '_')
			{
				continue;
			}

			$failAlt = FALSE;

			if(isset($fieldDef['_access']))
			{
				if(isset($fieldDef['_access']['read']))
				{
					$readRole = key($fieldDef['_access']['read']);
					$readAlt = current($fieldDef['_access']['read']);

					if($readRole !== 1  && !$user->hasRole($readRole))
					{
						$failAlt = $readAlt;
					}
				}

				if(($failAlt === FALSE) && isset($fieldDef['_access']['write']))
				{
					$writeRole = key($fieldDef['_access']['write']);
					$writeAlt = current($fieldDef['_access']['write']);

					if($writeRole !== 1 && !$user->hasRole($writeRole))
					{
						$failAlt = $writeAlt;
					}
				}
			}

			if($failAlt !== FALSE)
			{
				if(is_array($failAlt))
				{
					$fieldDef = array_merge($fieldDef, $failAlt);
				}
				else
				{
					$fieldDef = [
						'type'    => 'html'
						, 'value' => $failAlt
					];
				}
			}

			if(isset($fieldDef['_children']))
			{
				$fieldDef['_children'] = $this->processPermissions($fieldDef['_children']);
			}
		}

		return $skeleton;
	}

	public function setValues(array $values = [], $override = false)
	{
		if(!$override)
		{
			$values = $this->processSetValuePermisions($values, $this->originalSkeleton);	
		}
		
		return parent::setValues($values, $override);
	}

	protected function processSetValuePermisions($values, $originalSkeleton)
	{
		$remove = [];

		foreach($values as $fieldName => $fieldValue)
		{
			if(isset($skeleton['_access']))
			{
				if(isset($originalSkeleton[$fieldName]['_access'])
					&& isset($originalSkeleton[$fieldName]['_access']['_access']['write'])
				){
					$role = key($originalSkeleton[$fieldName]['_access']['write']);

					if(!$user->hasRole($role))
					{
						$remove[] = $fieldName;
					}
					else if(isset($originalSkeleton['_children'])
						&& is_array($values[$fieldName])
					){
						$values[$fieldName] = processSetValuePermisions($values[$fieldName], $originalSkeleton['_children']);
					}
				}
			}
		}

		foreach($remove as $key)
		{
			unset($values[$key]);
		}

		return $values;
	}

	public function toStructure()
	{
		$sub = function($fields) use(&$sub)
		{
			$structure = (object) [];

			foreach($fields as $name => $field)
			{
				$structure->$name = (object) [
					'name'      => $field->fullname()
					, 'title'   => $field->title()
					, 'value'   => NULL
					, 'type'    => $field->type()
					, 'attrs'   => $field->attrs()
					, 'options' => $field->options()
				];

				$fieldValue = $field->value();

				if(is_scalar($fieldValue) || is_null($fieldValue))
				{
					$structure->$name->value = $fieldValue;
				}
				else
				{
					//print_r([$field->fullname() => $field->value()]);
					$structure->$name->children = $sub($field->fields());
				}
			}

			return $structure;
		};

		return (object) $sub($this->fields);
	}
}