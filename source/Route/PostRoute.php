<?php
namespace SeanMorris\PressKit\Route;
class PostRoute extends \SeanMorris\PressKit\Controller
{
	protected
		$title = 'Posts'
		, $modelClass = 'SeanMorris\PressKit\Post'
		, $formTheme = 'SeanMorris\Form\Theme\Theme'
		, $modelRoutes = [
			'images' => '\SeanMorris\PressKit\Route\ImageRoute'
			, 'comments' => '\SeanMorris\PressKit\Route\CommentRoute'
		]
		, $modelSubRoutes = [
			'view' => [
				'comments', 'comments/create'
				 , 'images'
			]
		]
		, $access = [
			'view' => TRUE
			, 'edit' => '\SeanMorris\Access\Role\Administrator'
			, 'create' => '\SeanMorris\Access\Role\Administrator'
			, 'delete' => '\SeanMorris\Access\Role\Administrator'
			, 'index' => TRUE
		]
		, $hideTitle = [
			'index'
		]
	;
	protected static
		$listBy = 'byModerated'
		, $pageSize = 16
		, $pageSpread = 2
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