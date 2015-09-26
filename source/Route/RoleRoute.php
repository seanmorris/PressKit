<?php
namespace SeanMorris\PressKit\Route;
class RoleRoute extends \SeanMorris\PressKit\Controller
{
	protected
		$modelClass = 'SeanMorris\Access\Role'
		//, $modelRoute = 'SeanMorris\PressKit\Route\ModelSubRoute'
		, $formTheme = 'SeanMorris\Form\Theme\Form\Theme'
		, $listColumns = ['id', 'class']
		, $columnClasses = ['id' => 'droppable']
	;
	protected static 
		$menus = [
			'main' => [
				'Content' => [
					'_access' => 'SeanMorris\Access\Role\Administrator'
					, 'Roles' => [
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