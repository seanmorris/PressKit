<?php
namespace SeanMorris\PressKit\Route;
class ImageRoute extends \SeanMorris\PressKit\Controller
{
	protected
		$title = 'Images'
		, $modelClass = 'SeanMorris\PressKit\Image'
		, $formTheme = 'SeanMorris\Form\Theme\Theme'
		, $access = [
			'create' => 'SeanMorris\Access\Role\Administrator'
			, 'edit' => 'SeanMorris\Access\Role\Administrator'
			, 'delete' => 'SeanMorris\Access\Role\Administrator'
			, 'view' => TRUE
			, 'index' => TRUE
			//, 'index' => 'SeanMorris\Access\Role\Administrator'
			, '_contextMenu' => 'SeanMorris\Access\Role\Administrator'
		]
	;

	protected static
		$forms = [
			'edit' => 'SeanMorris\PressKit\Form\ImageForm',
			'search' => 'SeanMorris\PressKit\Form\ImageSearchForm',
		]
		// , $resourceClass = '\SeanMorris\TheWhtRbt\Resource'
		, $pageSize   = 16
		, $maxUploads = 4
		, $menus = [
			'main' => [
				'Content' => [
					'_access' => 'SeanMorris\Access\Role\Moderator'
					, 'Images' => [
						'_link'		=> ''
					]
				]
			]
		]
		, $list = ['images' => [
			'function' => 'byUrl'
			, 'params' => 'params'
		]]
	;

	public function index($router)
	{
		$get = $router->request()->get();

		if($get['url'] ?? FALSE)
		{
			static::$listBy = 'byUrl';
		}

		return parent::index($router);
	}

	protected static function params()
	{
		if($_GET['url'] ?? FALSE)
		{
			return [$_GET['url']];
		}

		return [];
	}

	public function create($router, $submitPost = TRUE)
	{
		$formClass = $this->_getForm('edit');
		$form      = new $formClass;

		$form = new $formClass([
			'_action' => '/' .  $router->request()->uri()
			, '_router'		=> $router
			, '_controller'	=> $this
		]);

		$images     = [];
		$modelClass = $this->modelClass;

		$files = $params = array_replace_recursive($router->request()->post(), $router->request()->files());

		if($submitPost && $files)
		{
			if(isset($params['image']) && is_array($params['image']))
			{
				foreach($params['image'] as $imageFile)
				{
					$image = new $modelClass;

					$image->consume(['image' => $imageFile]);

					if(!$image->url)
					{
						continue;
					}

					$image->save();

					if($image->id)
					{
						$images[] = $image;
					}

					if(count($images) >= static::$maxUploads)
					{
						break;
					}
				}

				$resourceClass = static::$resourceClass;
				$resource      = new $resourceClass($router);

				$resource->models($images);

				return $resource;
			}
		}

		return parent::create($router, $submitPost);
	}
}
