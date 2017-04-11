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
		, $formTheme = null
		, $model = null
		, $models = []
		, $routes = []
		, $subRoutes = []
		, $modelRoutes = []
		, $modelSubRoutes = []
		, $stopRoutes = []
		, $listColumns = ['id', 'title']
		, $columnClasses = []
		, $hideTitle = []
		, $alias = []
	;

	protected static
		$titleField = NULL
		, $list = []
		, $pageSize = NULL
		, $pageSpread = NULL
		, $loadBy = NULL
		, $listBy = 'byNull'
		, $searchBy = 'bySearch'
		, $menusBuilt = []
		, $menus = []
		, $modelRoute = 'SeanMorris\PressKit\Route\ModelSubRoute'
		, $actions = []
		, $forms = [
			'delete' => 'SeanMorris\PressKit\Form\ModelDeleteForm'
		]
	;

	protected function foldProperty($property, $depth = -1)
	{
		$merge = function($a, $b, $depth = -1) use(&$merge)
		{
			$main = [];

			foreach($a as $key => $value)
			{
				$main[$key] = $value;
			}

			foreach($b as $key => $value)
			{
				if(isset($main[$key]))
				{
					if($depth == 0 || !is_array($value))
					{
						continue;
					}

					$main[$key] = $merge($main[$key], $value, $depth > 0 ? $depth - 1 : $depth);

					continue;
				}

				$main[$key] = $value;
			}

			$main = array_merge(
				array_flip(array_keys($b))
				, $main
			);

			return $main;
		};

		$propertyContents = [];
		$class = get_called_class();
		$classProperty = NULL;
		$parentClassProperty = NULL;

		while(true)
		{
			if(!$parentClass = get_parent_class($class))
			{
				break;
			}

			$classProperty = $class::$$property;
			$parentClassProperty = $parentClass::$$property;

			if(!is_array($classProperty)
				|| !is_array($parentClassProperty)
			){
				$propertyContents = $classProperty;
			}
			else
			{
				$propertyContents = (
					$propertyContents
					+ $merge($classProperty, $parentClassProperty, $depth)
				);
			}

			$class = $parentClass;
		}

		return $propertyContents;
	}

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
			if(isset($controller->theme))
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

				if($user->hasRole($roleNeeded))
				{
					\SeanMorris\Ids\Log::debug('Access granted.');

					return TRUE;
				}
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

			$modelRoute = static::$modelRoute;
			$modelSubRoutes = $modelRoute
				? (new $modelRoute($this))->subRoutes
				: [];

			foreach($this->subRoutes as $node => $routes)
			{
				$routes = (array)$routes;
				foreach($routes as $route)
				{
					$allSubroutes[] = $route;
				}
			}

			foreach($modelSubRoutes as $node => $routes)
			{
				foreach($routes as $route)
				{
					$allSubroutes[] = $route;
				}
			}

			$allSubroutes = array_flip($allSubroutes);

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

					if(!class_exists($routeClass))
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

			$theme = $this->_getTheme($router);

			$menuViewClass = '\SeanMorris\PressKit\View\Menu';

			if($theme)
			{
				$themeMenuViewClass = $theme::resolveFirst('menu', NULL, 'list');

				$menuViewClass = $themeMenuViewClass
					? $themeMenuViewClass
					: $menuViewClass;
			}

			$menuViews[] = new $menuViewClass([
				'menu'      => $m
				, '__debug' => \SeanMorris\Ids\Settings::read('devmode')
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

		if(!$router->parent())
		{
			$menu = $this->_menu($router, $preroutePath);
		}

		if(is_object($body))
		{
			$theme = $this->_getTheme($router);
			$viewClass = $theme::resolveFirst(get_class($body));

			if($viewClass)
			{
				$body = new $viewClass([
					'object'    => $body
					, '__debug' => \SeanMorris\Ids\Settings::read('devmode')
				]);
			}
		}

		if($this->model && count($this->models) === 1)
		{
			$modelPath = $this->model->publicId;

			$contextMenu = [ '✯' => [
					'_weight' => -100
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
			]];

			$user = \SeanMorris\Access\Route\AccessRoute::_currentUser();

			if($user && $user->id)
			{
				$menuPath = $preroutePath->getSpentPath();

				$menuPathString = $menuPath->pathString();

				$m = \SeanMorris\PressKit\Menu::get('main');
				$m->add($contextMenu, $menuPathString, $user);
			}
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

		if(!$router->subRouted()
			&& !$router->aliased()
			&& !isset($this->context['breadcrumbs'])
		){
			$parentRouter = $router;
			$parentRouters = [];

			while($parentRouter)
			{
				$match = $parentRouter->match();

				if($parentRouter->aliased())
				{
					if(!$parentRouter = $parentRouter->parent())
					{
						break;
					}
				}

				$parentRoute = $parentRouter->routes();
				$parentRouters[] = $parentRouter;
				$parentRouter = $parentRouter->parent();
			}

			$parentRouters = array_reverse($parentRouters);
			$crumbUrl = '';

			foreach($parentRouters as $parentRouter)
			{
				if(isset($parentRoutes)
					&& isset($parentRoutes->alias)
					&& (array_search($match, $parentRoutes->alias) !== FALSE)
					&& isset($this->context['breadcrumbs'])
					&& $this->context['breadcrumbs']
				){
					$crumbUrl .= '/' . $parentRouter->match();
					$parentRoutes = $parentRouter->routes();
					continue;
				}

				$parentRoutes = $parentRouter->routes();

				$title = $parentRoutes->title
					? $parentRoutes->title
					: get_class($parentRoutes)
				;

				$this->context['breadcrumbs'][] = [
					'text' => $title
					, 'url' => $crumbUrl ? $crumbUrl : '/'
				];

				$crumbUrl .= '/' . $parentRouter->match();
			}
		}

		if(isset($this->context['breadcrumbsSuffix']))
		{
			foreach($this->context['breadcrumbsSuffix'] as $title => $url)
			{
				$this->context['breadcrumbs'][] = [
					'text' => $title
					, 'url' => $url
				];

				unset($this->context['breadcrumbsSuffix'][$title]);
			}
		}

		if($theme = $this->_getTheme($router))
		{
			foreach(['css','js'] as $contextElement)
			{
				if(!isset($this->context[$contextElement]))
				{
					$this->context[$contextElement] = [];
				}

				$context =& $router->getContext();

				$ctxEle = $theme::resolveList($contextElement, [], false);

				if(!$context[$contextElement])
				{
					$context[$contextElement] = [];
				}

				$context[$contextElement] = array_merge(
					$ctxEle,
					$context[$contextElement]
				);
			}

			if(isset($context['js']) && $context['js'] && !$router->parent())
			{
				$context['js'] = [\SeanMorris\Ids\AssetManager::buildAssets2($context['js'])];

				// \SeanMorris\Ids\Log::debug('Assets built:', $context['js']);
			}

			if(isset($context['css']) && $context['css'] && !$router->parent())
			{
				$context['css'] = [\SeanMorris\Ids\AssetManager::buildAssets2($context['css'])];

				// \SeanMorris\Ids\Log::debug('Assets built:', $context['css']);
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
						, '__debug' => \SeanMorris\Ids\Settings::read('devmode')
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
			$routes = $router->routes();

			if(!is_a($routes, get_class()))
			{
				continue;
			}

			if($parentModels = $routes->_models())
			{
				return $parentModels;
			}
		}
	}

	public function index($router)
	{
		if(!$this->modelClass)
		{
			return FALSE;
		}

		$modelClass = $this->modelClass;

		$params = $router->request()->params();
		$postParams = $router->request()->post();

		if(isset($postParams['action'], static::$actions[$postParams['action']])
			&& $this->_access(static::$actions[$postParams['action']], $router)
		){
			$action = static::$actions[$postParams['action']];
			$modelsProcessed = 0;
			$messages = \SeanMorris\Message\MessageHandler::get();

			if(isset($postParams['models'])
				&& is_array($postParams['models'])
				&& is_callable([get_called_class(), $action])
			){
				foreach ($postParams['models'] as $modelId)
				{
					if(static::$loadBy)
					{
						$loadBy = ucwords(static::$loadBy);
					}
					else
					{
						$loadBy = 'ByPublicId';
					}

					$loadBy = 'loadOne' . $loadBy;

					$model = $modelClass::$loadBy($modelId);
					static::$action($model);
					$modelsProcessed++;
				}
			}

			$messages->addFlash(
				new \SeanMorris\Message\SuccessMessage(sprintf(
					'%s %d records.'
					, $postParams['action']
					, $modelsProcessed
				))
			);

			$queryString = http_build_query($_GET);
			$pop = 0;

			if($this->getParentModels($router))
			{
				$pop = 1;
			}

			throw new \SeanMorris\Ids\Http\Http303(
				$router->path()->pathString($pop) . ($queryString
					? '?' . $queryString
					: NULL
				)
			);
		}

		$formClass = $router->routes()->_getForm('search');

		$formRendered = NULL;

		$formValues = [];

		if(!$modelClass)
		{
			return false;
		}

		$objects = [];

		$path = $router->path();

		$objectClass = $this->modelClass;

		$pageNumber = 0;

		$pagerLinks = [];

		if($parentModels = $this->getParentModels($router))
		{
			if(count($parentModels) == 1)
			{
				$parentModel = current($parentModels);

				$node = $router->path()->getNode(-1);

				$objectClass = $parentModel::getSubjectClass($node);

				if($node && $objectClass)
				{
					$gen = \SeanMorris\Ids\Relationship::generateByOwner(
						$parentModel, $node
					);

					foreach($gen() as $object)
					{
						\SeanMorris\Ids\Log::debug($object);

						if($subject = $object->subject())
						{
							$this->models[] = $objects[] = $subject;
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
				$form = new $formClass([
					'_router'		=> $router
					, '_controller'	=> $this
				]);

				$formTheme = $this->formTheme;

				if($params)
				{
					$form->setValues($params);
					$formValues = $form->getValues();
				}

				$formRendered = $form->render($formTheme);
			}

			unset($formValues['page']);

			if(array_filter($formValues))
			{
				$listBy = 'BySearch';
				
				if(static::$pageSize)
				{
					$listBy = 'Page' . $listBy;
				}

				$listBy = 'generate' . $listBy;

				if(isset($params['page']))
				{
					$pageNumber = (int)( ($params['page'] > 0) ? $params['page'] : 0 );
				}

				$gen = $modelClass::$listBy(array_filter(
					$formValues
					, function ($val)
					{
						return $val !== '';
					}
				), $pageNumber, static::$pageSize);
			}
			else
			{
				$listBy = 'ByModerated';

				if(static::$listBy)
				{
					$listBy = ucwords(static::$listBy);
					$countBy = 'count' . $listBy;
				}

				$listParams = [];
				$listType = $path->getNode();

				if($listType && isset(static::$list[$listType]))
				{
					if(is_array(static::$list[$listType]))
					{
						if(isset(static::$list[$listType]['function']))
						{
							$listBy = ucfirst(static::$list[$listType]['function']);
						}

						if(isset(static::$list[$listType]['params']))
						{
							$listParamFunction = static::$list[$listType]['params'];
							$listParams = static::$listParamFunction();
						}
					}
					else
					{
						$listBy = static::$list[$listType];
					}
				}
				else
				{
					// $path->unconsumeNode();
				}

				if(static::$pageSize)
				{
					$listBy = 'Page' . $listBy;
					$pageNumber = 0;

					if(isset($params['page']))
					{
						$pageNumber = (int)( ($params['page'] > 0) ? $params['page'] : 0 );
					}

					$unpagedlistParams = $listParams;

					$listParams[] = $pageNumber;
					$listParams[] = static::$pageSize;

					$count = $modelClass::$countBy(...$unpagedlistParams);

					$lastPageSpread = $pageNumber + static::$pageSpread;
					$lastPage = (int) ceil($count / static::$pageSize);

					if($lastPage > 0 && $pageNumber > $lastPage)
					{
						$pageNumber = $lastPage;

						$listParams = $unpagedlistParams;
						$listParams[] = $pageNumber;
						$listParams[] = static::$pageSize;
					}

					if($pageNumber)
					{
						$pagerLinks['<<'] = 0;
						$pagerLinks['<'] = $pageNumber - 1;
					}

					$firstPage = $pageNumber - static::$pageSpread;
					$firstPage = $firstPage >= 0 ? $firstPage : 0;

					if($lastPage < $lastPageSpread)
					{
						$lastPageSpread = $lastPage;
					}

					if($pageNumber <= $lastPageSpread)
					{
						$pager = $firstPage;

						while($pager <= $lastPageSpread)
						{
							if($pager >= $lastPage)
							{
								break;
							}

							$pagerLinks[$pager] = $pager;
							$pager++;
						}

						if($pageNumber < $lastPageSpread-1)
						{
							$pagerLinks['>'] = $pageNumber+1;
							$pagerLinks['>>'] = $lastPage -1;
						}
					}
				}

				$listBy = 'generate' . $listBy;

				$gen = $modelClass::$listBy(...$listParams);
			}

			foreach($gen() as $object)
			{
				$this->models[] = $objects[] = $object;
			}
		}

		if(isset($params['api']))
		{
			$pagerLinksKeys = array_map(
				function($page) use($path)
				{
					return sprintf('Page %d', $page +1);
				}
				, $pagerLinks
			);

			$pagerLinks = array_map(
				function($page) use($path)
				{
					return sprintf('%s?page=%s', $path->pathString(), $page);
				}
				, $pagerLinks
			);

			$pagerLinks = array_combine($pagerLinksKeys, $pagerLinks);
			//*/
			$resource = new \SeanMorris\PressKit\Api\Resource(
				$router
				, ['navigation' => $pagerLinks]
			);
			header('Content-Type: application/json');
			echo $resource->toJson();
			/*/
			echo json_encode(array_map(
				function($o)
				{
					return $o->unconsume();
				},
				$objects
			));
			//*/
			die;
		}

		if(!$objects)
		{
			$objects = [];
		}

		if(!$router->subRouted() && !in_array($router->routedTo(), $this->hideTitle))
		{
			$this->context['title'] = $this->title;
		}

		$list = NULL;

		if($theme = $this->_getTheme($router))
		{
			$objectClass = $objects ? get_class(current($objects)) : $objectClass;

			\SeanMorris\Ids\Log::debug(sprintf(
				'Rendering list of %s with theme %s.'
				, $objectClass
				, $theme
			));

			if($listViewClass = $theme::resolveFirst($objectClass, NULL, 'list'))
			{
				$list = new $listViewClass(
					[
						'columns'         => $this->listColumns
						, 'content'       => $objects
						, 'path'          => $path->getAliasedPath()->pathString()
						, 'currentPath'   => $path->pathString()
						, 'columnClasses' => $this->columnClasses
						, 'subRouted'     => $router->subRouted()
						, '_controller'   => $this
						, '_router'       => $router
						, 'hideTitle'     => in_array($router->routedTo(), $this->hideTitle)
						, 'page'          => $pageNumber
						, 'pager'         => $pagerLinks
						, 'query'         => $_GET
						, '__debug'       => TRUE
					] + $this->context
				);
			}
		}
		else
		{
			$list = new \SeanMorris\PressKit\Theme\Austere\Grid([
				'columns' => ['id', 'title', 'view']
				, 'columnClasses' => $this->columnClasses
				, 'objects'       => $objects
				, '_controller'   => $this
				, '_router'       => $router
				, 'subRouted'     => $router->subRouted()
				, 'hideTitle'     => in_array($router->routedTo(), $this->hideTitle)
				, 'currentPath'   => $path->pathString()
				, 'path'          => $path->getAliasedPath()->pathString()
				, 'page'          => $pageNumber
				, 'pager'         => $pagerLinks
				, 'query'         => $_GET
				, '__debug'       => TRUE
			] + $this->context);
		}

		return $formRendered . $list;
	}

	public function create($router)
	{
		$session = \SeanMorris\Ids\Meta::staticSession();

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
			, '_router'		=> $router
			, '_controller'	=> $this
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

				if($stateClass = $model->canHaveOne('state'))
				{
					if(is_a('SeanMorris\PressKit\State', $stateClass, true))
					{
						\SeanMorris\Ids\Log::debug(sprintf(
							'Assigning state to Model %s (%s)'
							, $modelClass
							, $stateClass
						));

						$owner = 0;

						if($owner = \SeanMorris\Access\Route\AccessRoute::_currentUser())
						{
							$skeleton['state'] = [
								'class' => $stateClass
								, 'owner' => $owner
								, 'state' => 0
							];
						}
					}
				}

				if(static::beforeCreate($model, $skeleton) === FALSE
					|| static::beforeWrite($model, $skeleton) === FALSE
				){
					return FALSE;
				}

				$model->consume($skeleton);

				$modelSaveStatus = FALSE;

				try
				{
					if($newModel = $model->create())
					{
						$this->model = $model = $newModel;

						$parents = $this->getParentModels($router);

						if($parents)
						{
							$parent = array_shift($parents);
							$property = $router->path()->getNode(-1);

							// @TODO: Add case for singular children
							if(get_class($model) == $parent->canHaveMany($property))
							{
								\SeanMorris\Ids\Log::debug($parent);
								$parent->addSubject($property, $model);
								$parent->storeRelationships($property, $parent->{$property});
							}

							static::afterCreate($model, $skeleton);
							static::afterWrite($model, $skeleton);

							throw new \SeanMorris\Ids\Http\Http303(
								$router->path()->pathString(2)
							);
						}

						static::afterCreate($model, $skeleton);
						static::afterWrite($model, $skeleton);

						$messages->addFlash(
							new \SeanMorris\Message\SuccessMessage('Update successful!')
						);

						$getParams = $router->request()->get();

						$suffix = NULL;

						if(isset($getParams['api']))
						{
							$suffix = '?api';
						}

						$redirect = new \SeanMorris\Ids\Http\Http303(
							$router->path()->pathString(1)
								. '/'
								. $model->publicId
								. $suffix
						);
					}
					else
					{
						$messages->addFlash(
							new \SeanMorris\Message\ErrorMessage('Unexpected error.')
						);

						\SeanMorris\Ids\Log::debug('Unexpected error.', $model);
					}
				}
				catch(\SeanMorris\PressKit\Exception\ModelAccessException $e)
				{
					$messages->addFlash(
						new \SeanMorris\Message\ErrorMessage($e->getMessage())
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

		if($id && isset(static::$list[$id]))
		{
			if(!$router->path()->remaining())
			{
				return $this->index($router);
			}

			$router->path()->consumeNode();
		}

		$modelClass = $this->modelClass;

		if(!class_exists($modelClass))
		{
			return false;
		}

		if(static::$loadBy)
		{
			$loadBy = ucwords(static::$loadBy);
		}
		else
		{
			$loadBy = 'ByPublicId';
		}

		\SeanMorris\Ids\Log::debug(
			'Loading model by ' . $loadBy
		);

		$loadBy ='generate' . $loadBy;

		$gen = $modelClass::$loadBy($id);

		foreach($gen() as $model)
		{
			if(!$this->model)
			{
				$this->model = $model;
			}

			$this->models[] = $model;
		}

		if(!$router->path()->done() && $this->models && static::$modelRoute)
		{
			$model = current($this->models);
			$modelRoute = new static::$modelRoute($this);

			$titleField = 'title';

			if(static::$titleField)
			{
				$titleField = static::$titleField;
			}

			if($model && $model->{$titleField})
			{
				$modelRoute->title = $model->{$titleField};
			}


			return $router->resumeRouting($modelRoute);
		}

		if(!$this->models)
		{
			if($router->parent() && $router->parent()->routes() === $this)
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
		if($router->subRouted())
		{
			return;
		}
		\SeanMorris\Ids\Log::trace();
		return new \SeanMorris\Ids\Http\Http404('Not Found: '. $router->path()->pathString());
		return FALSE;
		//return 404;
	}

	public function _publishModels($model)
	{
		$state = $model->getSubject('state');
		$state->change(1);
		$state->save();
	}

	public function _unpublishModels($model)
	{
		if($state = $model->getSubject('state'))
		{
			$state->consume(['state' => 0]);
			$state->save();
		}
	}

	public function _deleteModels($model)
	{
		$model->delete();
	}

	protected static function beforeCreate($instance, &$skeleton)
	{
		// @TODO: Assign state?
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

	public function _pathTo($class, $subclassAllowed = FALSE, $prefix = NULL)
	{
		$route = $this->_locateRoute($class, $subclassAllowed = FALSE, $prefix = NULL);

		return key($route);

		return;


		foreach($this->routes as $node => $route)
		{
			if(!$subclassAllowed && $class === $route)
			{
				return $prefix . '/' . $node;
			}
			else if(!$subclassAllowed && is_subclass_of($class, $route))
			{
				return $prefix . '/' . $node;
			}

			$route = new $route;
			$path = $route->_pathTo($class, $subclassAllowed, $prefix . '/' . $node);

			if($path !== FALSE)
			{
				return $path;
			}
		}

		return FALSE;
	}

	public function _locateRoute($class, $subclassAllowed = FALSE, $prefix = NULL)
	{
		foreach($this->routes as $node => $route)
		{
			if(is_subclass_of($class, 'SeanMorris\PressKit\Controller'))
			{
				if(!$subclassAllowed && $class === $route)
				{
					return [$prefix . '/' . $node => $route];
				}
				else if(!$subclassAllowed && is_subclass_of($class, $route))
				{
					return [$prefix . '/' . $node => $route];
				}
			}
			else if(is_subclass_of($class, 'SeanMorris\PressKit\Model'))
			{
				$route = new $route;

				if($model === $route->modelClass || is_subclass_of($model, $route->modelClass))
				{
					return [$prefix . '/' . $node => $route];
				}
			}

			$route = new $route;

			if(!$route instanceof Controller)
			{
				continue;
			}

			$path = $route->_locateRoute($class, $subclassAllowed, $prefix . '/' . $node);

			if($path !== FALSE)
			{
				return $path;
			}
		}

		return FALSE;
	}

	public function _dynamicId($model)
	{
		if(is_object($model)
			&& isset($model->id)
			&& (
				$model === $this->modelClass
				|| is_subclass_of($model, $this->modelClass)
			)
		){
			return $model->id;
		}

		return false;
	}

	public function _actions($router)
	{
		$actions = [];

		foreach(static::$actions as $action => $function)
		{
			if(!$this->_access($function, $router))
			{
				continue;
			}

			$actions[$action] = $function;
		}

		return $actions;
	}

	public function __get($name)
	{
		return $this->$name;
	}
}
