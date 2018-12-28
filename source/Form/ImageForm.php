<?php
namespace SeanMorris\PressKit\Form;
class ImageForm extends \SeanMorris\PressKit\Form\Form
{
	public function __construct(array $skeleton = [])
	{
		$skeleton['_method'] = 'POST';

		$skeleton['title'] = [
			'_title' => 'Title',
		];

		$skeleton['image'] = $skeleton['image'] + [
			'_title' => 'Image File',
			'type' => 'file',
		];

		// $skeleton['state'] = [
		// 	'_title' => 'State'
		// 	, '_subtitle' => 'State'
		// 	, '_class' => 'SeanMorris\PressKit\Form\StateReferenceField'
		// 	, '_multi' => FALSE
		// ];

		$skeleton['submit'] = [
			'_title' => 'Submit',
			'type' => 'submit',
		];

		$skeleton['id'] = [
			'type' => 'hidden'
		];

		$skeleton['publicId'] = [
			'type' => 'hidden'
		];

		parent::__construct($skeleton);
	}

	public function create($router, $submitPost = TRUE)
	{
		$formClass = $this->_getForm('edit');
		$form      = new $formClass;

		$form = new $formClass([
			'_action' => '/' .  $router->request()->uri()
			, '_router'		=> $router
			, '_controller'	=> $this
		]);

		if($submitPost && $params = array_replace_recursive($router->request()->post(), $router->request()->files()))
		{
			if($form->validate($params))
			{
				$modelClass = $this->modelClass;
				$model = new $modelClass;
				$skeleton = $form->getValues();

				var_dump($skeleton);
				die;
			}
		}

		die;
	}
}
