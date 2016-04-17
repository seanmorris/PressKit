<?php
namespace SeanMorris\PressKit\Form;
class UserForm extends \SeanMorris\PressKit\Form\Form
{
	public function __construct()
	{
		$skeleton = [];

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

		$skeleton['email'] = [
			'_title' => 'Email'
			, 'type' => 'text'
		];

		$skeleton['password'] = [
			'_title' => 'password'
			, 'type' => 'password'
			, '_lock' => true
		];

		$skeleton['roles'] = [
			'_title' => 'Roles'
			, '_subtitle' => 'Role'
			, 'type' => 'modelSearch'
			, '_searchEndpoint' => '/users/roles'
			, '_titlePoint' => 'class'
		];

		$skeleton['submit'] = [
			'_title' => 'Submit',
			'type' => 'submit',
		];
		
		parent::__construct($skeleton);
	}
}