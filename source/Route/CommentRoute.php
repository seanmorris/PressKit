<?php
namespace SeanMorris\PressKit\Route;
class CommentRoute extends \SeanMorris\PressKit\Controller
{
	protected
		$title = 'Comments'
		, $modelClass = 'SeanMorris\PressKit\Comment'
		, $formTheme = 'SeanMorris\Form\Theme\Theme'
		, $listColumns = [
			'id'
			, 'title'
			, 'body'
		]
		, $access = [
			'view'     => TRUE
			, 'index'  => TRUE
			, 'create' => TRUE
			, 'edit'   => 'SeanMorris\Access\Role\User'
			, 'delete' => 'SeanMorris\Access\Role\User'
		]
	;
	
	protected static
		$listBy = 'byModerated'  
		, $forms = [
			'edit' => 'SeanMorris\PressKit\Form\CommentForm',
			'search' => 'SeanMorris\PressKit\Form\CommentSearchForm',
		]
		, $menus = [
			'main' => [
				'Content' => [
					'_access' => 'SeanMorris\Access\Role\Moderator'
					, 'Comments' => [
						'_link'		=> ''
					]
				]
			]
		]
	;

	protected static function afterCreate($instance, &$skeleton)
	{
		$messages = \SeanMorris\Message\MessageHandler::get();

		if($instance->id)
		{
			$state = $instance->getSubject('state');

			if($state->state)
			{
				$messages->addFlash(new \SeanMorris\Message\SuccessMessage(
					'Comment submitted.'
				));	
			}
			else
			{
				$messages->addFlash(new \SeanMorris\Message\SuccessMessage(
					'Comment submitted for moderation. It will appear after review.'
				));	
			}
		}
	}
}