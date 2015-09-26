<?php
namespace SeanMorris\PressKit;
class Comment extends \SeanMorris\Ids\Model
{
	protected
		$id
		, $publicId
		, $title
		, $body
		, $written
		, $author
		, $state
	;

	protected static 
		$table = 'PressKitComment'
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
			, 'author' => 'SeanMorris\Access\User'
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
					, ['id' => '?', '=', '%s', 'id', FALSE]
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