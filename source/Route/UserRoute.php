<?php
namespace SeanMorris\PressKit\Route;
class UserRoute extends \SeanMorris\PressKit\Controller
{
	public
		$routes = [
			'roles' => 'SeanMorris\Access\Route\RoleRoute'
		];
	protected
		$modelClass = 'SeanMorris\Access\User'
		, $modelRoute = 'SeanMorris\PressKit\Route\UserSubRoute'
		, $formTheme = 'SeanMorris\Form\Theme\Form\Theme'
		, $listColumns = ['id', 'username', 'created']
		, $columnClasses = ['id' => 'droppable', 'created' => 'droppable']
		, $access = [
			'create' => 'SeanMorris\Access\Role\Administrator'
			, 'edit' => 'SeanMorris\Access\Role\Administrator'
			, 'delete' => 'SeanMorris\Access\Role\Administrator'
			, 'view' => TRUE
			, 'index' => 'SeanMorris\Access\Role\Administrator'
			, '_contextMenu' => 'SeanMorris\Access\Role\Administrator'
		]
	;
	protected static 
		$forms = [
			'edit' => 'SeanMorris\PressKit\Form\UserForm'
		]
		, $menus = [
			'main' => [
				'Content' => [
					'_access' => 'SeanMorris\Access\Role\Moderator'
					, 'Users' => [
						'_link'		=> ''
						, 'List'	=> [
							'_link' => ''
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