<?php
namespace SeanMorris\PressKit\Route;
class AdminRoute extends \SeanMorris\PressKit\Controller
{
	public
		$title = 'Admin'
		, $access = [
			'index'       =>   'SeanMorris\Access\Role\Administrator'
			, 'posts'     => 'SeanMorris\Access\Role\Administrator'
			, 'images'    => 'SeanMorris\Access\Role\Administrator'
			, 'comments'  => 'SeanMorris\Access\Role\Administrator'
		]
		, $routes = [
			'posts'       => 'SeanMorris\PressKit\Route\AdminPostRoute'
			, 'images'    => 'SeanMorris\PressKit\Route\AdminImageRoute'
			, 'comments'  => 'SeanMorris\PressKit\Route\AdminCommentRoute'
		]
	;

	// protected $theme = 'SeanMorris\PressKit\Theme\Austere\Theme';

	protected static
		$menus = [
			'main' => [
				'Administrate' => [
					'_link' => ''
					, '_access' => 'SeanMorris\Access\Role\Administrator'
				]
			]
		]
	;

	public function index($router)
	{
		$output = [];
		foreach($this->routes as $path => $route)
		{
			$output[] = sprintf('<a href = "/admin/%s">%s</a>', $path, $path);
		}

		return implode('<br />', $output);
	}
}