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
		, $_content
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
		, $byNull = [
			'with'    => ['state' => 'byNull']
			, 'order' => ['id' => 'ASC']
		]
		, $byFullSized = [
			'where' => [['crop' => 'NULL', 'IS']]
			, 'order' => ['id' => 'ASC']
			, 'with'    => ['state' => 'byNull']
		]
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
		, $crops = [
			'thumbnail' => [38, 38]
			, 'preview' => [122, 82]
			/*
			, 'cga'     => [320, 200]
			, 'vga'     => [680, 480]
			, 'hd'      => [1920, 1080]
			*/
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

	protected function content()
	{
		if($this->_content)
		{
			return $this->_content;
		}

		$publicDir = \SeanMorris\Ids\Settings::read('public');

		$filename = $publicDir . $image->url;

		return file_get_contents($filename);
	}

	protected function scaled($width, $height)
	{
		if(!$image = $this->content())
		{
			return;
		}

		try
		{
			list($originalWidth, $originalHeight,)
				 = $info
				 = getimagesizefromstring($image);

			$imageData = imagecreatefromstring($image);
		}
		catch (\Exception $e)
		{
			\SeanMorris\Ids\Log::warn('Cannot scale image', $image);
			return;
		}

		$resizedImageData = imagecreatetruecolor($width, $height);

		$originalRatio = $originalWidth/$originalHeight;
		$newRatio = $width/$height;

		$widthRatio = $originalWidth / $width;
		$heightRatio = $originalHeight / $height;
		
		if($originalWidth > $originalHeight)
		{
			if($width < $height)
			{
				$sampleWidth = $originalWidth;
				$sampleHeight = $originalWidth / $newRatio;
				$sampleLeft = 0;
				$sampleTop = ($originalHeight / 2) - ($sampleHeight / 2);
			}
			else
			{
				$sampleWidth = $originalHeight * $newRatio;
				$sampleHeight = $originalHeight;
				$sampleLeft = ($originalWidth / 2) - ($sampleWidth / 2);
				$sampleTop = 0;
			}
		}
		else
		{
			if($width > $height)
			{
				$sampleWidth = $originalWidth;
				$sampleHeight = $originalWidth / $newRatio;
				$sampleLeft = 0;
				$sampleTop = ($originalHeight / 2) - ($sampleHeight / 2);
			}
			else
			{
				$sampleWidth = $originalHeight * $newRatio;
				$sampleHeight = $originalHeight;
				$sampleLeft = ($originalWidth / 2) - ($sampleWidth / 2);
				$sampleTop = 0;
			}
		}

		imagecopyresampled(
			$resizedImageData
			, $imageData
			, 0 // dst_x
			, 0 // dst_y
			, $sampleLeft // src_x
			, $sampleTop // src_y
			, $width // dst_w
			, $height // dst_h
			, $sampleWidth // src_w
			, $sampleHeight // src_h
		);

		ob_start();
		imagejpeg($resizedImageData);
		return(ob_get_clean());
	}

	public function crop($size, $useExisting = TRUE)
	{
		$original = $this;

		if($this->original)
		{
			$original = static::getOneById($this->original);
		}

		\SeanMorris\Ids\Log::debug(sprintf(
			'Checking for existing crop "%s" for image #%d.'
			, $size
			, $original->id
		));

		if($useExisting && $existingCrop = static::loadOneByCrop($original->id, $size))
		{
			\SeanMorris\Ids\Log::debug('Existing crop found.');
			return $existingCrop;
		}

		\SeanMorris\Ids\Log::debug(sprintf(
			'Creating new crop "%s" for image %d.'
			, $size
			, $original->id
		));

		if(isset($size))
		{
			if(isset(static::$crops[ $size ]))
			{
				list($width, $height) = static::$crops[ $size ];
			}
		}
		else
		{
			return FALSE;
		}

		if(!$scaledImage = $original->scaled($width, $height))
		{
			return FALSE;
		}

		$tmpFile = new \SeanMorris\Ids\Disk\File('/tmp/' . uniqid(), $original->url);
		$tmpFile->write($scaledImage);

		if(!preg_match('/\.(gif|png|jpe?g)$/', $original->url, $m))
		{
			\SeanMorris\Ids\Log::warn('Cannot crop image without extension.', $this, $image);
			return;
		}

		$newName = sprintf(
			'%s.%s.%dx%d.%s'
			, $original->publicId
			, $original->updated
			, $width
			, $height
			, $m[1]
		);

		$crop = new static;

		$crop->consume([
			'title'      => $original->title
			, 'original' => $original->id
			, 'crop'     => $size
		], TRUE);

		$crop->store($tmpFile);

		$crop->forceSave();

		return $crop;
	}

	public function mime()
	{
		$finfo = new \finfo(FILEINFO_MIME_TYPE);

		return $finfo->buffer($this->content());
	}

	public function warmCrops()
	{
		foreach(static::$crops as $cropName => $size)
		{
			$this->crop($cropName);
		}
	}
}
