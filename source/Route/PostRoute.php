<?php
namespace SeanMorris\PressKit\Route;
class PostRoute extends \SeanMorris\PressKit\Controller
{
	protected
		$title = 'Posts'
		// , $modelClass = 'SeanMorris\PressKit\Post'
		, $modelClass = 'SeanMorris\Portfolio\SuperPost'
		, $modelRoute = 'SeanMorris\PressKit\Route\PostSubRoute'
		, $formTheme = 'SeanMorris\Form\Theme\Form\Theme'
		, $access = [
			'create' => 'SeanMorris\Access\Role\Administrator'
			, 'edit' => 'SeanMorris\Access\Role\Administrator'
			, 'delete' => 'SeanMorris\Access\Role\Administrator'
			, 'view' => TRUE
			, 'index' => TRUE
			, '_contextMenu' => 'SeanMorris\Access\Role\Administrator'
		]
		, $hideTitle = [
			'index'
		]
	;
	protected static 
		$forms = [
			'edit' => 'SeanMorris\PressKit\Form\PostForm'
		]
		, $menus = [
			'main' => [
				'Content' => [
					'_access' => 'SeanMorris\Access\Role\Administrator'
					, 'Posts' => [
						'_link'		=> ''
						, 'List'	=> [
							'_link' => ''
							, '_access' => 'SeanMorris\Access\Role\Administrator'
						]
						, 'Create'	=> [
							'_link' => 'create'
						]
					]
				]
			]
		]
	;
}