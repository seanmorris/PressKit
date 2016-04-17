<?php
namespace SeanMorris\PressKit\State;
class PostState extends \SeanMorris\PressKit\State
{
	protected static
		$states	= [
			0 => [
				'create'	=> 'SeanMorris\Access\Role\Administrator'
				, 'read'	 => 0
				, 'update'	 => [1, 'SeanMorris\Access\Role\Administrator']
				, 'delete'	 => ['SeanMorris\Access\Role\Administrator', 'SeanMorris\Access\Role\Administrator']

				, '$title'	=> [
					'write'  => [1, 'SeanMorris\Access\Role\Administrator']
					, 'read' => 1
				]
				, '$body'	=> [
					'write'  => [1, 'SeanMorris\Access\Role\Administrator']
					, 'read' => 1
				]
				, '$summary'	=> [
					'write'  => [1, 'SeanMorris\Access\Role\Administrator']
					, 'read' => 1
				]
				, '$weight'	=> [
					'write'  => [1, 'SeanMorris\Access\Role\Administrator']
					, 'read' => 1
				]
				, '$slugSize'	=> [
					'write'  => [1, 'SeanMorris\Access\Role\Administrator']
					, 'read' => 
1				]
				, '$ctaLink'	=> [
					'write'  => [1, 'SeanMorris\Access\Role\Administrator']
					, 'read' => 1
				]
				, '$ctaLinkText'	=> [
					'write'  => [1, 'SeanMorris\Access\Role\Administrator']
					, 'read' => 1
				]
				, '$images'	=> [
					'write'  => [1, 'SeanMorris\Access\Role\Administrator']
					, 'read' => 1
				]
			]
			, 1 => [
				'create'	=> 'SeanMorris\Access\Role\Administrator'
				, 'read'	 => 1
				, 'update'	 => [1, 'SeanMorris\Access\Role\Administrator']
				, 'delete'	 => ['SeanMorris\Access\Role\Administrator', 'SeanMorris\Access\Role\Administrator']
				
				, '$title'	=> [
					'write'  => [1, 'SeanMorris\Access\Role\Administrator']
					, 'read' => 1
				]
				, '$body'	=> [
					'write'  => [1, 'SeanMorris\Access\Role\Administrator']
					, 'read' => 1
				]
				, '$summary'	=> [
					'write'  => [1, 'SeanMorris\Access\Role\Administrator']
					, 'read' => 1
				]
				, '$weight'	=> [
					'write'  => [1, 'SeanMorris\Access\Role\Administrator']
					, 'read' => 1
				]
				, '$slugsize'	=> [
					'write'  => [1, 'SeanMorris\Access\Role\Administrator']
					, 'read' => 1
				]
				, '$ctaLink'	=> [
					'write'  => [1, 'SeanMorris\Access\Role\Administrator']
					, 'read' => 1
				]
				, '$ctaLinkText'	=> [
					'write'  => [1, 'SeanMorris\Access\Role\Administrator']
					, 'read' => 1
				]
				, '$images'	=> [
					'write'  => [1, 'SeanMorris\Access\Role\Administrator']
					, 'read' => 1
				]
				, '$comments'	=> [
					'add' => 'SeanMorris\Access\Role\User'
					, 'read' => 1
				]
			]
		]
		/*
		, $transitions	= [
			0 => [
				1 => 32
			]
			, 1 => [
				0 => -1
			]
		]
		*/;
}