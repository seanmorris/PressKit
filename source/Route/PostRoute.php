<?php
namespace SeanMorris\PressKit\Route;
class PostRoute extends \SeanMorris\PressKit\Controller
{
	protected
		$title = 'Posts'
		, $modelClass = 'SeanMorris\PressKit\Post'
		, $formTheme = 'SeanMorris\Form\Theme\Form\Theme'
		, $modelRoutes = [
			'images' => '\SeanMorris\PressKit\Route\ImageRoute'
			, 'comments' => '\SeanMorris\PressKit\Route\CommentRoute'
			, 'comments2' => '\SeanMorris\PressKit\Route\CommentRoute'
		]
		, $modelSubRoutes = [
			'view' => [
				'comments', 'comments/create'
				, 'comments2', 'comments2/create'
				, 'images'
			]
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