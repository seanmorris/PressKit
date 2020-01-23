<?php
namespace SeanMorris\PressKit\Form;
class UserSearchForm extends \SeanMorris\PressKit\Form\Form
{
	public function __construct(array $skeleton = [])
	{
		$skeleton['_method'] = 'POST';

		$skeleton['id'] = [
			'type' => 'hidden'
		];

		$skeleton['keyword'] = [
			'_title' => 'Keyword'
			, 'type' => 'text'
		];

		$skeleton['submit'] = [
			'_title' => 'Submit',
			'type' => 'submit',
		];
		
		parent::__construct($skeleton);
	}
}
