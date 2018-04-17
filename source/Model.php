<?php
namespace SeanMorris\PressKit;
class Model extends \SeanMorris\Ids\Model
{
	protected $_selected;

	protected static
	$byOwner = [
		'join' => [
			'SeanMorris\PressKit\State' => [
				'on'     => 'state'
				, 'by'   => 'owner'
				, 'type' => 'INNER'
			]
		]
	];

	public function create()
	{
		\SeanMorris\Ids\Log::debug(sprintf(
			'Trying to create %s.'
			, get_called_class()
		));

		$this->ensureState();

		if($this->can('create'))
		{
			\SeanMorris\Ids\Log::debug(sprintf(
				'Can create %s.', get_called_class()
			));

			return parent::create();
		}

		\SeanMorris\Ids\Log::debug(sprintf(
			'Cannot create %s.'
			, get_called_class()
		));

		throw new \SeanMorris\PressKit\Exception\ModelAccessException(
			'Access denied - Cannot create model.'
		);
	}

	protected static function instantiate($skeleton, $args = [], $rawArgs = [])
	{
		if(!$instance = parent::instantiate($skeleton, $args, $rawArgs))
		{
			return;
		}

		$instance->_selected = microtime(TRUE);

		// \SeanMorris\Ids\Log::debug([
		// 	$instance->can('read')
		// 	, !isset($instance->state)
		// ]);

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

		$exception = new \SeanMorris\PressKit\Exception\ModelAccessException(
			'Access denied - Cannot update model.'
		);

		\SeanMorris\Ids\Log::logException($exception);

		throw $exception;
		
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
			\SeanMorris\Ids\Log::debug("Model lacks state.");

			if($action === 'read' || $action === 'view')
			{
				return true;
			}

			return $user->hasRole('SeanMorris\Access\Role\Administrator');
		}

		$stateClass = static::$hasOne['state'];
		$state = $this->getSubject('state');
		/*
		\SeanMorris\Ids\Log::debug(
			sprintf('Checking %s(%d) has a state... ', get_called_class(), $this->id)
			, $state
		);
		*/

		if(!$state)
		{
			$state = $this->ensureState();

			if(!$state)
			{
				\SeanMorris\Ids\Log::error(sprintf('Cannot load state for %s(%d).', get_called_class(), $this->id));
				return FALSE;
			}
		}
		else
		{
			/*
			\SeanMorris\Ids\Log::debug(
				sprintf('%s(%d) has a state... ', get_called_class(), $this->id)
				, $state ? 1 : 0
			);
			*/
		}

		if(php_sapi_name() == 'cli')
		{
			return TRUE;
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
			'Checking if user[%d] "%s" can %s'
				. PHP_EOL
				. '%s[%d] in state %s[%d] (%d)'
			, $user->id
			, $user->username
			, $action . ($point
				? sprintf(' property $%s on', $point)
				: NULL
			)
			, get_called_class()
			, $this->id
			, get_class($state)
			, $state->id
			, $state->state
		), $allowed ? 1:0);

		if($allowed && $point && isset($this->{$point}))
		{
			\SeanMorris\Ids\Log::debug('Content:', $this->{$point});
		}

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
			\SeanMorris\Ids\Log::debug('No state control, can create.');
			return true;
		}

		\SeanMorris\Ids\Log::debug('Checking new state instance...');

		$state = new static::$hasOne['state'];
		$state->owner = $user->id;

		return $state->can($user, $action);
	}

	public function getSubjects($column, $override = FALSE)
	{
		if(!$this->can('read', $column) && !$override)
		{
			return [];
		}

		return parent::getSubjects($column);
	}

	public function getSubject($column = NULL, $override = FALSE)
	{
		if($column == 'state'
			&& isset(static::$hasOne['state'])
			&& !is_subclass_of(static::$hasOne['state'], '\SeanMorris\PressKit\State')
		){
			if(!$this->can('read', $column) && !$override)
			{
				return FALSE;
			}
		}
		else if($column !== 'state')
		{
			if(!$this->can('read', $column) && !$override)
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
		\SeanMorris\Ids\Log::debug(sprintf(
				'Consuming skeleton for model of type %s' . ($override ? ' OVERRIDE PERMISSIONS!' : NULL)
				, get_called_class()
			)
			, $skeleton
		);

		if(!$override)
		{
			$remove = [];
			foreach ($skeleton as $column => &$value)
			{
				//\SeanMorris\Ids\Log::debug('COLUMN:' . $column, $this->can('write', $column) ? 1:0);
				if(!$this->can('write', $column))
				{
					$remove[] = $column;
				}
			}

			\SeanMorris\Ids\Log::debug(sprintf(
					'Stripping disallowed columns for model of type %s'
					, get_called_class()
				)
				, $remove
			);

			foreach ($remove as $key) {
				unset($skeleton[$key]);
			}
		}

		\SeanMorris\Ids\Log::debug(
			'Skeleton', $skeleton
			, 'Override', (int) $override
		);

		parent::consume($skeleton, $override);

		\SeanMorris\Ids\Log::debug($this);
	}

	public function unconsume($children = 0, $override = FALSE)
	{
		$skeleton = parent::unconsume($children);

		$remove = [];

		foreach($skeleton as $column => &$value)
		{
			if(!$override && !$this->can('read', $column))
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
		){
			\SeanMorris\Ids\Log::debug(
				'Creating new state '
				. static::$hasOne['state']
				. ' for '
				. get_class($this)
			);
			$stateClass = static::$hasOne['state'];
			$state = new $stateClass;
			$owner = \SeanMorris\Access\Route\AccessRoute::_currentUser();
			$state->consume([
				'owner' => $owner ? $owner->id : NULL
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

	public static function solrSearch(array $args = [])
	{
		$queryString = http_build_query(
			$args = [
				'wt'             => 'json'
				, 'version'      => 2.2
				, 'content_type' => get_called_class()
			]                // Non overrideable defaults
			+ $args          // Supplied args
			+ ['rows' => 10] // Overrideable defaults
		);

		$client = new \GuzzleHttp\Client();

		if(!$solrSettings = \SeanMorris\Ids\Settings::read('solr', 'endpoint', 'main'))
		{
			return FALSE;
		}

		$res = $client->request('GET', sprintf(
			'http://%s:%d%sselect/?%s'
			, $solrSettings->host
			, $solrSettings->port
			, $solrSettings->path
			, $queryString
		));

		if($res->getStatusCode() == 200)
		{
			$resp = json_decode($res->getBody());
			$resp = (array) $resp->response;

			return (object) $resp;
		}
	}

	public function toApi($depth = 0)
	{
		return $this->unconsume($depth);
	}
}
