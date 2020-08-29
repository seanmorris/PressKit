<?php
namespace SeanMorris\PressKit\Form;
class UserForm extends \SeanMorris\PressKit\Form\Form
{
	public function __construct($skeleton = [])
	{
		$skeleton += static::skeleton($skeleton);

		$skeleton['submit'] = $skeleton['submit'] ?? [];

		$skeleton['submit'] += [
			'_title' => 'Submit',
			'value' => 'Submit',
			'type' => 'submit',
		];

		parent::__construct($skeleton);
	}

	protected static function skeleton($skeleton = [])
	{
		$skeleton['_method'] = 'POST';

		$skeleton['id'] = [
			'_title' => 'Id'
			, 'type' => 'hidden'
		];

		$skeleton['publicId'] = [
			'_title' => 'PublicId'
			, 'type' => 'hidden'
		];

		$skeleton['username'] = [
			'_title' => 'Username'
			, 'type' => 'text'
		];

		$skeleton['fbid'] = [
			'type' => 'hidden'
		];

		$skeleton['email'] = [
			'_title' => 'Email'
			, 'type' => 'text'
		];

		$skeleton['password'] = [
			'autocomplete' => 'new-password'
			, '_title'     => 'New Password'
			, 'type'       => 'password'
		];

		$skeleton['confirm_password'] = [
			'autocomplete' => 'new-password'
			, '_title'     => 'Confirm Password'
			, 'type'       => 'password'
		];

		$skeleton['roles'] = [
			'_title'            => 'Roles'
			, '_subtitle'       => 'Role'
			, 'type'            => 'modelSearch'
			, '_searchEndpoint' => '/user/roles'
			, '_createEndpoint' => FALSE
			, '_titlePoint'     => 'class'
			, '_array'          => TRUE
			, '_multi'          => TRUE
			, '_access'         => [
				'read' => ['SeanMorris\Access\Role\Administrator' => FALSE]
			]
		];

		return $skeleton;
	}
}
