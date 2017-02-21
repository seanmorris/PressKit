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
			, 'created' 		=> 'UNIX_TIMESTAMP()'
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
		//$skeleton['url'] = $instance->url;

		$request = new \SeanMorris\Ids\Request;
		foreach($request->files() as $fileField => $tmpFile)
		{
			if(!array_key_exists($fileField, $skeleton))
			{
				continue;
			}

			if(!($tmpFile instanceof \SeanMorris\Ids\Disk\File))
			{
				continue;
			}

			$originalName = $tmpFile->originalName();

			preg_match(
				'/\.(gif|png|jpe?g)$/'
				, $originalName
				, $m
			);

			if(!$m)
			{
				\SeanMorris\Ids\Log::debug('Not an image.');
				return FALSE;
			}

			$microtime = explode(' ', microtime());

			$publicDir = \SeanMorris\Ids\Settings::read('public');

			$newName = sprintf(
				'/Static/Dynamic/%s.%s.%03d.%s'
				, $microtime[1]
				, substr($microtime[0], 2)
				, rand(0,999)
				, $m[1]
			);

			$newUrl = $newName;
			$newFileName = $publicDir . $newName;

			$newFile = $tmpFile->copy($newFileName);

			var_dump($fileField, $tmpFile, $newFileName);

			if(!$newFile->check())
			{
				\SeanMorris\Ids\Log::error('Failed to copy.', $newFileName);
				return FALSE;
			}

			$skeleton['url'] = $newUrl;
		}
	}

	protected static function beforeCreate($instance, &$skeleton)
	{
	}
}
