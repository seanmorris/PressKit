<?php
namespace SeanMorris\PressKit\Api;
class Resource
{
	protected
		$code = 0
		, $body = NULL
		, $meta = NULL
		, $navigation = []
		, $messages = []
		, $controller = NULL
		, $router = NULL

		, $model = []
		, $models = []
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
		else if($models = $controller->_models())
		{
			$this->models($models);
		}
		else if($model = $controller->_model())
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

		if($controller->routes)
		{
			foreach($controller->routes as $path => $class)
			{
				$this->navigation[$path] = sprintf('/%s/%s', $currentPath, $path);
			}
		}

		if($controller->subRoutes)
		{
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

		$messages = \SeanMorris\Message\MessageHandler::get();

		$this->messages += array_map(
			function($msg)
			{
				return $msg->text();
			},
			$messages->flash()
		);

		return (object)[
			'code'			=> $this->code
			, 'controller'	=> get_class($this->controller)
			, 'messages'	=> $this->messages
			, 'meta'		=> $this->meta
			, 'body'		=> $this->body
			, 'navigation'	=> $this->navigation
			, 'sessionId'   => session_id()
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

		switch(TRUE)
		{
			case $object instanceof \SeanMorris\PressKit\Model:

				$value = $object->toApi($depth);

				foreach($value as $k => &$v)
				{
					if($depth <= 0)
					{
						continue;
					}

					if(!($subjectClass = $object::getSubjectClass($k))
						|| ($skipSubjects[$k] ?? FALSE)
					) {
						continue;
					}

					if(is_object($vv = $object->getSubject($k)))
					{
						$v = $this->processObject($vv, $type, $k, $object, $k, [], $depth-1);
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
					else if(is_array($vv = $object->getSubjects($k)))
					{
						$v  = [];

						foreach($vv as $kk => $subject)
						{
							$v[] = $this->processObject($subject, $type, $kk, $object, $k, [], $depth - 1);
						}
					}
				}

				$value['_permissions'] = [
					'read'     => $object->can('read')
					, 'update' => $object->can('update')
					, 'delete' => $object->can('delete')
				];

				break;

			case $object instanceof \SeanMorris\PressKit\State:

				$value = $object->unconsume(0);

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

	public function toJson($type = 'json', $depth = NULL)
	{
		$struct = $this->toStructure($type, $depth);
		$res = json_encode($struct, JSON_PRETTY_PRINT);

		if($res === FALSE)
		{
			\SeanMorris\Ids\Log::error($struct, json_last_error_msg());
			return;
		}

		return $res;
	}

	public function toXml($type = 'xml', $depth = NULL)
	{
		return \xmlrpc_encode($this->toStructure($type, $depth));
	}

	public function toYaml($type = 'yaml', $depth = NULL)
	{
		$struct  = $this->toStructure($type, $depth);
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
		$this->models = $models;		
	}

	public function model($model)
	{
		$this->model = $model;
	}
}
