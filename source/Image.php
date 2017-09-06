<?php
namespace SeanMorris\PressKit;
class Image extends \SeanMorris\PressKit\Model
{
	protected
		$id
		, $publicId
		, $created
		, $updated
		, $class
		, $title
		, $url
		, $state
	;

	protected static
		$table = 'PressKitImage'
		, $createColumns		= [
			'publicId'			=> 'UNHEX(REPLACE(UUID(), "-", ""))'
			, 'created' 		=> 'UNIX_TIMESTAMP()'
			, 'updated' 		=> '0'
		]
		, $readColumns			= [
			'publicId'			=> 'HEX(%s)'
		]
		, $updateColumns		= [
			'publicId'			=> 'UNHEX(%s)'
			, 'updated'         => 'UNIX_TIMESTAMP()'
		]
		, $hasOne				= [
			'state'				=> 'SeanMorris\PressKit\State\ImageState'
		]
		, $byNull = ['with' => ['state' => 'byNull']]
		, $byId = [
			'where' => [['id' => '?']]
			, 'with' => ['state' => 'byNull']
		]
		, $byUrl = [
			'where' => [['url' => '?']]
			, 'with' => ['state' => 'byNull']
		]
		, $byPublicId = [
			'where' => [['publicId' => 'UNHEX(?)']]
			, 'with' => ['state' => 'byNull']
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
			, 'with' => ['state' => 'byNull']
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
			, 'with' => ['state' => 'byNull']
		]
	;

	protected static function beforeConsume($instance, &$skeleton)
	{
		$tmpFile = NULL;

		if(isset($skeleton['image']))
		{
			$tmpFile = $skeleton['image'];
		
			if($tmpFile && !($tmpFile instanceof \SeanMorris\Ids\Disk\File))
			{
				return FALSE;
			}
		}		

		if(!$tmpFile)
		{
			return;
		}

		return $instance->store($tmpFile);	
	}

	protected function store($tmpFile)
	{
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

		$publicDir = \SeanMorris\Ids\Settings::read('public');

		$newName = sprintf(
			'/Static/Dynamic/%s.%s.%s'
			, $this->publicId ?? uniqid()
			, microtime(TRUE)
			, $m[1]
		);

		$newUrl = $newName;
		$newFileName = $publicDir . $newName;

		$newFile = $tmpFile->copy($newFileName);

		if(!$newFile->check())
		{
			\SeanMorris\Ids\Log::error('Failed to copy.', $newFileName);
			return FALSE;
		}

		$this->url = $newUrl;
	}

	protected function remove()
	{

	}
}
