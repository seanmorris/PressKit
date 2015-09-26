<?php
namespace SeanMorris\PressKit;
class Post extends \SeanMorris\Ids\Model
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
		, $comments2
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
			'state' => 'SeanMorris\PressKit\State'
		]
		, $hasMany = [
			'comments' => 'SeanMorris\PressKit\Comment'
			, 'comments2' => 'SeanMorris\PressKit\Comment'
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
					['title' => '?', 'LIKE', '%%%s%%', 'test_title', FALSE]
					, ['body' => '?', 'LIKE', '%%%s%%', 'test_title2']
				]
			]
			, 'join' => [
				'SeanMorris\PressKit\State' => [
					'on' => 'state'
					, 'by' => 'moderated'
					, 'type' => 'INNER'
				]
			]
		]
		, $byModerated = [
			'join' => [
				'SeanMorris\PressKit\State' => [
					'on' => 'state'
					, 'by' => 'moderated'
					, 'type' => 'LEFT'
				]
			]
		]
	;
}