<?php
namespace SeanMorris\PressKit\State;
class ImageState extends \SeanMorris\PressKit\State
{
	protected static
		$states	= [
			0 => [
				'create'	 => 'SeanMorris\Access\Role\User'
				, 'read'	 => ['SeanMorris\Access\Role\User', 'SeanMorris\Access\Role\Moderator']
				, 'update'	 => [1, 'SeanMorris\Access\Role\Moderator']
				, 'delete'	 => [1, 'SeanMorris\Access\Role\Administrator']
				, '$class'   => FALSE
				, '$state'   => FALSE
			]
			, 1 => [
				'read'		 => 1
				, 'update'	 => [1, 'SeanMorris\Access\Role\Administrator']
				, 'delete'	 => [1, 'SeanMorris\Access\Role\Administrator']
				, '$class'   => FALSE
				, '$state'   => FALSE
			]
		];
}