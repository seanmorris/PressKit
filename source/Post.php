<?php
namespace SeanMorris\PressKit;
class Post extends \SeanMorris\PressKit\Model
{
	protected
		$publicId
		, $title
		, $body
		, $written
		, $edited
		, $weight
		, $author
		, $category
		, $summary
		, $slugsize
		, $ctaLink
		, $ctaLinkText
		, $state
		, $images
		, $comments
		//, $comments2
	;

	protected static 
		$table = 'PressKitPost'
		, $createColumns = [
			'publicId' => 'UNHEX(REPLACE(UUID(), "-", ""))'
			, 'written' => 'UNIX_TIMESTAMP()'
		]
		, $readColumns = [
			'publicId' => 'HEX(%s)'
		]
		, $updateColumns = [
			'publicId' => 'UNHEX(%s)'
			, 'edited' => 'UNIX_TIMESTAMP()'
		]
		, $hasOne = [
			'author' => 'SeanMorris\Access\User'
			, 'state' => 'SeanMorris\PressKit\State\PostState'
		]
		, $hasMany = [
			'comments' => 'SeanMorris\PressKit\Comment'
			//, 'comments2' => 'SeanMorris\PressKit\Comment'
			, 'images' => 'SeanMorris\PressKit\Image'
		]
		, $byPublicId = [
			'where' => [['publicId' => 'UNHEX(?)']]
		]
		, $bySearch = [
			'named' => TRUE
			, 'distinct' => TRUE
			, 'where' => [
				'OR' => [
					['title' => '?', 'LIKE', '%%%s%%', 'keyword', FALSE]
					, ['body' => '?', 'LIKE', '%%%s%%', 'keyword', FALSE]
				]
			]
			, 'join' => [
				'SeanMorris\PressKit\State' => [
					'on' => 'state'
					, 'by' => 'stateNamed'
					, 'type' => 'INNER'
				]
			]
		]
		, $byModerated = [
			'join' => [
				'SeanMorris\PressKit\State' => [
					'on' => 'state'
					, 'by' => 'moderated'
					, 'type' => 'INNER'
				]
			]
		]
	;
}