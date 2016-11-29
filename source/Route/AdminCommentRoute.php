<?php
namespace SeanMorris\PressKit\Route;
class AdminCommentRoute extends CommentRoute
{
	protected
		$access = [
			'edit' => 'SeanMorris\Access\Role\Moderator'
			, 'delete' => 'SeanMorris\Access\Role\Moderator'
			, 'view' => 'SeanMorris\Access\Role\Moderator'
			, 'owners' => 'SeanMorris\Access\Role\Moderator'
			, 'index' => 'SeanMorris\Access\Role\Moderator'
			, '_contextMenu' => 'SeanMorris\Access\Role\Moderator'
			, '_publishModels' => 'SeanMorris\Access\Role\Moderator'
			, '_unpublishModels' => 'SeanMorris\Access\Role\Moderator'
			, '_deleteModels' => 'SeanMorris\Access\Role\Moderator'
		]
		, $listColumns = [
			'title'
		]
	;
	protected static
		$listBy = 'byNull'
		, $forms = [
			'edit' => 'SeanMorris\PressKit\Form\CommentForm',
			'search' => 'SeanMorris\PressKit\Form\CommentSearchForm',
		]
		, $pageSize = 10
		, $pageSpread = 5
		, $actions = [
			'Unpublish' => '_unpublishModels'
			, 'Publish' => '_publishModels'
			, 'Delete' => '_deleteModels'
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