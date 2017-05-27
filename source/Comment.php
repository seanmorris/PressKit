<?php
namespace SeanMorris\PressKit;
class Comment extends \SeanMorris\PressKit\Model
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
			'state' => 'SeanMorris\PressKit\State\CommentState'
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
		, $byNull = ['order' => ['id' => 'DESC']]
		, $byState = [
			'named' => TRUE
			, 'join' => [
				'SeanMorris\PressKit\State' => [
					'on' => 'state'
					, 'by' => 'stateNamed'
					, 'type' => 'INNER'
				]
			]
		]
	;

	protected static function beforeCreate($instance, &$skeleton)
	{
		$user = \SeanMorris\Access\Route\AccessRoute::_currentUser();

		if($user)
		{
			$skeleton['author'] = $user->id;
		}
		else
		{
			return false;
		}
	}
}