<?php
namespace SeanMorris\PressKit\Form;
class CommentSearchForm extends \SeanMorris\Form\Form
{
	public function __construct()
	{
		$skeleton = [];

		$skeleton['_method'] = 'POST';

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

		$skeleton['submit'] = [
			'_title' => 'Submit'
			, 'type' => 'submit'
		];

		parent::__construct($skeleton);
	}
}