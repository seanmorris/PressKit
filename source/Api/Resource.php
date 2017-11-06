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

	public function __construct($router, $more = [], $code = 0)
	{
		$controller = $router->routes();

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

	protected function toStructure($type)
	{
		if($this->models)
		{
			$this->body = $this->processObjects($this->models, $type);
			$this->meta['count'] = count($this->models);
		}

		if($this->model)
		{
			$this->body = $this->processObject($this->model, $type);
		}

		foreach($this->models as $object)
		{
			if(isset($object->publicId))
			{
				$objectIds[$object->publicId] = $object->publicId;
			}
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
		];
	}

	protected function processObject($object, $type = NULL, $index = 0, $parent = NULL, $property = NULL, $skipSubjects = [])
	{
		$value = NULL;

		switch(TRUE)
		{
			case $object instanceof \SeanMorris\PressKit\Model:
				$value = $object->toApi(1);
				foreach($value as $k => &$v)
				{
					if(!$object::getSubjectClass($k) || ($skipSubjects[$k] ?? FALSE))
					{
						continue;
					}

					if($vv = $object->getSubject($k))
					{
						$v = $this->processObject($vv, $type, $k, $object, $k);
					}
					else if($vv = $object->getSubjects($k))
					{
						$v = [];

						foreach($vv as $kk => $subject)
						{
							$v[] = $this->processObject($subject, $type, $kk, $object, $k);
						}
					}
				}
				break;
		}

		return $value;
	}

	protected function processObjects($objects, $type = NULL)
	{
		return array_map(
			function($o, $i) use($type)
			{
				return $this->processObject($o, $type, $i);
			}
			, $objects
			, array_keys($objects)
		);
	}

	public function toJson($type)
	{
		return json_encode($this->toStructure($type), JSON_PRETTY_PRINT);
	}

	public function toXml()
	{
		return \xmlrpc_encode($this->toStructure($type));
	}

	public function toHtml()
	{
		return $this->controller->_renderList($this->router);
	}

	public function encode($type)
	{
		if($type == 'xml')
		{
			header('Content-Type: application/xml');
			return $this->toXml($type);
		}
		else
		{
			header('Content-Type: application/json');
			return $this->toJson($type);
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
