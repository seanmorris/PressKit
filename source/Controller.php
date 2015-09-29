<?php
namespace SeanMorris\PressKit;
class Controller implements \SeanMorris\Ids\Routable
{
	protected
		$modelClass
		, $theme
		, $title
		, $access = []
		, $context = []
		, $model = null
		, $models = []
		, $subRoutes = []
		, $stopRoutes = []
		, $modelRoute
		, $listColumns = ['id', 'title']
		, $columnClasses = []
		, $hideTitle = []
	;

	protected static
		$menusBuilt = []
		, $menus = []
		, $forms = [
			'delete' => 'SeanMorris\PressKit\Form\ModelDeleteForm'
		]
	;

	protected function _getForm($name)
	{
		$class = get_called_class();

		while($class)
		{
			if(isset($class::$forms[$name]))
			{
				return $class::$forms[$name];
			}

			$class = get_parent_class($class);
		}
	}

	protected function _setTheme(\SeanMorris\Theme\Theme $theme)
	{
		$this->theme = $theme;
	}

	protected function _getTheme($router)
	{
		$controller = $this;

		while($router)
		{
			if($controller->theme)
			{
				return $controller->theme;
			}

			$router = $router->parent();

			if(!$router)
			{
				break;
			}

			$controller = $router->routes();
		}
	}

	public function _init($router)
	{
		$this->context =& $router->getContext();
	}

	public function _access($endPoint, $router)
	{
		\SeanMorris\Ids\Log::debug(sprintf(
			'Checking for access to %s::%s'
			, get_called_class()
			, $endPoint
		));

		if(!$this->access)
		{
			\SeanMorris\Ids\Log::debug('Access always granted.');
			return true;
		}

		if(isset($this->access[$endPoint]))
		{
			$roleNeeded = $this->access[$endPoint];

			if($roleNeeded === TRUE)
			{
				\SeanMorris\Ids\Log::debug('Access always granted.');
				return TRUE;
			}

			$session = \SeanMorris\Ids\Meta::staticSession();

			if(isset($session['user']))
			{
				\SeanMorris\Ids\Log::debug(sprintf(
					'Access requires %s.'
					, $roleNeeded
				));

				$user = $session['user'];

				return $user->hasRole($roleNeeded);
			}

			\SeanMorris\Ids\Log::debug('Access Denied.');

			return false;
		}		

		return false;
	}

	public function _preRoute($router)
	{
		$endPoint = $router->routedTo();

		if($endPoint !== '_dynamic')
		{
			return $this->_access($endPoint, $router);
		}

		return true;
	}

	public function _menu(\SeanMorris\Ids\Router $router, $path, \SeanMorris\Ids\Routable $routable = NULL)
	{
		$session = \SeanMorris\Ids\Meta::staticSession();
		
		$user = null;

		if(isset($session['user']))
		{
			$user = $session['user'];
		}

		if(!$routable && isset(static::$menusBuilt[get_called_class()]))
		{
			return;
		}
		else if($routable && isset(static::$menusBuilt[get_class($routable)]))
		{
			return;
		}

		$menuPath = $path->getSpentPath();
		$menuPathString = $menuPath->pathString();

		if(($this->model && (!$router->child() || !$router->child()->child())) && !$routable)
		{
			$modelPath = $this->model->publicId;

			$contextMenu = [ 'Content' => [
				'Context' => [
					'_weight' => 100
					, '_access'	=> isset($this->access['_contextMenu'])
						? $this->access['_contextMenu']
						: false
					, 'View'=> [
						'_link'		=> $modelPath . '/view'
						, '_access'	=> isset($this->access['view'])
							? $this->access['view']
							: true
					]
					, 'Edit'=> [
						'_link'		=> $modelPath . '/edit'
						, '_access'	=> isset($this->access['edit'])
							? $this->access['edit']
							: false
					]
					, 'Delete'=> [
						'_link'		=> $modelPath . '/delete'
						, '_access'	=> isset($this->access['delete'])
							? $this->access['delete']
							: false
					]
				]
			]];

			$user = NULL;

			if(isset($session['user']))
			{
				$user = $session['user'];
			}
			
			$m = \SeanMorris\PressKit\Menu::get('main');
			$m->add($contextMenu, $menuPathString, $user);

			// return;
		}

		$menuViews = [];

		$menus = static::$menus;

		if($routable && isset($routable::$menus))
		{
			$menus = $routable::$menus;		
		}

		foreach($menus as $menuName => $menu)
		{
			$m = \SeanMorris\PressKit\Menu::get($menuName);
			$m->add($menu, $menuPathString, $user);

			$menuViews = [];
			$allSubroutes = [];

			if(!$routable && $this->subRoutes)
			{
				$allSubroutes = array_flip(
					array_merge(...array_values($this->subRoutes))
				);
			}

			$nextRoutes = NULL;

			if(isset($this->routes))
			{
				$nextRoutes = $this->routes;
			}

			if($routable && isset($routable->routes))
			{
				$nextRoutes = $routable->routes;
			}

			if($nextRoutes && is_array($nextRoutes))
			{
				foreach($nextRoutes as $routePath => $routeClass)
				{
					if(isset($allSubroutes[$routePath]))
					{
						continue;
					}

					$route = new $routeClass();

					if(is_callable([$route, '_menu']) || ($route && isset($route::$menus)))
					{
						$subRouteable = null;
						$submenuPath = $menuPath->append($routePath);
						$submenuPath->consumeNode();
						$submenuPath->consumeNode();

						if(!is_callable([$route, '_menu']) && $route && $route::$menus && !$routable)
						{
							$subMenu = $this->_menu($router, $submenuPath, $route);
						}
						else if(is_callable([$route, '_menu']))
						{
							$subMenu = $route->_menu($router, $submenuPath);
						}
					}
				}
			}

			$menuViews[] = new \SeanMorris\PressKit\View\Menu([
				'menu' => $m
			]);
		}

		if($routable)
		{
			static::$menusBuilt[get_class($routable)] = true;
		}
		else
		{
			static::$menusBuilt[get_called_class()] = true;
		}

		if($menuViews)
		{
			$menuView = implode(PHP_EOL, $menuViews);
			return $menuView;
		}
	}

	public function _postRoute($router, $body, $preroutePath)
	{
		$menu = null;
		
		if(!$router->parent() || $this->models)
		{
			$menu = $this->_menu($router, $preroutePath);
		}

		$routedTo = $router->routedTo();
		$panels = [$body];

		foreach($this->subRoutes as $triggerPath => $subRoutePaths)
		{
			if($router->child())
			{
				break;
			}

			if($triggerPath !== NULL && $triggerPath !== $routedTo)
			{
				continue;
			}

			foreach($subRoutePaths as $subRouteNode)
			{
				$subRouteSubNodes = $subRouteNode;
				$subRouteNodes = explode('/', $subRouteNode);

				if(count($subRouteNodes))
				{
					$subRouteNode = array_shift($subRouteNodes);
				}

				if(isset($this->routes[$subRouteNode]))
				{
					$subRouteClassName = $this->routes[$subRouteNode];

					$subPath = $router->path()->getSpentPath();

					$subPath = $subPath->append($subRouteNode, ...$subRouteNodes);
					$subPath->consumeNode();

					$subRequest = $router->request()->copy([
						'path' => $subPath
					]);

					try
					{
						$panel = $router->subRoute(
							$subRequest
							, new $subRouteClassName
						);

						$panels[$subRouteSubNodes] = $panel;

					}
					catch(\SeanMorris\Ids\Http\HttpException $e)
					{
						\SeanMorris\Ids\Log::logException($e);
						continue;
					}
				}
			}
		}

		$trail = [];

		if(!$router->subRouted() && !isset($this->context['breadcrumbs']))
		{
			$parentRouter = $router;
			$parentRouters = [];

			while($parentRouter)
			{
				$parentRouters[] = $parentRouter;
				$parentRouter = $parentRouter->parent();
			}

			$parentRouters = array_reverse($parentRouters);
			$crumbUrl = '';

			foreach($parentRouters as $parentRouter)
			{
				$parentRoutes = $parentRouter->routes();

				$title = $parentRoutes->title
					? $parentRoutes->title
					: get_class($parentRoutes)
				;

				$trail[] = [
					'text' => $title
					, 'url' => $crumbUrl ? $crumbUrl : '/' 
				];

				$crumbUrl .= '/' . $parentRouter->match();
			}

			$this->context['breadcrumbs'] = $trail;
		}

		if($theme = $this->_getTheme($router))
		{
			$this->context['css'] = $this->context['js'] = [];
			
			foreach(['css','js'] as $contextElement)
			{
				$ctxEle = $theme::resolveList($contextElement, [], false);

				if(!$this->context[$contextElement])
				{
					$this->context[$contextElement] = [];
				}

				$this->context[$contextElement] = array_merge($this->context[$contextElement], $ctxEle);

				$this->context[$contextElement] = array_unique($this->context[$contextElement]);
			}

			$stack = $theme::resolveFirst('stack');

			if(!isset($this->context['messages']))
			{
				$this->context['messages'] = \SeanMorris\Message\MessageHandler::get()->render();
			}

			if($stack)
			{
				$stack = new $stack(
					[
						'menu' => $menu
						//, 'messages' => \SeanMorris\Message\MessageHandler::get()->render()
						, 'body' => $panels
					] + $this->context
					, get_class()
				);

				return $stack;
			}
		}
		else
		{
			$body = implode(PHP_EOL . PHP_EOL, $panels);
		}

		return $body;
	}

	protected function getParentModels($router)
	{
		while($router = $router->parent())
		{
			if($parentModels = $router->routes()->_models())
			{
				return $parentModels;
			}
		}
	}

	public function index($router)
	{
		if(!$this->modelClass)
		{
			return;
		}

		$modelClass = $this->modelClass;

		$formClass = $router->routes()->_getForm('search');

		$formRendered = NULL;

		$formValues = [];

		if(!$modelClass)
		{
			return false;
		}

		$objects = [];

		$path = $router->path();

		if($parentModels = $this->getParentModels($router))
		{
			if(count($parentModels) == 1)
			{
				$parentModel = current($parentModels);
				$node = $router->path()->getNode(-1);

				if($node && $parentModel::getSubjectClass($node))
				{
					$gen = \SeanMorris\Ids\Relationship::generateByOwner(
						$parentModel, $node
					);

					foreach($gen() as $object)
					{
						\SeanMorris\Ids\Log::debug($object);

						if($subject = $object->subject())
						{
							$objects[] = $subject;
						}
					}
				}
			}
			else
			{
				// @todo: figure out what kind of list to load in
				// the event of multiple parentmodels
			}
		}
		else
		{
			$params = $router->request()->params();
			
			if($formClass)
			{
				$form = new $formClass;

				$formTheme = $this->formTheme;

				if($params)
				{
					$form->setValues($params);
					$formValues = $form->getValues();
				}
				
				$formRendered = $form->render($formTheme);
			}

			if($formValues)
			{
				$gen = $modelClass::generateBySearch(array_filter($formValues));
			}
			else
			{
				$gen = $modelClass::generateByModerated();
			}

			foreach($gen() as $object)
			{
				$objects[] = $object;
			}

			if(isset($params['api']))
			{
				echo json_encode(array_map(
					function($o)
					{
						return $o->unconsume(2);
					},
					$objects
				));
				die;
			}

		}

		if(!$objects)
		{
			return;
		}

		if(!$router->subRouted() && !in_array($router->routedTo(), $this->hideTitle))
		{
			$this->context['title'] = $this->title;
		}

		if($theme = $this->_getTheme($router))
		{
			\SeanMorris\Ids\Log::debug(sprintf(
				'Rendering list of %s with theme %s.'
				, get_class(current($objects))
				, $theme
			));

			$list = $theme::render(
				current($objects)
				, [
					'columns' => $this->listColumns
					, 'content' => $objects
					, 'path' => $path->getAliasedPath()->pathString()
					, 'columnClasses' => $this->columnClasses
					, 'subRouted' => $router->subRouted()
					, 'hideTitle' => in_array($router->routedTo(), $this->hideTitle)
				] + $this->context
				, 'list'
			);
		}
		else
		{
			$list = new \SeanMorris\PressKit\Theme\Austere\Grid([
				'columns' => ['id', 'title', 'view']
				, 'columnClasses' => $this->columnClasses
				, 'objects' => $objects
				, 'subRouted' => $router->subRouted()
				, 'hideTitle' => in_array($router->routedTo(), $this->hideTitle)
			] + $this->context);
		}		

		return $formRendered . $list;
	}

	public function create($router)
	{
		$formClass = $this->_getForm('edit');
		
		if(!$formClass)
		{
			\SeanMorris\Ids\Log::error(sprintf(
				'Edit form not found for model %s'
				, $this->modelClass
			));
			return false;
		}

		$form = new $formClass([
			'_action' => '/' .  $router->request()->uri()
		]);

		if($params = $router->request()->post())
		{
			$messages = \SeanMorris\Message\MessageHandler::get();
			$redirect = FALSE;

			if($form->validate($params))
			{
				$modelClass = $this->modelClass;
				$model = new $modelClass;
				$skeleton = $form->getValues();

				if(static::beforeCreate($model, $skeleton) === FALSE
					|| static::beforeWrite($model, $skeleton) === FALSE
				){
					return FALSE;
				}

				$model->consume($skeleton);

				if($model = $model->save())
				{
					$parents = $this->getParentModels($router);

					if($parents)
					{
						$parent = array_shift($parents);
						$property = $router->path()->getNode(-1);
						
						if(get_class($model) == $parent->canHaveMany($property))
						{
							\SeanMorris\Ids\Log::debug($parent);
							$parent->addSubject($property, $model);
							$parent->save();
						}

						throw new \SeanMorris\Ids\Http\Http303(
							$router->path()->pathString(2)
						);
					}

					$messages->addFlash(
						new \SeanMorris\Message\SuccessMessage('Update successful!')
					);

					$redirect = new \SeanMorris\Ids\Http\Http303(
						$router->path()->pathString(1) . '/' . $model->publicId
					);

					static::afterCreate($model, $skeleton);
					static::afterWrite($model, $skeleton);
				}
				else
				{
					$messages->addFlash(
						new \SeanMorris\Message\ErrorMessage('Unexpected error.')
					);
				}
			}
			else
			{
				$errors = $form->errors();

				foreach($errors as $error)
				{
					$messages->addFlash(new \SeanMorris\Message\ErrorMessage($error));
				}

				if(!$router->subRouted())
				{
				}
			}

			if($redirect)
			{
				throw $redirect;
			}
		}

		$classParts = explode('\\', $this->modelClass);

		if(!$router->subRouted())
		{
			$this->context['title'] = 'Creating ' . array_pop($classParts);
		}		
		
		$formTheme = $this->formTheme;

		return $form->render($formTheme);
	}

	public function _dynamic($router)
	{
		$id = $router->path()->getNode();

		$modelClass = $this->modelClass;

		if(!class_exists($modelClass))
		{
			return false;
		}

		$gen = $modelClass::generateByPublicId($id);

		foreach($gen() as $model)
		{
			if(!$this->model)
			{
				$this->model = $model;
			}

			$this->models[] = $model;
		}

		if(!$router->path()->done() && $this->models && $this->modelRoute)
		{
			$model = current($this->models);
			$modelRoute = new $this->modelRoute;

			if($model->title)
			{
				$modelRoute->title = $model->title;
			}
			

			return $router->resumeRouting($modelRoute);
		}	

		if(!$this->models)
		{
			if($router->parent()->routes() === $this)
			{
				return false;
			}

			$router->path()->unconsumeNode();
			return $router->resumeRouting($this);
		}
	}

	public function _modelClass()
	{
		return $this->modelClass;
	}

	public function _model()
	{
		return $this->model;
	}

	public function _models()
	{
		return $this->models;
	}

	public function _notFound($router)
	{
		\SeanMorris\Ids\Log::trace();
		throw new \SeanMorris\Ids\Http\Http404('Not Found: '. $router->path()->pathString());
		return FALSE;
		//return 404;
	}

	protected static function beforeCreate($instance, &$skeleton)
	{

	}

	protected static function afterCreate($instance, &$skeleton)
	{

	}

	protected static function beforeWrite($instance, &$skeleton)
	{

	}

	protected static function afterWrite($instance, &$skeleton)
	{

	}

	protected static function beforeRead($instance)
	{
		
	}

	protected static function afterRead($instance)
	{

	}

	protected static function beforeUpdate($instance, &$skeleton)
	{
		\SeanMorris\Ids\Log::debug('BEFORE UPDATE ON ', get_called_class());
	}

	protected static function afterUpdate($instance, &$skeleton)
	{

	}

	protected static function beforeDelete($instance)
	{

	}

	protected static function afterDelete($instance)
	{

	}
}