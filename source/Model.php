<?php
namespace SeanMorris\PressKit;
class Model extends \SeanMorris\Ids\Model
{
	protected $_selected, $_stub = false;

	protected static
	$byOwner = [
		'with' => ['state' => 'byOwner']
		// 'join' => [
		// 	'SeanMorris\PressKit\State' => [
		// 		'on'     => 'state'
		// 		, 'by'   => 'owner'
		// 		, 'type' => 'INNER'
		// 	]
		// ]
	];

	public function create()
	{
		if($this->_stub)
		{
			return;
		}

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

		$trace = debug_backtrace(TRUE);

		foreach($trace as $frame)
		{
			if(isset($frame['class']) && is_a($frame['class'], get_class(), TRUE)
				&& isset($frame['function']) && $frame['function'] === 'can'
			){
				return $instance;
			}
		}

		$instance->_selected = microtime(TRUE);

		if($instance->canHaveOne('state') && !$instance->can('read'))
		{
			\SeanMorris\Ids\Log::debug(
				\SeanMorris\Ids\Log::color(
					sprintf(
						'Permission check failed for %s'
						, get_class($instance)
					)
					, 'red'
					, 'black'
				)
			);

			return FALSE;
		}

		Listener::publish(
			sprintf(
				'model:loaded:%s:%d'
				, get_class($instance)
				, $instance->id
			)
			, $instance
		);

		return $instance;
	}

	public function forceSave()
	{
		$this->ensureState();

		if($this->id)
		{
			return parent::update();
		}

		$result = parent::create();

		return $result;
	}

	public function update()
	{
		if($this->_stub)
		{
			return;
		}

		if($this->can('update'))
		{
			Listener::publish(
				sprintf(
					'model:beforeUpdate:%s:%s'
					, get_class($this)
					, $this->id
				)
				, $this
			);

			$result = parent::update();

			Listener::publish(
				sprintf(
					'model:afterUpdate:%s:%s'
					, get_class($this)
					, $this->id
				)
				, $this
				, $result
			);

			return $result;
		}

		$exception = new \SeanMorris\PressKit\Exception\ModelAccessException(
			'Access denied - Cannot update model.'
		);

		\SeanMorris\Ids\Log::logException($exception);

		throw $exception;
		
	}

	public function delete($override = FALSE)
	{
		if($override || $this->can('delete'))
		{
			return parent::delete();
		}

		throw new \SeanMorris\PressKit\Exception\ModelAccessException(
			'Access denied - Cannot delete model.'
		);
	}

	public function can($action, $point = NULL)
	{
		// if($this->_stub && $action == 'read')
		// {
		// 	return true;
		// }

		if(php_sapi_name() == 'cli')
		{
			return true;
		}

		$user    = \SeanMorris\Access\Route\AccessRoute::_currentUser();
		$isAdmin = $user->hasRole('SeanMorris\Access\Role\Administrator');

		if($isAdmin)
		{
			return TRUE;
		}

		if(!isset(static::$hasOne['state']))
		{
			return TRUE;

			$isEditor = $user->hasRole('SeanMorris\Access\Role\Editor');

			// \SeanMorris\Ids\Log::debug("Model lacks state.");

			if($isAdmin || $isEditor || $action === 'read' || $action === 'view')
			{
				return true;
			}

			return $isAdmin;
		}

		\SeanMorris\Ids\Log::debug($this->state);

		if(!$this->id)
		{
			$state = $this->ensureState();
		}

		$stateClass = static::$hasOne['state'];
		$state      = $this->state;

		if(!is_object($state))
		{
			$state  = $this->getSubject('state');
		}

		// \SeanMorris\Ids\Log::debug(
		// 	sprintf(
		// 		'Checking %s(%d) has a state... '
		// 		, get_called_class()
		// 		, $this->id
		// 	)
		// 	, $state
		// );

		if(!$state || !is_object($state))
		{
			\SeanMorris\Ids\Log::error($state);
			// die;
			// ;

			if(is_array($state))
			{
				die;
			}

			if(!$state || !is_object($state))
			{
				\SeanMorris\Ids\Log::error(sprintf(
					'Cannot load state for %s(%d).'
					, get_called_class()
					, $this->id
				), $action, $point);

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

		// \SeanMorris\Ids\Log::debug(sprintf(
		// 	'Checking if user[%d] "%s" can %s'
		// 		. PHP_EOL
		// 		. '%s[%d] in state %s[%d] (%d)'
		// 	, $user->id
		// 	, $user->username
		// 	, $action . ($point
		// 		? sprintf(' property $%s on', $point)
		// 		: NULL
		// 	)
		// 	, get_called_class()
		// 	, $this->id
		// 	, get_class($state)
		// 	, $state->id
		// 	, $state->state
		// ), $allowed ? 1:0);

		// if($allowed && $point && isset($this->{$point}))
		// {
		// 	\SeanMorris\Ids\Log::debug('Content:', $this->{$point});
		// }

		return $allowed;
	}

	public static function canStatic($action)
	{
		if(php_sapi_name() == 'cli')
		{
			return true;
		}

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
		if(!$override && !$this->can('read', $column))
		{
			return [];
		}

		return parent::getSubjects($column, $override);
	}

	public function getSubject($column = NULL, $override = FALSE)
	{
		if($column == 'state'
			&& isset(static::$hasOne['state'])
			&& !is_subclass_of(static::$hasOne['state'], '\SeanMorris\PressKit\State')
		){
			if(!$override && !$this->can('read', $column))
			{
				return FALSE;
			}
		}
		else if($column !== 'state')
		{
			if(!$override && !$this->can('read', $column))
			{
				return FALSE;
			}
		}

		return parent::getSubject($column);
	}

	public function addSubject($property, $subject, $override = FALSE)
	{
		if($override || $this->can('add', $property))
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
			foreach($skeleton as $column => &$value)
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

			foreach($remove as $key)
			{
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

		foreach($remove as $key)
		{
			\SeanMorris\Ids\Log::debug(sprintf(
				'Removing disallowed key %s'
				, $key
			));

			unset($skeleton[$key]);
		}

		return $skeleton;
	}

	protected function ensureState($force = FALSE)
	{
		\SeanMorris\Ids\Log::debug(
			'ENSURE'
		);

		if($this->id && $this->state && !is_object($this->state))
		{
			$this->state = $this->getSubject('state', TRUE);
		}

		if(isset(static::$hasOne['state'])
			&& static::$hasOne['state']
			&& ((!$this->state || !is_object($this->state)) || $force)
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

			if($this->id)
			{
			}
			$state->save();
			// $this->state = $state->id;

			$this->state = $state;
			// $this->id && $this->postUpdate();

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
			// \SeanMorris\Ids\Log::debug('Nope.');
			return;
		}

		// \SeanMorris\Ids\Log::debug('ok.');

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
		$contentTypeString = sprintf(
			'content_type_t:%s'
			, addSlashes(get_called_class())
		);
		if($args['q'] ?? FALSE)
		{
			$args['q'] .= ' AND ' . $contentTypeString;
		}
		else
		{
			$args['q'] = $contentTypeString;
		}

		$queryString = http_build_query(
			$args = [
				'wt'               => 'json'
				, 'version'        => 2.2
			]                // Non overrideable defaults
			+ $args          // Supplied args
			+ ['rows' => 10] // Overrideable defaults
		);

		$client       = new \GuzzleHttp\Client();
		$class        = get_called_class();
		$solrSettings = static::solrSettings();
		$solrSettings = $solrSettings->endpoint->main;

		if(!$solrSettings)
		{
			return FALSE;
		}

		$url = sprintf(
			'http://%s:%d%s/select/?%s'
			, $solrSettings->host
			, $solrSettings->port
			, $solrSettings->path
			, $queryString
		);

		\SeanMorris\Ids\Log::debug(sprintf(
			'Solr Search URL: %s'
			, $url
		));

		$res = $client->request('GET', $url);

		if($res->getStatusCode() == 200)
		{
			$resp = json_decode($res->getBody());

			$resp = (object) $resp->response;

			$stubs = [];

			foreach($resp->docs as $doc)
			{
				$stubs[] = static::instantiateStub($doc);
			}

			$resp->docs = $stubs;

			return  $resp;
		}
	}

	public function stub()
	{
		// $structure = [];

		// $structure[ get_class($this) ] = [$this->publicId];

		// foreach($this->getProperties() as $property)
		// {
		// 	if(!$manyClass = $this->canHaveMany($property))
		// 	{
		// 		continue;
		// 	}

		// 	$subjects = $this->getSubjects($property, TRUE);

		// 	$subjectIds = array_map(
		// 		function($subject)
		// 		{
		// 			return $subject->publicId;
		// 		}
		// 		, $subjects
		// 	);

		// 	$structure[ $manyClass] = $subjectIds;
		// }

		// var_dump($structure);

		// die;

		$state = $this->getSubject('state', TRUE);

		return (object) [
			'id'                  => $this->id
			, 'publicId'          => $this->publicId
			, 'title'             => $this->title
			, '_selected_f'       => $this->_selected
			, 'content_type_t'    => get_class($this)
			, 'state_i'           => is_object($state)
				? $state->id
				: $state
			, 'state_s'           => is_object($state)
				? json_encode($state->unconsume(0))
				: NULL
		];
	}

	public function solrDocument($update)
	{
		$solrClient = static::solrClient();
		$document   = $update->createDocument();

		$stub = $this->stub();

		foreach($stub as $key => $value)
		{
			$document->addField($key, $value);
		}

		// $document->id             = $this->id;
		// $document->publicId       = $this->publicId;
		// $document->title          = $this->title;
		// $document->content_type_t = get_class($this);

		// $document->state_i        = is_object($this->state)
		// 	? $this->state->id
		// 	: $this->state;

		return $document;
	}

	public static function solrClear(array $args = [])
	{
		// $queryString = http_build_query(
		// 	$args = [
		// 		'wt'             => 'json'
		// 		, 'version'      => 2.2
		// 		, 'content_type_t' => get_called_class()
		// 	]                // Non overrideable defaults
		// 	+ $args          // Supplied args
		// 	+ ['rows' => 10] // Overrideable defaults
		// );

		$args = $args ? $args : ['*'=>'*'];

		$queryString = implode(';', array_map(
			function($key, $arg)
			{
				return sprintf('%s:%s', $key, $arg);
			}
			, array_keys($args)
			, $args
		));

		$client = new \GuzzleHttp\Client();

		$solrSettings = static::solrSettings();

		$solrSettings = $solrSettings->endpoint->main;

		if(!$solrSettings)
		{
			return FALSE;
		}

		$url = sprintf(
			'http://%s:%d%s/update'
			, $solrSettings->host
			, $solrSettings->port
			, $solrSettings->path
		);

		// $url = sprintf(
		// 	'http://%s:%d%s/update?stream.body=%s'
		// 	, $solrSettings->host
		// 	, $solrSettings->port
		// 	, $solrSettings->path
		// 	, urlencode(sprintf(
		// 		'<delete><query>%s</query></delete>'
		// 		, $queryString
		// 	))
		// );

		$queryString = sprintf(
			'<delete><query>%s</query></delete>'
			, $queryString
		);

		$res = $client->post($url, [
			'body'      => $queryString
			, 'headers' => [
				'Content-Type' => 'text/xml;charset=utf-8'
			]
		]);

		$res = $client->post($url, [
			'body'      => '<commit/>'
			, 'headers' => [
				'Content-Type' => 'text/xml;charset=utf-8'
			]
		]);

		if($res->getStatusCode() == 200)
		{
			$resp = json_decode($res->getBody());

			return (object) $resp;
		}
	}

	public function solrStore()
	{
		$update   = static::solrUpdateStart();
		$document = $this->solrDocument($update);

		\SeanMorris\Ids\Log::debug(sprintf(
			'Storing %s::[%d] in Solr...'
			, get_class($this)
			, $this->id
		), $document);

		$update->addDocument($document);

		$update->addCommit();

		return static::solrUpdateCommit($update);
	}

	public function solrDelete()
	{
		$update   = static::solrUpdateStart();
		$document = $this->solrDocument($update);

		$query = sprintf(
			'id:%d AND content_type_t:"%s"'
			, $this->id
			, addSlashes(get_called_class())
		);

		// var_dump($query);die;

		$update->addDeleteQuery($query);

		$update->addCommit();

		return static::solrUpdateCommit($update);
	}

	protected static function solrSettings()
	{
		$class = get_called_class();

		$solrSettings = FALSE;

		while($class)
		{
			if($solrSettings = \SeanMorris\Ids\Settings::read(
				'solr-cores'
				, $class
			)){
				break;
			}

			$class = get_parent_class($class);

		}

		if(!$solrSettings)
		{
			if(!$solrSettings = \SeanMorris\Ids\Settings::read('solr', 'endpoint', 'main'))
			{
				return FALSE;
			}
		}

		return $solrSettings;
	}

	protected static function solrClient()
	{
		static $solrSettings, $solrClient;

		if(!$solrSettings)
		{
			if($solrSettings = static::solrSettings())
			{
				$solrClient = new \Solarium\Client($solrSettings);
			}
		}

		return $solrClient;
	}

	public static function solrUpdateStart()
	{
		$solrClient = static::solrClient();

		return $solrClient->createUpdate();
	}

	public static function solrUpdateCommit($update)
	{
		$solrClient = static::solrClient();

		try
		{
			$result = $solrClient->update($update);
		}
		catch(\Solarium\Exception\HttpException $exception)
		{
			if($exception->getMessage() !== 'OK')
			{
				\SeanMorris\ids\Log::error($exception);
			}
			else
			{
				\SeanMorris\ids\Log::warn($exception);
			}

			return FALSE;
		}

		return TRUE;
	}

	public function toApi($depth = 0)
	{
		return $this->unconsume($depth);
	}

	protected static function instantiateStub($skeleton)
	{
		\SeanMorris\Ids\Log::debug(sprintf(
			'Instantiating stub for %s'
			, get_called_class()
		), $skeleton);

		$stub        = new static;

		$stub->_stub = true;
		$stub->state = $skeleton->state_i        ?? NULL;
		$stub->class = $skeleton->content_type_t ?? NULL;

		foreach($stub as $property => $value)
		{
			if(!isset($skeleton->{$property}))
			{
				continue;
			}

			if(is_scalar($skeleton->{$property}))
			{
				$stub->{$property} = $skeleton->{$property};
			}
			else if($skeleton->{$property} && in_array($property, [
				'publicId', 'title'
			])){
				$stub->{$property} = current($skeleton->{$property});

				continue;
			}

			if(!is_scalar($skeleton->{$property}))
			{
				if($subjectClass = $stub->canHaveOne($property))
				{
					$stub->{$property} = $subjectClass::instantiateStub(reset(
						$skeleton->{$property}
					));

					continue;
				}

				if(!$subjectClass = $stub->canHaveMany($property))
				{
					continue;
				}

				foreach($skeleton->{$property} as $subvalue)
				{
					$stub->{$property}[] = $subjectClass::instantiateStub($subvalue);
				}
			}
		}

		return $stub;
	}

	public function childIds($depth = 4)
	{
		// $structure = [get_called_class() => [$this->id]];

		$structure = [];

		if(!$depth)
		{
			return $structure;
		}

		foreach($this->getProperties() as $property)
		{
			if($oneClass = $this->canHaveOne($property))
			{
				if($subject = $this->getSubject($property, TRUE))
				{
					$structure[$oneClass][] = $subject->id;

					if($stateClass = $subject->canHaveOne('state'))
					{
						if(!($structure[$stateClass] ?? FALSE))
						{
							$structure[$stateClass] = [];
						}

						$stateId = $subject->state;

						if(is_object($stateId))
						{
							$stateId = $stateId->id;
						}

						$structure[$stateClass][] = $stateId;
					}

					if(is_callable([$subject, 'childIds']))
					{
						$structure = array_merge_recursive(
							$structure
							, $subject->childIds($depth - 1)
						);
					}					
				}
			}

			if($manyClass = $this->canHaveMany($property))
			{
				if(!($structure[$manyClass] ?? FALSE))
				{
					$structure[$manyClass] = [];
				}

				$subjects = $this->getSubjects($property, TRUE);

				foreach($subjects as $subject)
				{
					$structure[$manyClass][] = $subject->id;

					if($stateClass = $subject->canHaveOne('state'))
					{
						if(!($structure[$stateClass] ?? FALSE))
						{
							$structure[$stateClass] = [];
						}

						$stateId = $subject->state;

						if(is_object($stateId))
						{
							$stateId = $stateId->id;
						}

						$structure[$stateClass][] = $stateId;
					}

					if(is_callable([$subject, 'childIds']))
					{
						$structure = array_merge_recursive(
							$structure
							, $subject->childIds($depth - 1)
						);
					}
				}
			}
		}

		$structure = array_map('array_unique', $structure);

		// if($structure[get_called_class()])
		// {
		// 	$index = array_search(
		// 		$this->id
		// 		, $structure[get_called_class()]
		// 	);

		// 	if($index !== FALSE)
		// 	{
		// 		unset($structure[get_called_class()][$index]);
		// 	}
		// }

		return $structure;
	}

	public function redisStore()
	{
		$owner = $this->getSubject('owner', TRUE);

		$setKey = sprintf(
			'z-notifications;%s;uid:%d'
			, get_called_class()
			, $owner->id
		);

		$hashKey = sprintf(
			'h-notifications;%s;uid:%d'
			, get_called_class()
			, $owner->id
		);

		$redis = \SeanMorris\Ids\Settings::get('redis');

		$redis->zadd(
			$setKey
			, -$this->incremented
			, (int) $this->id
		);

		$redis->hset(
			$hashKey
			, (int) $this->id
			, $source = json_encode($this->stub())
		);

		return TRUE;
	}
}
