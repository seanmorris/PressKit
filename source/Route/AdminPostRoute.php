<?php
namespace SeanMorris\PressKit\Route;
class AdminPostRoute extends PostRoute
{
	protected
		$access = [
			'create' => 'SeanMorris\Access\Role\Administrator'
			, 'edit' => 'SeanMorris\Access\Role\Administrator'
			, 'delete' => 'SeanMorris\Access\Role\Administrator'
			, 'view' => 'SeanMorris\Access\Role\Administrator'
			, 'index' => 'SeanMorris\Access\Role\Administrator'
			, '_contextMenu' => 'SeanMorris\Access\Role\Administrator'
			, '_publishModels' => 'SeanMorris\Access\Role\Administrator'
			, '_unpublishModels' => 'SeanMorris\Access\Role\Administrator'
		]
		, $listColumns = [
			'title'
		]
	;
	protected static 
		$listBy = 'byNull'
		, $forms = [
			'edit' => 'SeanMorris\PressKit\Form\PostForm'
			, 'search' => 'SeanMorris\PressKit\Form\CommentSearchForm'
		]
		, $actions = [
			'Unpublish' => '_unpublishModels'
			, 'Publish' => '_publishModels'
			, 'Delete' => '_deleteModels'
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