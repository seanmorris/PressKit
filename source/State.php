<?php
namespace SeanMorris\PressKit;
class State extends \SeanMorris\Ids\Model
{
	protected
		$id
		, $publicId
		, $state = 0
		, $owner
	;

	protected static
		$states	= [
			0 => [
				'create'	=> 'SeanMorris\Access\Administrator'
				, 'read'	 => TRUE
				, 'update'	 => [TRUE, FALSE]
				, 'delete'	 => FALSE
			]
		]
		, $transitions	= []
		// , $transitions	= [
		// 	0 => [
		// 		1 => 32
		// 	]
		// 	, 1 => [
		// 		0 => -1
		// 	]
		// ]

		, $hasOne = [
			'owner' => 'SeanMorris\Access\User'
		]

		, $table = 'StateFlowState'
		, $createColumns = [
			'publicId' => 'UNHEX(REPLACE(UUID(), "-", ""))'
		]
		, $readColumns = [
			'publicId' => 'HEX(%s)'
		]
		, $updateColumns = [
			'publicId' => 'UNHEX(%s)'
		]
		, $byId = [
			'where'   => [['id' => '?']]
			, 'index' => ['class']
		]
		, $byIds = [
			'where'   => [['id' => '?', 'IN', '%s', 'id', FALSE]]
			, 'index' => ['class']
		]
		, $byNull = [
			'index' => ['class']
		]
		, $byModerated = [
			'where' => ['AND' => [['state' => 0, '>']]]
			// Also good: 'where' => [['state' => '0', '>']]
		]
		, $byOwner = [
			'where'   => [['owner' => '?']]
			, 'index' => ['class']
		]
		, $byOwnerNamed = [
			'named' => TRUE
			, 'index' => ['class']
			, 'where' => [
				['owner' => '?', '=', '%s', 'owner', FALSE]
			]
		]
		, $byStateNamed = [
			'named' => TRUE
			, 'where' => [
				['state' => '?', '=', '%s', 'state', FALSE]
			]
		]
	;

	public function can($user, $point, $action = 'read')
	{
		$result = $this->_can($user, $point, $action);

		\SeanMorris\Ids\Log::debug([
			'subclass' => get_called_class(),
			'$point'  => $point,
			'$action' => $action,
			'$result' => $result,
// 			'$user'   => $user
		]);

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

		$userArray = $user->unconsume(FALSE, TRUE);

		\SeanMorris\Ids\Log::debug(sprintf(
			'Checking if %s[%d] can %s%s on %s[%d]'
			, get_class($user)
			, $userArray['id']
			, $action
			,  ($pointCheck
				? sprintf(' (on %s)', $point)
				: NULL)
			, get_class($this)
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

			\SeanMorris\Ids\Log::error(sprintf('Cannot, [0] (%s).', $point));
			return FALSE;
		}
		elseif(!isset(static::$states[$this->state][$point]))
		{
			if($pointCheck && $action === 'read')
			{
				\SeanMorris\Ids\Log::debug('Can.');
				return TRUE;
			}

			\SeanMorris\Ids\Log::error(sprintf('Cannot, [1] (%s, %s).', $point, $action));
			return FALSE;
		}

		$role = static::$states[$this->state][$point];

		\SeanMorris\Ids\Log::debug(
			sprintf('Role needed for %s %s:', $action, $point)
			, $role
			, sprintf(
				'Current State: %s[%d]::{%d}'
				, static::class
				, $this->id
				, is_object($this->state)
					? $this->state->id
					: $this->state
			)
		);

		$isOwner = FALSE;

		if(is_array($role) && $pointCheck)
		{
			$role = $role[$action] ?? FALSE;
		}

		if(is_array($role))
		{
			if(!isset($role[0], $role[1]))
			{
				\SeanMorris\Ids\Log::debug('Cannot, [2].');
				return false;
			}

			$owner = NULL;

			if($this->owner)
			{
				$owner = $this->getSubject('owner', TRUE);
			}

			$owner && \SeanMorris\Ids\Log::debug(sprintf(
				'Checking if user[%d] "%s" is owner user[#%d]... %d'
				, $user->id
				, $user->username
				, $owner->id
				, $owner->isSame($user)
			));

			if($owner && $owner->isSame($user))
			{
				$role    = $role[0];
				$isOwner = TRUE;
			}
			else
			{
				$role = $role[1];
			}
		}

		if(is_numeric($role) || is_bool($role))
		{
			if($role == 1)
			{
				\SeanMorris\Ids\Log::debug('Can.');
				return TRUE;
			}
			else if($role == 0)
			{
				\SeanMorris\Ids\Log::debug('Cannot, [3].');
				return FALSE;
			}

			if($role < 0)
			{
				\SeanMorris\Ids\Log::debug('Cannot, [4].');
				return FALSE;
			}
		}

		if(!isset($user))
		{
			return false;
		}

		\SeanMorris\Ids\Log::debug(
			'Checking for role "'
			. print_r($role, 1)
			. '"... '
			. (int)$user->hasRole($role)
		);

		if($user->hasRole($role))
		{
			\SeanMorris\Ids\Log::debug('Can.');
			return true;
		}

		\SeanMorris\Ids\Log::debug('Cannot, [5].');

		return false;
	}

	public function change($to, $force = FALSE)
	{
		if($force || $this->canChange($to))
		{
			$this->__set('state', $to);
			return true;
		}

		return false;
	}

	public function canChange($to)
	{
		\SeanMorris\Ids\Log::debug(sprintf(
			'Trying to change %s from %s to %d.'
			, get_called_class()
			, $this->state
			, $to
		));

		$user = \SeanMorris\Access\Route\AccessRoute::_currentUser();

		if(!isset(static::$transitions[$this->state][$to])
			&& $parent = static::getParent($this)
		){
			if($super = $parent->canChange($to))
			{
				return $super;
			}

			if($user->hasRole('\SeanMorris\Access\Role\Administrator'))
			{
				return TRUE;
			}
			else
			{
				return FALSE;
			}

		}
		elseif(!isset(static::$transitions[$this->state][$to]))
		{
			if($user->hasRole('\SeanMorris\Access\Role\Administrator'))
			{
				return TRUE;
			}
			else
			{
				return FALSE;
			}
		}

		$role = static::$transitions[$this->state][$to];

		if(is_array($role))
		{
			if(!isset($role[0], $role[1]))
			{
				return FALSE;
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

		\SeanMorris\Ids\Log::debug(sprintf(
			'Role needed to change %s from %s to %d: %s.'
			, get_called_class()
			, $this->state
			, $to
			, $role
		));

		if($role < 0)
		{
			return FALSE;
		}

		if($user->hasRole($role))
		{
			return TRUE;
		}

		return FALSE;
	}

	protected static function getParent($state)
	{
		$parentClass = get_parent_class($state);

		if(is_a(get_parent_class($state), 'PressKit\\State', true))
		{
			$parent = new $parentClass;

			$parent->consume([
				'id'      => $state->id
				, 'owner' => $state->owner
				, 'state' => $state->state
			], true);

			return $parent;
		}

		return false;
	}

	public function consume($skeleton, $override = false)
	{
		if($override && isset($skeleton['state']))
		{
			$this->__set('state', $skeleton['state']);
		}

		if(!$override && isset($skeleton['state']))
		{
			$this->change($skeleton['state']);
		}

		unset($skeleton['state']);

		parent::consume($skeleton, $override);
	}

	// public function __set($name, $value)
	// {
	// 	if($name == 'state')
	// 	{
	// 		if($this->canChange($value))
	// 		{
	// 			$this->__set('state', $value);
	// 		}
	// 	}
	// 	else
	// 	{
	// 		parent::__set($name, $value);
	// 	}
	// }

	public function unconsume($children = NULL)
	{
		if($children === NULL)
		{
			return parent::unconsume(1);
		}

		return parent::unconsume($children);
	}
}
