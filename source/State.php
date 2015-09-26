<?php
namespace SeanMorris\PressKit;
class State extends \SeanMorris\Ids\Model
{
	protected
		$id
		, $state
		, $owner
	;

	protected static
		$states	= [
			0 => [
				'create'	=> 'SeanMorris\Access\Administrator'
				, 'read'	 => 0
				, 'update'	 => [0, -1]
				, 'delete'	 => -1

				, '$title'	=> [
					'write'  => -1
					, 'read' => 0
				]
				, '$publicId'	=> [
					'write'  => -1
					, 'read' => 0
				]
				, '$written'	=> [
					'write'  => -1
					, 'read' => 0
				]
				, '$edited'		=> [
					'write'  => -1
					, 'read' => 0
				]
			]
			, 1 => [
				'read'	 => ['SeanMorris\Access\Administrator',0]
				, '$title'	=> [
					'write'  => 0
					, 'read' => 0
				]
			]
		]
		, $transitions	= [
			0 => [
				1 => 32
			]
			, 1 => [
				0 => -1
			]
		]
		
		,$table = 'StateFlowState'
		, $byId = [
			'where' => [['id' => '?']]
		]
		, $readColumns = [
			'owner' => 'HEX(%s)'
		]
		, $updateColumns = [
			'owner' => 'UNHEX(%s)'
		]
		, $byModerated = [
			'where' => [['state' => '-1', '>']]
		]
		, $byStateNamed = [
			'where' => [
				['state' => '?', '=', '%s', 'ssss', TRUE]
			]
		]
	;

	public function can($user, $point, $action = 'read')
	{
		$result = $this->_can($user, $point, $action);

		//\SeanMorris\Ids\Log::debug([get_called_class() . '::can', $point, $action, $result]);

		if($action == 'write')
		{
			//\SeanMorris\Ids\Log::trace();
		}

		return $result;
	}

	// Action is only considered when dealing with properties
	// Properties are defined by $point =~ /^\$/
	protected function _can($user, $point, $action = 'read')
	{
		$pointCheck = substr($point, 0, 1) == '$';

		\SeanMorris\Ids\Log::debug(sprintf(
			'Checking if %s can %s%s during state %s'
			, $user->username
			, $action
			,  ($pointCheck
				? sprintf(' (on %s)', $point)
				: NULL)
			, $this->state
		));

		if(!isset(static::$states[$this->state][$point])
			&& $parent = static::getParent($this)
		){
			\SeanMorris\Ids\Log::debug($parent);
			if($super = $parent->can($point, $action))
			{
				return $super;
			}

			return false;
		}
		elseif(!isset(static::$states[$this->state][$point]))
		{
			return false;
		}

		$role = static::$states[$this->state][$point];

		if($pointCheck)
		{
			$role = $role[$action];
		}

		if(is_array($role))
		{
			if(!isset($role[0], $role[1]))
			{
				return false;
			}

			\SeanMorris\Ids\Log::debug('Checking ' . $this->owner . ' and ' . $user->publicId);

			if($this->owner == $user->publicId)
			{
				$role = $role[0];
			}
			else
			{
				$role = $role[1];
			}
		}

		\SeanMorris\Ids\Log::debug('Checking for ' . $role);

		if($role === 0)
		{
			return true;
		}
		else if($role === -1)
		{
			return false;
		}

		if($role < 0)
		{
			return false;
		}

		if(!isset($user))
		{
			return false;
		}

		\SeanMorris\Ids\Log::debug('Has role? ' . (int)$user->hasRole($role));

		if($user->hasRole($role))
		{
			return true;
		}

		return false;
	}

	public function change($to)
	{
		if($this->canChange($to))
		{
			$this->state = $to;
			return true;
		}

		return false;
	}

	public function canChange($to)
	{
		$user = $_SESSION['user'];

		if(!isset(static::$transitions[$this->state][$to])
			&& $parent = static::getParent($this)
		){
			if($super = $parent->canChange($to))
			{
				return $super;
			}

			return false;
		}
		elseif(!isset(static::$transitions[$this->state][$to]))
		{
			return false;
		}

		$role = static::$transitions[$this->state][$to];

		if(is_array($role))
		{
			if(!isset($role[0], $role[1]))
			{
				return false;
			}

			if($this->owner == $user->publicId)
			{
				$role = $role[$action][0];
			}
			else
			{
				$role = $role[$action][1];
			}
		}

		if($role < 0)
		{
			return false;
		}

		if($user->hasRole($role))
		{
			return true;
		}

		return false;
	}

	protected static function getParent($state)
	{
		$parentClass = get_parent_class($state);
		
		if(is_a(get_parent_class($state), 'PressKit\\State', true))
		{
			$parent			= new $parentClass;
			$parent->id 	= $state->id;
			$parent->state	= $state->state;
			$parent->owner	= $state->owner;

			return $parent;
		}

		return false;
	}
}