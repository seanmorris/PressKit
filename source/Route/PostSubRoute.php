<?php
namespace SeanMorris\PressKit\Route;
class PostSubRoute extends \SeanMorris\PressKit\Route\ModelSubRoute
{
	public
		$routes = [
			'images' => '\SeanMorris\PressKit\Route\ImageRoute'
			, 'comments' => '\SeanMorris\PressKit\Route\CommentRoute'
			//, 'comments2' => '\SeanMorris\PressKit\Route\CommentRoute'
		]
		, $subRoutes = [
			'view' => [
				'comments', 'comments/create'
				//, 'comments2', 'comments2/create'
				, 'images'
			]
		]
	;
}