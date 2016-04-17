<?php
namespace SeanMorris\PressKit\Form;
class CommentForm extends \SeanMorris\PressKit\Form\Form
{
	public function __construct($skeleton = [])
	{
		$skeleton['_method'] = 'POST';

		$fail = sprintf(
			'<i>Please <a href = "%s">log in</a> or <a href = "%s">register</a> to post a comment.</i>'
			, \SeanMorris\Access\Route\AccessRoute::_loginLink($skeleton['_router'])
			, \SeanMorris\Access\Route\AccessRoute::_registerLink($skeleton['_router'])
		);

		$user = \SeanMorris\Access\Route\AccessRoute::_currentUser();

		if($user->id)
		{
			$fail = 'Verify your email address to post comments.';
		}

		$skeleton['_access'] = [
			'SeanMorris\Access\Role\User' => ['error' => [
				'type'    => 'html'
				, 'value' => $fail
			]]
		];

		$skeleton['id'] = [
			'_title' => 'Id'
			, 'type' => 'hidden'
		];

		$skeleton['publicId'] = [
			'_title' => 'PublicId'
			, 'type' => 'hidden'
		];

		$skeleton['title'] = [
			'_title' => 'Title'
			, 'type' => 'text'
			, '_validators' => [
				'SeanMorris\Form\Validator\Regex' => [
					'/.{8,}/' => '%s must be at least 8 characters'
				]
			]
		];

		$skeleton['body'] = [
			'_title' => 'Body'
			, 'type' => 'textarea'
			/*, '_access' => [
				'read' => [
					'SeanMorris\Access\Role\Administrator' => NULL
				]
				, 'write' => [
					'SeanMorris\Access\Role\Moderator' => [
						'_title'     => 'Title'
						, 'disabled' => 'disabled'
					]
				]
			]*/
			, '_validators' => [
				'SeanMorris\Form\Validator\Regex' => [
					'/.{25,}/' => '%s must be at least 25 characters'
				]
			]
		];

		$stateFields = [];

		$stateFields['id'] = [
			'_title' => 'Id'
			, 'type' => 'hidden'
		];

		$stateFields['state'] = [
			'_title' => 'State'
			, 'type' => 'text'
		];

		$skeleton['state'] = [
			'_title' => 'Moderate'
			, '_subtitle' => 'State'
			, 'type' => 'modelReference'
			, '_children' => $stateFields
			, '_multi' => FALSE
			, '_access' => [
				'write' => ['SeanMorris\Access\Role\Administrator' => NULL]
				, 'read' => ['SeanMorris\Access\Role\Administrator' => NULL]
			]
		];
		
		$skeleton['submit'] = [
			'_title' => 'Submit'
			, 'type' => 'submit'
		];
		
		parent::__construct($skeleton);
	}
}