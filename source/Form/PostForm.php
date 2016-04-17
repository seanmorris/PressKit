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

		$skeleton['slugsize'] = [
			'_title' => 'Slugsize'
			, 'type' => 'text'
		];

		/*$skeleton['state'] = [ 
			'_title' => 'State'
			, 'type' => 'text'
		];*/

		$skeleton['images'] = [
			'_title' => 'Images'
			, '_subtitle' => 'Image'
			, 'type' => 'modelSearch'
			, '_searchEndpoint' => '/images'
			, '_previewImagePoint' => 'url'
			, '_titlePoint' => 'title'
		];
		/*
		$skeleton['comments'] = [
			'_title' => 'Comments'
			, '_subtitle' => 'Comment'
			, 'type' => 'modelSearch'
			, '_searchEndpoint' => '/comments'
			, '_previewImagePoint' => 'url'
			, '_titlePoint' => 'title'
		];
		*/

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
		/*
		$stateFields = [];

		$stateFields['id'] = [
			'_title' => 'Id'
			, 'value' => ''
			, 'type' => 'text'
		];

		$stateFields['state'] = [
			'_title' => 'State'
			, 'value' => ''
			, 'type' => 'text'
		];
		*/

		$skeleton['written'] = [
			'_title' => 'Written'
			, 'type' => 'text'
			, '_access' => [
				'read' => [TRUE  => '']
				, 'write' => [FALSE => ['disabled' => 'disabled']]
			]
		];

		$skeleton['edited'] =[
			'_title' => 'Edited'
			, 'type' => 'text'
			, '_access' => [
				'read' => [TRUE  => '']
				, 'write' => [FALSE => ['disabled' => 'disabled']]
			]
		];

		/*

		$skeleton['author'] = [
			'_title' => 'Author'
			, 'type' => 'text'
			, '_access' => [
				'read' => [TRUE  => '']
				, 'write' => [FALSE => ['disabled' => 'disabled']]
			]
		];
		*/

		
		/*
		$skeleton['state'] = [
			'_title' => 'State Fields'
			, '_subtitle' => 'State'
			, 'type' => 'modelReference'
			, '_children' => $stateFields
			, '_multi' => FALSE
		];

		$skeleton['checked'] = [
			'_title' => 'Checked'
			, 'type' => 'checkbox'
		];
		*/

		parent::__construct($skeleton);
	}
}