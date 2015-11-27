<?php
namespace SeanMorris\PressKit\Form;
class CommentForm extends \SeanMorris\Form\Form
{
	public function __construct($skeleton = [])
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

		$skeleton['title'] = [
			'_title' => 'Title'
			, 'type' => 'text'
			, '_validators' => [
				'SeanMorris\Form\Validator\RegexValidator' => [
					'/.{8,}/' => '%s must be at least 8 characters'
				]
			]
		];

		$skeleton['body'] = [
			'_title' => 'Body'
			, 'type' => 'textarea'
			, '_validators' => [
				'SeanMorris\Form\Validator\RegexValidator' => [
					'/.{25,}/' => '%s must be at least 25 characters'
				]
			]
		];
		
		$skeleton['submit'] = [
			'_title' => 'Submit'
			, 'type' => 'submit'
		];
		
		parent::__construct($skeleton);
	}
}