<?php
namespace SeanMorris\PressKit\Route;
class AdminCommentRoute extends CommentRoute
{
	protected
		$access = [
			'edit' => 'SeanMorris\Access\Role\Administrator'
			, 'delete' => 'SeanMorris\Access\Role\Administrator'
			, 'view' => TRUE
			, 'index' => TRUE
			, '_contextMenu' => 'SeanMorris\Access\Role\Administrator'
		]
	;
	protected static
		$forms = [
			'edit' => 'SeanMorris\PressKit\Form\CommentForm',
			'search' => 'SeanMorris\PressKit\Form\CommentSearchForm',
		]
		, $menus = [
			'main' => [
				'Administrate' => [
					'Content' => [
						'_access' => 'SeanMorris\Access\Role\Moderator'
						, 'Comments' => [
							'_link'		=> ''
						]
					]
				]
			]
		]
	;

	/*
	public function moderate($router)
	{
		$class = $this->modelClass;

		$comments = $class::getByState(['ssss' => 0]);
		$theme = $this->_getTheme($router);

		$view = new \SeanMorris\PressKit\Theme\Austere\ModelGrid([
			'path' => $router->path()->pathString()
			, 'content' => $comments
			, 'columns' => [
				'id'
				, 'title'
			]
		]);

		return $view;

		foreach($comments as &$comment)
		{
			$comment = $theme::render($comment, ['path' => $router->path()->pathString()]);
		}

		return implode(PHP_EOL, $comments);
	}
	*/

	protected static function beforeCreate($instance, &$skeleton)
	{
		$session = \SeanMorris\Ids\Meta::staticSession(1);

		\SeanMorris\Ids\Log::debug($session);

		if(isset($session['user']) && $session['user'])
		{
			$instance->addSubject('author', $session['user']);
		}
		else
		{
			return false;
		}
	}
}