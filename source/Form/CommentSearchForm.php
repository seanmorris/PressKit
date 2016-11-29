<?php
namespace SeanMorris\PressKit\Form;
class CommentSearchForm extends \SeanMorris\PressKit\Form\Form
{
	public function __construct()
	{
		$skeleton = [];

		$skeleton['_method'] = 'GET';

		$skeleton['search'] = [
			'_title' => 'Search'
			, 'type' => 'fieldset'
		];

		$skeleton['search']['_children']['keyword'] = [
			'_title' => 'Keyword'
			, 'type' => 'text'
		];

		$skeleton['search']['_children']['id'] = [
			'type' => 'hidden'
		];

		$skeleton['search']['_children']['state'] = [
			'_title' => 'State'
			, 'value' => ''
			, 'type' => 'select'
			, '_options' => [
				NULL => NULL
				, 0  => 'Unpublished'
				, 1  => 'Published'
			]
		];
		$skeleton['search']['_children']['submit'] = [
			'_title' => 'Submit'
			, 'type' => 'submit'
		];

		parent::__construct($skeleton);
	}
}