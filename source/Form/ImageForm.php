<?php
namespace SeanMorris\PressKit\Form;
class ImageForm extends \SeanMorris\PressKit\Form\Form
{
	public function __construct()
	{
		$skeleton = [];

		$skeleton['_method'] = 'POST';

		$skeleton['id'] = [
			'type' => 'hidden'
		];

		$skeleton['publicId'] = [
			'type' => 'hidden'
		];

		$skeleton['title'] = [
			'_title' => 'Title',
		];

		$skeleton['image'] = [
			'_title' => 'Image File',
			'type' => 'file',
		];

		$skeleton['state'] = [
			'_title' => 'State'
			, '_subtitle' => 'State'
			, '_class' => 'SeanMorris\PressKit\Form\StateReferenceField'
			, '_multi' => FALSE
		];

		$skeleton['submit'] = [
			'_title' => 'Submit',
			'type' => 'submit',
		];


		parent::__construct($skeleton);
	}
}
