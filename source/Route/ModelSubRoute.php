<?php
namespace SeanMorris\PressKit\Route;
class ModelSubRoute extends \SeanMorris\PressKit\Controller
{
	protected
		$formTheme = 'SeanMorris\Form\Theme\Theme'
	;
	public
		$routes = []
		, $subRoutes = []
		, $alias = [
			'index' => 'view'
		]
	;

	protected function __construct(\SeanMorris\PressKit\Controller $controller)
	{
		$this->routes = $controller->modelRoutes;
		$this->subRoutes = $controller->modelSubRoutes;
	}

	public function view($router)
	{
		$body = null;
		$this->model = $model = $this->getModel($router);
		$parentController = $router->parent()->routes();

		$params = $router->request()->params();

		if($model)
		{
			$parentController::beforeRead($model);

			$state = $model->getSubject('state');

			$session = \SeanMorris\Ids\Meta::staticSession(1);

			\SeanMorris\Ids\Log::debug('SESSION LOADED:', $session);

			if($state && isset($session['user']))
			{
				//var_dump($state, $session['user'], $state->can($session['user'], 'read'));
			}

			\SeanMorris\Ids\Log::debug('State', $state);

			$titleField = 'title';

			if(static::$titleField)
			{
				$titleField = static::$titleField;
			}
			else if($parentController::_titleField())
			{
				$titleField = $parentController::_titleField();
			}

			if($model->{$titleField})
			{
				$this->title = $model->{$titleField};
			}
			else
			{
				$this->title = 'View';
			}

			if(!$router->subRouted())
			{
				$this->context['title'] = $this->title;
			}
			
			if($theme = $this->_getTheme($router))
			{
				\SeanMorris\Ids\Log::debug(sprintf(
					'Rendering %s with theme %s under %s.'
					, get_class($model)
					, $theme
					, get_called_class()
				));

				$body .= ' ' . $theme::render($model, [
					'path'          => $router->path()->pathString()
					, 'hideTitle'   => in_array($router->routedTo(), $this->hideTitle)
					, '_controller' => $this
					, '_router'     => $router
				] + $this->context, 'single');
			}

			$parentController::afterRead($model);
		}

		if(isset($params['api']))
		{
			$resource = new \SeanMorris\PressKit\Api\Resource(
				$router
			);
			if($params['api'] == 'html')
			{
				echo $body;
			}
			else if($params['api'] == 'xml')
			{
				header('Content-Type: application/xml');
				echo $resource->toXml();
			}
			else
			{
				header('Content-Type: application/json');
				echo $resource->toJson();
			}
			/*/
			echo json_encode($model->unconsume(1));
			/*/
			die;
		}

		return $body;
	}

	public function edit($router)
	{
		$model = $this->getModel($router);
		$properties = $model::getProperties();

		$formClass = $router->parent()->routes()->_getForm('edit');

		if(!$formClass)
		{
			\SeanMorris\Ids\Log::error(sprintf(
				'Edit form not found for model %s'
				, $router->parent()->routes()->_modelClass()
			));
			return false;
		}

		$titleField = 'title';
		$this->title  = 'Edit';

		if(static::$titleField)
		{
			$titleField = static::$titleField;
		}

		if($model->{$titleField})
		{
			$this->title .= ' ' . $model->{$titleField};
		}

		if(!$router->subRouted())
		{
			$this->context['title'] = $this->title;
		}

		$form = new $formClass([
			'_router' => $router
			, '_controller' => $this
		]);


		if($params = array_replace_recursive($router->request()->post(), $router->request()->files()))
		{
			$messages = \SeanMorris\Message\MessageHandler::get();

			if($form->validate($params))
			{
				$parentController = $router->parent()->routes();

				$modelClass = $parentController->_modelClass();

				\SeanMorris\Ids\Log::debug('Model class: ', $modelClass);

				if(!$model)
				{
					$model = new $modelClass;
				}

				$skeleton = $form->getValues();

				if($parentController::beforeUpdate($model, $skeleton) === FALSE
					|| $parentController::beforeWrite($model, $skeleton) === FALSE
				){
					return FALSE;
				}

				$model->consume($skeleton);

				$messages = \SeanMorris\Message\MessageHandler::get();

				$modelSaveStatus = FALSE;

				try
				{
					if($model = $model->save())
					{
						$messages->addFlash(
							new \SeanMorris\Message\SuccessMessage('Update successful!')
						);

						$parentController::afterUpdate($model, $skeleton);
						$parentController::afterWrite($model, $skeleton);
					}
					else
					{
						$messages->addFlash(
							new \SeanMorris\Message\ErrorMessage('Unexpected error!')
						);
					}
				}
				catch(\SeanMorris\PressKit\Exception\ModelAccessException $e)
				{
					$messages->addFlash(
						new \SeanMorris\Message\ErrorMessage($e->getMessage())
					);
				}

				if(isset($params['submit']))
				{
					if($params['submit'] === 'Save & View')
					{
						throw new \SeanMorris\Ids\Http\Http303(
							$router->path()->pathString(1)
						);
					}

					if($params['submit'] === 'Save & Exit')
					{
						throw new \SeanMorris\Ids\Http\Http303(
							$router->path()->pathString(2)
						);
					}
				}

				throw new \SeanMorris\Ids\Http\Http303(
					$router->path()->pathString()
				);
			}
			else
			{
				$errors = $form->errors();
				foreach($errors as $error)
				{
					$messages->addFlash(new \SeanMorris\Message\ErrorMessage($error));
				}
			}
		}

		$formTheme = $this->formTheme;

		$skeleton = $model->unconsume(true);

		if(!$router->subRouted())
		{
			$this->context['title'] = 'Editing ' . $model->title;
		}

		$form->setValues($skeleton);
		$formVals = $form->getValues();

		$formPostVals = $form->getValues();

		$return = $form->render($formTheme);

		return $return;
	}

	public function delete($router)
	{
		$model = $this->getModel($router);
		$properties = $model::getProperties();

		$formClass = $router->parent()->routes()->_getForm('delete');

		if(!$formClass || !$model)
		{
			return false;
		}

		$form = new $formClass;

		if($params = $router->request()->params())
		{
			$messages = \SeanMorris\Message\MessageHandler::get();

			if(isset($params['delete']) && $params['delete'])
			{
				if($model->delete())
				{
					$messages->addFlash(
						new \SeanMorris\Message\SuccessMessage('Record deleted.')
					);

					throw new \SeanMorris\Ids\Http\Http303(
						$router->path()->pathString(2)
					);
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
				$messages->addFlash(
					new \SeanMorris\Message\AlertMessage('Record NOT deleted.')
				);

				throw new \SeanMorris\Ids\Http\Http303(
					$router->path()->pathString(1)
				);
			}
		}

		$formTheme = $this->formTheme;
		$formVals = $form->getValues();
		$formPostVals = $form->getValues();
		$return = $form->render($formTheme);

		$this->context['title'] = 'Delete ' . $model->title;

		return $return;
	}

	public function owners($router)
	{
		$model = $this->getModel($router);
	}

	public function api($router)
	{
		$models = $this->getModels($router);

		$models = array_map(
			function($model){
				if($model)
				{
					return $model->unconsume();
				}
			}
			, $models
		);

		$models = array_filter($models);

		array_walk_recursive($models, function(&$item, $key)
		{
			$item = utf8_encode($item);
		});

		print json_encode($models, JSON_PRETTY_PRINT, 5);
		die;
	}

	protected function getModelClass(\SeanMorris\Ids\Router $router)
	{
		return $router->parent()->routes()->_modelClass();
	}

	protected function getModel(\SeanMorris\Ids\Router $router)
	{
		return $router->parent()->routes()->_model();
	}

	protected function getModels(\SeanMorris\Ids\Router $router)
	{
		return $router->parent()->routes()->_models();
	}

	public function _access($endPoint, $router)
	{
		if(is_callable([$router->parent()->routes(), '_access']))
		{
			return $router->parent()->routes()->_access($endPoint, $router);
		}

		\SeanMorris\Ids\Log::debug(sprintf(
			'Checking for access to %s'
			, $endPoint
		));

		if(isset($this->access[$endPoint]))
		{
			$roleNeeded = $this->access[$endPoint];

			$session = \SeanMorris\Ids\Meta::staticSession();

			if(isset($session['user']))
			{
				$user = $session['user'];

				return $user->hasRole($roleNeeded);
			}

			return false;
		}		

		return false;
	}
}