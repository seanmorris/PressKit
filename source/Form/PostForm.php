<?php
namespace SeanMorris\PressKit\Form;
class PostForm extends \SeanMorris\Form\Form
{
	public function __construct()
	{
		$skeleton = [];

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

		$skeleton['written'] = [
			'_title' => 'Written'
			, 'type' => 'text'
		];

		$skeleton['edited'] =[
			'_title' => 'Edited'
			, 'type' => 'text'
		];

		$skeleton['weight'] = [
			'_title' => 'Weight'
			, 'type' => 'text'
		];

		$skeleton['author'] = [
			'_title' => 'Author'
			, 'type' => 'text'
		];

		$skeleton['category'] =[ 
			'_title' => 'Category'
			, 'type' => 'text'
		];

		$skeleton['slugsize'] = [
			'_title' => 'Slugsize'
			, 'type' => 'text'
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

		$skeleton['state'] = [ 
			'_title' => 'State'
			, 'type' => 'text'
		];

		$skeleton['images'] = [
			'_title' => 'Images'
			, '_subtitle' => 'Image'
			, 'type' => 'modelSearch'
			, '_searchEndpoint' => '/images'
			, '_previewImagePoint' => 'url'
			, '_titlePoint' => 'title'
		];

		$skeleton['comments'] = [
			'_title' => 'Comments'
			, '_subtitle' => 'Comment'
			, 'type' => 'modelSearch'
			, '_searchEndpoint' => '/comments'
			, '_previewImagePoint' => 'url'
			, '_titlePoint' => 'title'
		];

		$skeleton['comments2'] = [
			'_title' => 'Comments'
			, '_subtitle' => 'Comment'
			, 'type' => 'modelSearch'
			, '_searchEndpoint' => '/comments'
			, '_previewImagePoint' => 'url'
			, '_titlePoint' => 'title'
		];

		$skeleton['formatter'] = [
			'_title' => 'Formatter'
			, 'value' => ''
			, 'type' => 'text'
		];

		$skeleton['test'] = [
			'_title' => 'Test Field'
			, 'value' => ''
			, 'type' => 'text'
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