<?php
namespace SeanMorris\PressKit\Route;
class UserSubRoute extends \SeanMorris\PressKit\Route\ModelSubRoute
{
	public
		$routes = [
			'roles' => 'SeanMorris\PressKit\Route\RoleRoute'
		]
		, $subRoutes = [
			'view' => ['roles']
		]
		, $access = [
			//'edit' => 'SeanMorris\Access\Role\Administrator'
		]
	;
}