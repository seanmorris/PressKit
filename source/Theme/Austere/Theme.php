<?php
namespace SeanMorris\PressKit\Theme\Austere;
class Theme extends \SeanMorris\Theme\Theme
{
	protected static
		$view = [
			//'SeanMorris\Ids\Model' => 'SeanMorris\PressKit\Theme\Austere\Model',
			'SeanMorris\Ids\Model' => [
				'single' => 'SeanMorris\PressKit\Theme\Austere\Model',
				'list' => 'SeanMorris\PressKit\Theme\Austere\ModelGrid',
			],
			//'SeanMorris\PressKit\Post' => 'SeanMorris\PressKit\Theme\Austere\Model',
		]
		, $list = [
			//'SeanMorris\Ids\Model' => 'SeanMorris\PressKit\Theme\Austere\Grid',
		]
		, $themes = [
			'SeanMorris\Form\Theme\Form\Theme'
		]
	;
}