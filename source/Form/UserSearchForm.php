<?php
namespace SeanMorris\PressKit\Form;
class UserSearchForm extends \SeanMorris\PressKit\Form\Form
{
	public function __construct()
	{
		$skeleton = [];

		$skeleton['_method'] = 'POST';

		$skeleton['id'] = [
			'type' => 'hidden'
		];

		$skeleton['keyword'] = [
			'_title' => 'Search Term'
			, 'type' => 'text'
		];

		$skeleton['submit'] = [
			'_title' => 'Submit',
			'type' => 'submit',
		];
		
		parent::__construct($skeleton);
	}
}