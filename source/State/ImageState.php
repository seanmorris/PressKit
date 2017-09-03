<?php
namespace SeanMorris\PressKit\State;
class ImageState extends \SeanMorris\PressKit\State
{
	protected static
		$states	= [
			0 => [
				'create'	 => 'SeanMorris\Access\Role\User'
				, 'read'	 => ['SeanMorris\Access\Role\User', 'SeanMorris\Access\Role\Moderator']
				, 'update'	 => [TRUE, 'SeanMorris\Access\Role\Moderator']
				, 'delete'	 => [TRUE, 'SeanMorris\Access\Role\Administrator']
				, '$class'   => [
					'read' 		=> TRUE
					, 'write'   => 'SeanMorris\Access\Role\Administrator'
				]
				, '$state'   => [
					'read' 		=> TRUE
					, 'write'   => 'SeanMorris\Access\Role\Administrator'
				]
				, '$title'	 => [
					'read' 		=> TRUE
					, 'write'   => ['SeanMorris\Access\Role\User', 'SeanMorris\Access\Role\Moderator']
				]
				, '$image'	 => [
					'read' 		=> TRUE
					, 'write'   => ['SeanMorris\Access\Role\User', 'SeanMorris\Access\Role\Moderator']
				]
			]
			, 1 => [
				'read'		 => 1
				, 'update'	 => [1, 'SeanMorris\Access\Role\Administrator']
				, 'delete'	 => [1, 'SeanMorris\Access\Role\Administrator']
				, '$class'   => FALSE
				, '$state'   => [
					'read' 		=> TRUE
					, 'write'   => 'SeanMorris\Access\Role\Administrator'
				]
				, '$title'	 => [
					'read' 		=> TRUE
					, 'write'   => ['SeanMorris\Access\Role\User', 'SeanMorris\Access\Role\Moderator']
				]
				, '$image'	 => [
					'read' 		=> TRUE
					, 'write'   => ['SeanMorris\Access\Role\User', 'SeanMorris\Access\Role\Moderator']
				]
			]
		]
		, $transitions	= [
			0 => [
				1 => 'SeanMorris\Access\Role\Administrator'
			]
			, 1 => [
				0 => 'SeanMorris\Access\Role\Administrator'
			]
		];
}
