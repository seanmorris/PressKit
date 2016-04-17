<?php
namespace SeanMorris\PressKit;
class Image extends \SeanMorris\PressKit\Model
{
	protected
		$id
		, $publicId
		, $created
		, $title
		, $url
		, $state
	;

	protected static 
		$table = 'PressKitImage'
		, $createColumns		= [
			'publicId'			=> 'UNHEX(REPLACE(UUID(), "-", ""))'
			, 'created' => 'UNIX_TIMESTAMP()'
		]
		, $readColumns			= [
			'publicId'			=> 'HEX(%s)'
		]
		, $updateColumns		= [
			'publicId'			=> 'UNHEX(%s)'
		]
		, $hasOne				= [
			'state'				=> 'SeanMorris\PressKit\State\ImageState'
		]
		, $byId = [
			'where' => [['id' => '?']]
		]
		, $byPublicId = [
			'where' => [['publicId' => 'UNHEX(?)']]
		]
		, $byAll = []
		, $byModerated = [
			'join' => [
				'SeanMorris\PressKit\State' => [
					'on' => 'state'
					, 'by' => 'moderated'
					, 'type' => 'LEFT'
				]
			]
		]
		, $bySearch = [
			'named' => TRUE
			, 'distinct' => TRUE
			, 'where' => [
				'OR' => [
					['title' => '?', 'LIKE', '%%%s%%', 'keyword', FALSE]
					, ['id' => '?', '=', '%s', 'id', FALSE]
				]
			]
		]
		, $files = ['image']
	;

	protected static function beforeConsume($instance, &$skeleton)
	{
		$skeleton['url'] = $instance->url;

		//var_dump($instance, $skeleton);

		foreach(static::$files as $fileField)
		{
			if(isset($skeleton[$fileField])
				&& $skeleton[$fileField] instanceof \SeanMorris\Ids\Storage\Disk\File
			){
				$tmpFile = $skeleton[$fileField];

				$originalName = $tmpFile->originalName();

				preg_match(
					'/\.(gif|png|jpe?g)$/'
					, $originalName
					, $m
				);

				if(!$m)
				{
					return FALSE;
				}

				$microtime = explode(' ', microtime());

				$newName = sprintf(
					'%s%s.%s.%03d.%s'
					, IDS_PUBLIC_DYNAMIC
					, $microtime[1] 
					, substr($microtime[0], 2)
					, rand(0,999)
					, $m[1]
				);

				$newUrl = '/' . $newName;
				$newFileName = IDS_PUBLIC_ROOT . $newName;

				$newFile = $tmpFile->copy($newFileName);

				if(!$newFile->check())
				{
					return FALSE;
				}

				$skeleton['url'] = $newUrl;

				// var_dump($instance, $skeleton, $newFile);
			}
		}
	}

	protected static function beforeCreate($instance, &$skeleton)
	{
	}
}