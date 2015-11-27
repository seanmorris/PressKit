<?php
namespace SeanMorris\PressKit\Form;
class CommentSearchForm extends \SeanMorris\Form\Form
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
			, 'type' => 'text'
			, 'value' => 0
		];

		$skeleton['submit'] = [
			'_title' => 'Submit'
			, 'type' => 'submit'
		];

		parent::__construct($skeleton);
	}
}