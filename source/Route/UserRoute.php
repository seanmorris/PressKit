<?php
namespace SeanMorris\PressKit\Route;
class UserRoute extends \SeanMorris\PressKit\Controller
{
	public
		$routes = [
			'roles' => 'SeanMorris\Access\Route\RoleRoute'
		]
		, $modelRoutes = [
			'roles' => 'SeanMorris\PressKit\Route\RoleRoute'
		]
		, $modelSubRoutes = [
			'view' => ['roles']
		]
	;
	protected
		$title = 'Users'
		, $modelClass = 'SeanMorris\Access\User'
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
		$titleField = 'username'
		, $modelRoute = 'SeanMorris\PressKit\Route\UserSubRoute' 
		, $forms = [
			'edit' => 'SeanMorris\PressKit\Form\UserForm'
		]
		, $menus = [
			'main' => [
				'Administrate' => [
					'_access' => 'SeanMorris\Access\Role\Moderator'
					, 'Users' => [
						'_link'		=> ''
						, 'Create'	=> [
							'_link' => 'create'
						]
					]
				]
			]
		]
	;
}