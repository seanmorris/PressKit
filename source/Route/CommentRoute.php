<?php
namespace SeanMorris\PressKit\Route;
class CommentRoute extends \SeanMorris\PressKit\Controller
{
	protected
		$title = 'Comments'
		, $modelClass = 'SeanMorris\PressKit\Comment'
		, $modelRoute = 'SeanMorris\PressKit\Route\ModelSubRoute'
		, $formTheme = 'SeanMorris\Form\Theme\Form\Theme'
		, $listColumns = ['id', 'body']
		, $access = [
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
			'edit' => 'SeanMorris\PressKit\Form\CommentForm',
			'search' => 'SeanMorris\PressKit\Form\CommentSearchForm',
		]
		, $menus = [
			'main' => [
				'Content' => [
					'_access' => 'SeanMorris\Access\Role\Moderator'
					, 'Comments' => [
						'_link'		=> ''
						, 'List'	=> [
							'_link' => ''
						]
						, 'Create'	=> [
							'_link' => 'new'
						]
					]
				]
			]
		]
	;

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