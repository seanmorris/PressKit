<?php
namespace SeanMorris\PressKit;
class Model extends \SeanMorris\Ids\Model
{
	//private $state;

	protected static
		$byOwner = [
			'join' => [
				'SeanMorris\PressKit\State' => [
					'on' => 'state'
					, 'by' => 'owner'
					, 'type' => 'INNER'
				]
			]
		];

	public function create()
	{
		$this->ensureState();
		
		if($this->can('create'))
		{
			return parent::create();
		}

		throw new \SeanMorris\PressKit\Exception\ModelAccessException(
			'Access denied - Cannot create model.'
		);
	}

	protected static function instantiate($skeleton, $args = [], $rawArgs = [])
	{
		$instance = parent::instantiate($skeleton, $args, $rawArgs);

		\SeanMorris\Ids\Log::debug([$instance->can('read'), !isset($instance->state)]);

		if($instance->can('read') || !isset($instance->state))
		{
			return $instance;
		}

		return FALSE;
	}

	public function forceSave()
	{
		if($this->id)
		{
			return parent::update();
		}

		return parent::create();
	}

	public function update()
	{
		if($this->can('update'))
		{
			return parent::update();
		}

		throw new \SeanMorris\PressKit\Exception\ModelAccessException(
			'Access denied - Cannot update model.'
		);
	}

	public function delete()
	{
		if($this->can('delete'))
		{
			return parent::delete();
		}

		throw new \SeanMorris\PressKit\Exception\ModelAccessException(
			'Access denied - Cannot delete model.'
		);
	}

	public function can($action, $point = NULL)
	{
		$user = \SeanMorris\Access\Route\AccessRoute::_currentUser();

		if(!isset(static::$hasOne['state']))
		{
			return true;
		}

		$stateClass = static::$hasOne['state'];
		$state = $this->getSubject('state');
		/*
		\SeanMorris\Ids\Log::debug(
			sprintf('Checking %s(%d) has a state... ', get_called_class(), $this->id)
			, $state ? 1 : 0			
		);
		*/

		if(!$state)
		{
			$state = $this->getSubject('state');

			if(!$state)
			{
				return FALSE;
			}
		}

		if($point)
		{
			$allowed = $state->can($user, '$' . $point, $action);
		}
		else
		{
			$allowed = $state->can($user, $action);

		}
		
		\SeanMorris\Ids\Log::debug(sprintf(
			'Checking if user "%s" can %s, %s'
				, $user->username
				, $action . ' ' . $point
				, get_called_class()
		), $allowed ? 1:0);

		return $allowed;
	}

	public static function canStatic($action)
	{	
		$user = \SeanMorris\Access\Route\AccessRoute::_currentUser();

		\SeanMorris\Ids\Log::debug(sprintf(
			'Checking if user %s can %s'
			, $user->username
			, $action
		));

		if(!isset(static::$hasOne['state']))
		{
			return true;
		}

		$state = new static::$hasOne['state'];
		$state->owner = $user->id;

		return $state->can($user, $action);
	}

	public function getSubjects($column)
	{
		if(!$this->can('read', $column))
		{
			return [];
		}

		return parent::getSubjects($column);
	}

	public function getSubject($column = NULL)
	{
		if( isset(static::$hasOne['state'])
			&& !is_subclass_of(static::$hasOne['state'], '\SeanMorris\PressKit\State')
		){
			if(!$this->can('read', $column))
			{
				return FALSE;
			}
		}
		
		return parent::getSubject($column);
	}

	public function addSubject($property, $subject, $override = FALSE)
	{
		if($this->can('add', $property) || $override)
		{
			return parent::addSubject($property, $subject);
		}
	}

	public function consume($skeleton, $override = false)
	{
		if(!$override)
		{
			$remove = [];
			foreach ($skeleton as $column => &$value)
			{
				if(!$this->can('write', $column))
				{
					$remove[] = $column;
				}
			}

			foreach ($remove as $key) {
				unset($skeleton[$key]);
			}
		}

		\SeanMorris\Ids\Log::debug($skeleton, $override, '!!!!!!!!!!!!!!!!!!');

		parent::consume($skeleton, $override);

		\SeanMorris\Ids\Log::debug($this);
	}

	public function unconsume($children = 0)
	{
		$skeleton = parent::unconsume($children);

		$remove = [];
		foreach ($skeleton as $column => &$value)
		{
			if(!$this->can('read', $column))
			{
				$remove[] = $column;
			}
		}

		foreach ($remove as $key) {
			unset($skeleton[$key]);
		}

		return $skeleton;
	}

	protected function ensureState()
	{
		if(isset(static::$hasOne['state'])
			&& static::$hasOne['state']
			&& !$this->state
			&& $owner = \SeanMorris\Access\Route\AccessRoute::_currentUser()
		){
			\SeanMorris\Ids\Log::debug(
				'Creating new state '
				. static::$hasOne['state']
				. ' for '
				. get_class($this)
			);
			$stateClass = static::$hasOne['state'];
			$state = new $stateClass;
			$state->consume([
				'owner' => $owner->id
			]);
			$state->save();
			$this->state = $state->id;
			$this->id && $this->forceSave();

			return $state;
		}	

		return $this->state;
	}

	public function __get($name)
	{
		/*
		if($name == 'state')
		{
			return;
		}
		*/

		// \SeanMorris\Ids\Log::debug('Trying to __get ' . $name);

		if(!$this->can('read', $name))
		{
			return;
		}
		
		return parent::__get($name);
	}

	public function __set($name, $value)
	{
		if(!$this->can('write', $name))
		{
			return;
		}

		$this->$name = $value;
	}
}