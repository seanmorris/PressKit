<?php
namespace SeanMorris\PressKit\Route;
class PostRoute extends \SeanMorris\PressKit\Controller
{
	protected
		$title = 'Posts'
		// , $modelClass = 'SeanMorris\PressKit\Post'
		, $modelClass = 'SeanMorris\Portfolio\SuperPost'
		, $formTheme = 'SeanMorris\Form\Theme\Form\Theme'
		, $routes = [
			'images' => 'SeanMorris\PressKit\Route\ImageRoute'
			, 'comments' => 'SeanMorris\PressKit\Route\CommentRoute'
		]
		, $access = [
			'view' => TRUE
			, 'index' => TRUE
		]
		, $hideTitle = [
			'index'
		]
	;
	protected static
		$listBy = 'byModerated' 
		, $modelRoute = 'SeanMorris\PressKit\Route\PostSubRoute' 
		, $forms = [
			'edit' => 'SeanMorris\PressKit\Form\PostForm'
		]
		, $menus = [
			'main' => [
				'Content' => [
					'_access' => 'SeanMorris\Access\Role\Moderator'
					, 'Posts' => [
						'_link'		=> ''
						, '_weight'	=> -1
					]
				]
			]
		]
	;
}