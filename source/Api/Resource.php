<?php
namespace SeanMorris\PressKit\Api;
class Resource
{
	protected
		$code         = 0
		, $body       = NULL
		, $meta       = NULL
		, $navigation = []
		, $messages   = []
		, $controller = NULL
		, $router     = NULL
		, $time       = NULL

		, $model      = []
		, $models     = []

		, $lightLoad = false
	;

	protected static $depth = 3;

	public function __construct($router, $more = [], $code = 0)
	{
		$controller = $router->routes();

		$this->code = $code;

		$this->controller = $controller;
		$this->router     = $router;

		$objectIds = [];

		if(isset($more['body']))
		{
			$this->body = $more['body'];
		}
		else if($controller instanceof \SeanMorris\PressKitController
			&& $models = $controller->_models())
		{
			$this->models($models);
		}
		else if($controller instanceof \SeanMorris\PressKitController
			&& $model = $controller->_model())
		{
			$this->model($model);
		}

		$realPath = $router->path()->getAliasedPath()->pathString();
		$currentPath = $router->path()->pathString();

		if($router->parent())
		{
			$this->navigation['.'] = '/' . $currentPath;

			if($parentPath = $router->path()->getAliasedPath()->pop()->pathString())
			{
				$this->navigation['..'] = '/' . $parentPath;
			}

			if(isset($more['navigation']))
			{
				$this->navigation += $more['navigation'];
			}
		}

		if($controller instanceof \SeanMorris\PressKitController
			&& $controller->routes
		){
			foreach($controller->routes as $path => $class)
			{
				$this->navigation[$path] = sprintf('/%s/%s', $currentPath, $path);
			}
		}

		if($controller instanceof \SeanMorris\PressKitController
			&& $controller->subRoutes
		){
			foreach($controller->subRoutes as $path => $class)
			{
				$this->navigation[$path] = sprintf('/%s/%s', $currentPath, $path);
			}
		}

		foreach($objectIds as $value)
		{
			$this->navigation[$value] = sprintf('/%s/%s', $realPath, $value);
		}

		unset($this->navigation['view']);
	}

	protected function toStructure($type, $depth = NULL)
	{
		if($this->models)
		{
			$this->body = $this->processObjects(
				$this->models
				, $type
				, $depth
			);

			$this->meta['count'] = count($this->models);

			foreach($this->models as $object)
			{
				if(isset($object->publicId))
				{
					$objectIds[$object->publicId] = $object->publicId;
				}
			}
		}
		else if($this->model)
		{
			$this->body = $this->processObject($this->model, $type);
		}

		$this->meta('currentUser', FALSE);

		if($user = \SeanMorris\Access\Route\AccessRoute::_currentUser())
		{
			if($user->id)
			{
				$value = $user->toApi(1);

				$value['_permissions'] = [
					'read'     => true
					, 'update' => $user->can('update')
					, 'delete' => $user->can('delete')
				];

				$this->meta('currentUser', $value);
			}
		}

		$messages = \SeanMorris\Message\MessageHandler::get()->flash();

		foreach($messages as $message)
		{
			if($message instanceof \SeanMorris\Message\ErrorMessage)
			{
				$this->code = $this->code ? $this->code : 1;
			}
		}

		$this->messages += array_map(
			function($msg)
			{
				return $msg->text();
			},
			$messages
		);

		return (object)[
			'code'			=> $this->code
			// , 'controller'	=> get_class($this->controller)
			, 'messages'	=> $this->messages
			, 'meta'		=> $this->meta
			, 'body'		=> $this->body
			, 'navigation'	=> $this->navigation
			// , 'time'        => round(microtime(true) - START, 4)
			// , 'sessionId'   => session_id()
		];
	}

	protected function processObject($object, $type = NULL, $index = NULL, $parent = NULL, $property = NULL, $skipSubjects = [], $depth = NULL)
	{
		if($depth === NULL)
		{
			$depth = 0;

			// if($parent === NULL && $index === NULL)
			if($parent === NULL)
			{
				$depth = static::$depth;
			}
		}

		$value = NULL;
		$value = [];

		switch(TRUE)
		{
			case $object instanceof \SeanMorris\PressKit\Model:

				// $value = $object->toApi($this->lightLoad ? 0 : $depth);

				$value = $object->toApi($depth);
				if($depth > 0)
				{

					foreach($value as $k => &$v)
					{
						if(!($subjectClass = $object::getSubjectClass($k))
							|| ($skipSubjects[$k] ?? FALSE)
						) {
							continue;
						}

						if($this->lightLoad)
						{
							continue;
						}

						if(!$this->lightLoad && is_object($vv = $object->getSubject($k)))
						{
							$v = $this->processObject($vv, $type, $k, $object, $k, [], $depth - 1);
							// if(is_object($v)/* && $vv instanceof \SeanMorris\PressKit\Model*/)
							// {
							// 	$v = $this->processObject($vv, $type, $k, $object, $k, $depth-1);
							//}
							/*
							else if(is_object($vv) && $vv instanceof \SeanMorris\Ids\Model)
							{
								$v = $vv->unconsume();
							}*/

						}
						else if(!$this->lightLoad && is_array($vv = $object->getSubjects($k)))
						{
							$v  = [];

							foreach($vv as $kk => $subject)
							{
								$v[] = $this->processObject($subject, $type, $kk, $object, $k, [], $depth - 1);
							}
						}
					}
				}

				if(is_a($object->canHaveOne('state'), 'SeanMorris\PressKit\State', true))
				{
					if($readPerm = $object->can('read'))
					{
						$value['_permissions'] = [
							'read'     => $readPerm
							, 'update' => $object->can('update')
							, 'delete' => $object->can('delete')
						];
					}
				}

				break;

			case $object instanceof \SeanMorris\Access\State\UserState:
				$depth = 0;

			case $object instanceof \SeanMorris\PressKit\State:

				$value = [];

				if($user = \SeanMorris\Access\Route\AccessRoute::_currentUser())
				{
					if($object->owner === $user->id
						|| (is_object($object->owner) && $user->id == $object->owner->id)
						|| $user->hasRole('\SeanMorris\Access\Role\Administrator')
					){
						$value = $object->unconsume($depth);
					}
				}

				break;
		}

		return $value;
	}

	protected function processObjects($objects, $type = NULL, $depth = NULL)
	{
		return array_map(
			function($o, $i) use($type, $depth)
			{
				return $this->processObject($o, $type, $i, NULL, NULL, NULL, $depth);
			}
			, $objects
			, array_keys($objects)
		);
	}

	public function toJson($depth = NULL)
	{
		$struct = $this->toStructure('json', $depth);

		$res = json_encode($struct);

		if($res === FALSE)
		{
			\SeanMorris\Ids\Log::error($struct, json_last_error_msg());
			return;
		}

		return $res;
	}

	public function toXml($depth = NULL)
	{
		return \xmlrpc_encode($this->toStructure('xml', $depth));
	}


	public function toBob($depth = NULL)
	{
		$struct = $this->toStructure('bob', $depth);

		// return \SeanMorris\Bob\Bank::encode(['a'=>1,'b'=>2]);
		return \SeanMorris\Bob\Bank::encode($struct);
	}

	public function toYaml($depth = NULL)
	{
		$struct  = $this->toStructure('yaml', $depth);
		$toArray = function($x) use(&$toArray)
		{
			return is_scalar($x)
				? $x
				: array_map($toArray, (array) $x);
		};

		return yaml_emit(
			$toArray($struct)
			, YAML_UTF8_ENCODING
		);
	}

	public function toHtml()
	{
		return $this->controller->_renderList($this->router);
	}

	public function encode($type, $depth = NULL)
	{
		if($type == 'xml')
		{
			header('Content-Type: application/xml');
			return $this->toXml($type, $depth);
		}
		elseif($type == 'bob')
		{
			// header('Content-Type: application/octet-stream');
			return $this->toBob($type, $depth);
		}
		elseif($type == 'yaml')
		{
			header('Content-Type: text/yaml');
			return $this->toYaml($type, $depth);
		}
		else
		{
			header('Content-Type: application/json');
			return $this->toJson($type, $depth);
		}
	}

	public function decode($type)
	{

	}

	public function meta($key, $value)
	{
		$this->meta[$key] = $value;

		return $this->meta;
	}

	public function body($body)
	{
		return $this->body = $body;
	}

	public function models($models)
	{
		// $this->body = $this->processObjects($models);
		$this->models = $models;
	}

	public function model($model)
	{
		// $this->body = $this->processObject($model);
		$this->model = $model;
	}

	public function lightLoad($set = true)
	{
		$this->lightLoad = $set;
	}

	public function __toString()
	{
		return $this->encode($_GET['api'] ?? 'json');
	}
}
