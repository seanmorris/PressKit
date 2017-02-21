<?php
namespace SeanMorris\PressKit\Form;
class PostForm extends \SeanMorris\PressKit\Form\Form
{
	public function __construct(array $skeleton = [])
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
		];

		$skeleton['body'] = [
			'_title' => 'Body'
			, 'type' => 'textarea'
			, 'rows' => 15
		];

		$skeleton['summary'] = [
			'_title' => 'Summary'
			, 'type' => 'textarea'
			, 'rows' => 5
		];

		$skeleton['weight'] = [
			'_title' => 'Weight'
			, 'type' => 'text'
			, '_access' => [
				'read' => [FALSE  => '']
				, 'write' => [FALSE => '']
			]
		];

		$skeleton['category'] =[
			'_title' => 'Category'
			, 'type' => 'text'
			, '_access' => [
				'read' => [FALSE  => '']
				, 'write' => [FALSE => '']
			]
		];

		$skeleton['ctaLink'] = [
			'_title' => 'CtaLink'
			, 'value' => ''
			, 'type' => 'text'
		];

		$skeleton['ctaLinkText'] = [
			'_title' => 'CtaLinkText'
			, 'type' => 'text'
			, 'value' => ''
		];

		$skeleton['slugSize'] = [
			'_title' => 'Slugsize'
			, 'type' => 'text'
		];

		$skeleton['images'] = [
			'_title' => 'Images'
			, '_subtitle' => 'Image'
			, 'type' => 'modelSearch'
			, '_searchEndpoint' => '/images'
			, '_previewImagePoint' => 'url'
			, '_titlePoint' => 'title'
			, '_array' => TRUE
			, '_multi' => TRUE
		];

		$skeleton['state'] = [
			'_title' => 'State'
			, '_subtitle' => 'State'
			, '_class' => 'SeanMorris\PressKit\Form\StateReferenceField'
			, '_multi' => FALSE
		];

		$skeleton['saveContinue'] = [
			'_title' => 'Save & Continue'
			, 'type' => 'submit'
		];

		$skeleton['saveView'] = [
			'_title' => 'Save & View'
			, 'type' => 'submit'
		];

		$skeleton['saveExit'] = [
			'_title' => 'Save & Exit'
			, 'type' => 'submit'
		];

		parent::__construct($skeleton);
	}
}
