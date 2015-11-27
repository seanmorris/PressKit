<?php
namespace SeanMorris\PressKit\Route;
class AdminPostRoute extends PostRoute
{
	protected
		$access = [
			'create' => 'SeanMorris\Access\Role\Administrator'
			, 'edit' => 'SeanMorris\Access\Role\Administrator'
			, 'delete' => 'SeanMorris\Access\Role\Administrator'
			, 'view' => TRUE
			, 'index' => TRUE
			, '_contextMenu' => 'SeanMorris\Access\Role\Administrator'
		]
	;
	protected static 
		$forms = [
			'edit' => 'SeanMorris\PressKit\Form\PostForm'
			, 'search' => 'SeanMorris\PressKit\Form\CommentSearchForm'
		]
		, $menus = [
			'main' => [
				'Administrate' => [
					'Content' => [
						'_access' => 'SeanMorris\Access\Role\Administrator'
						, 'Posts' => [
							'_link'		=> ''
							, 'Create'	=> [
								'_link' => 'create'
								, '_access' => 'SeanMorris\Access\Role\Administrator'
							]
						]
					]
				]
			]
		]
	;
	
}