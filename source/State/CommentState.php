<?php
namespace SeanMorris\PressKit\State;
class CommentState extends \SeanMorris\PressKit\State
{
	protected static
		$states	= [
			0 => [
				'create'	=> 'SeanMorris\Access\Role\User'
				, 'read'	 => [
					'SeanMorris\Access\Role\User'
					, 'SeanMorris\Access\Role\Moderator'
				]
				, 'update'	 => [1, 'SeanMorris\Access\Role\Moderator']
				, 'delete'	 => [1, 'SeanMorris\Access\Role\Administrator']
				
				, '$title'	=> [
					'write'  => [1, 'SeanMorris\Access\Role\Moderator']
					, 'read' => 1
				]
				, '$body'	=> [
					'write'  => [1, 'SeanMorris\Access\Role\Moderator']
					, 'read' => 1
				]
				, '$state'	=> [
					'write'  => 'SeanMorris\Access\Role\Moderator'
					, 'read' => 'SeanMorris\Access\Role\Moderator'
				]
				, '$class'   => FALSE
			]
			, 1 => [
				'read'	 => 1
				, 'update'	 => [1, 'SeanMorris\Access\Role\Moderator']
				, 'delete'	 => [1, 'SeanMorris\Access\Role\Administrator']
				
				, '$title'	=> [
					'write'  => [1, 'SeanMorris\Access\Role\Moderator']
					, 'read' => 1
				]
				, '$body'	=> [
					'write'  => [1, 'SeanMorris\Access\Role\User']
					, 'read' => 1
				]
				, '$state'	=> [
					'write'  =>'SeanMorris\Access\Role\Moderator'
					, 'read' => 'SeanMorris\Access\Role\Moderator'
				]
				, '$class'   => FALSE
			]
		]
		, $transitions	= [
			0 => [
				1 => 'SeanMorris\Access\Role\Moderator'
			]
			, 1 => [
				0 => 'SeanMorris\Access\Role\Moderator'
			]
		];
}