<?php
namespace SeanMorris\PressKit\Route;
class ImageRoute extends \SeanMorris\PressKit\Controller
{
	protected
		$title = 'Images'
		, $modelClass = 'SeanMorris\PressKit\Image'
		, $formTheme = 'SeanMorris\Form\Theme\Theme'
		, $access = [
			'create' => 'SeanMorris\Access\Role\Administrator'
			, 'edit' => 'SeanMorris\Access\Role\Administrator'
			, 'delete' => 'SeanMorris\Access\Role\Administrator'
			, 'view' => TRUE
			, 'index' => TRUE
			//, 'index' => 'SeanMorris\Access\Role\Administrator'
			, '_contextMenu' => 'SeanMorris\Access\Role\Administrator'
		]
	;
	protected static 
		$forms = [
			'edit' => 'SeanMorris\PressKit\Form\ImageForm',
			'search' => 'SeanMorris\PressKit\Form\ImageSearchForm',
		]
		, $menus = [
			'main' => [
				'Content' => [
					'_access' => 'SeanMorris\Access\Role\Moderator'
					, 'Images' => [
						'_link'		=> ''
					]
				]
			]
		]
	;
}