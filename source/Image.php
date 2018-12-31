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
		, $original
		, $crop
		, $fit
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
			'order' => ['id' => 'ASC']
			, 'with'  => ['state' => 'byNull']
		]
		, $byFullSized = [
			'where'   => [
				['crop' => 'NULL', 'IS']
				, ['fit'  => 'NULL', 'IS']
			]
			, 'order' => ['id' => 'ASC']
			, 'with'  => ['state' => 'byNull']
		]
		, $byId = [
			'where'   => [['id' => '?']]
			, 'order' => ['id' => 'ASC']
			, 'with'  => ['state' => 'byNull']
		]
		, $byUrl = [
			'where'  => [['url' => '?']]
			, 'with' => ['state' => 'byNull']
		]
		, $byPublicId = [
			'where'  => [['publicId' => 'UNHEX(?)']]
			, 'with' => ['state' => 'byNull']
		]
		, $byCrop = [
			'where' => [
				['original' => '?']
				, ['crop'   => '?']
				// , ['deleted' => '1', '!=',]
			]
			, 'with' => ['state' => 'byNull']
		]
		, $byCropsAndIds = [
			'where'   => [
				['original' => '?', 'IN', '%s', 'id', FALSE]
				, ['crop'   => '?', 'IN', '%s', 'crop', FALSE]
				// , ['deleted' => '1', '!=',]
			]
			, 'with' => ['state' => 'byNull']
		]
		, $byFit = [
			'where' => [
				['original' => '?']
				, ['fit'    => '?']
				// , ['deleted' => '1', '!=',]
			]
			, 'with' => ['state' => 'byNull']
		]
		, $byFitsAndIds = [
			'where'   => [
				['original' => '?', 'IN', '%s', 'id', FALSE]
				, ['fit'    => '?', 'IN', '%s', 'fit', FALSE]
				// , ['deleted' => '1', '!=',]
			]
			, 'with' => ['state' => 'byNull']
		]
		, $byAll = []
		, $byModerated = [
			'with' => ['state' => 'byNull']
			// , 'join' => [
			// 	'SeanMorris\PressKit\State' => [
			// 		'on' => 'state'
			// 		, 'by' => 'moderated'
			// 		, 'type' => 'LEFT'
			// 	]
			// ]
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
			'cga'     => [320, 200]
			, 'vga'     => [680, 480]
			, 'hd'      => [1920, 1080]
		]
	;

	protected static function beforeConsume($instance, &$skeleton)
	{
		$tmpFile = NULL;

		if(isset($skeleton['image']))
		{
			$tmpFile = $skeleton['image'];

			if(is_string($skeleton['image']) && !is_numeric($skeleton['image']))
			{
				$instance->url = $skeleton['url'] = $skeleton['image'];
			}
			else if($tmpFile && !($tmpFile instanceof \SeanMorris\Ids\Disk\File))
			{
				return FALSE;
			}
		}		

		if(!$tmpFile)
		{
			return;
		}

		$instance->store($tmpFile);
	}

	protected function store($tmpFile)
	{
		$originalName = is_string($tmpFile)
			? $tmpFile
			: $tmpFile->originalName();

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

		if($m[1] === 'jpg' || $m[1] === 'jpeg')
		{
			$this->orient();
		}
	}

	protected function remove()
	{

	}

	public function location()
	{
		if($this->_content)
		{
			return $this->_content;
		}

		$publicDir = \SeanMorris\Ids\Settings::read('public');

		return $publicDir . $this->url;
	}

	public function content()
	{
		if(!file_exists($this->location()))
		{
			return FALSE;
		}
		return file_get_contents($this->location());
	}


	protected function orient()
	{
		\SeanMorris\Ids\Log::debug('Orienting...', $this);

		if(!$image = $this->content())
		{
			return;
		}

		try
		{
			list($originalWidth, $originalHeight,)
				 = $info
				 = getimagesizefromstring($image);

			$imageOriginal = imagecreatefromstring($image);
		}
		catch (\Exception $e)
		{
			\SeanMorris\Ids\Log::warn('Cannot orient image', $this);
			\SeanMorris\Ids\Log::logException($e);
			return;
		}

		$file = new \SeanMorris\Ids\Disk\File(
			$this->location()
		);

		$orientedImage = imagecreatetruecolor(
			$originalWidth
			, $originalHeight
		);

		imagecopyresampled(
			$orientedImage
			, $imageOriginal
			, 0, 0, 0, 0
			, $originalWidth, $originalHeight
			, $originalWidth, $originalHeight
		);

		$exif = exif_read_data($this->location());

		switch ($exif['Orientation'] ?? 0)
		{
			case 3:
				$orientedImage = imagerotate($orientedImage, 180, 0);
				break;

			case 6:
				$orientedImage = imagerotate($orientedImage, -90, 0);
				break;

			case 8:
				$orientedImage = imagerotate($orientedImage, 90, 0);
				break;
		}

		ob_start();
		imagejpeg($orientedImage);
		$imageData = ob_get_contents();
		ob_end_clean();

		$file->write($imageData, false);

		\SeanMorris\Ids\Log::debug('Oriented', $this);
	}

	public function scaled($width, $height)
	{
		preg_match(
			'/\.(gif|png|jpe?g)$/'
			, $this->url
			, $m
		);

		if(!$m)
		{
			\SeanMorris\Ids\Log::warn('Not a scalable image.');
			return FALSE;
		}

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
			\SeanMorris\Ids\Log::warn('Cannot scale image', $this);
			return;
		}

		$resizedImageData = imagecreatetruecolor($width, $height);

		$originalRatio = $originalWidth/$originalHeight;
		$newRatio = $width/$height;

		$widthRatio = $originalWidth / $width;
		$heightRatio = $originalHeight / $height;
		
		if($originalRatio > $newRatio)
		{
			$sampleHeight = $originalHeight;
			$sampleWidth  = $originalHeight * $newRatio;

			$sampleTop    = 0;
			$sampleLeft   = ($originalWidth / 2) - ($sampleWidth / 2);
		}
		else
		{
			$sampleHeight = $originalWidth / $newRatio;
			$sampleWidth  = $originalWidth;

			$sampleTop    = ($originalHeight / 2) - ($sampleHeight / 2);
			$sampleLeft   = 0;
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

		while($this->original)
		{
			if(!$original = static::getOneById($this->original))
			{
				return;
			}
		}

		if(1 || $useExisting)
		{
			\SeanMorris\Ids\Log::debug(sprintf(
				'Checking for existing crop "%s" for image #%d.'
				, $size
				, $original->id
			));

			if($existingCrop = static::loadOneByCrop($original->id, $size))
			{
				\SeanMorris\Ids\Log::debug('Existing crop found.');
				return $existingCrop;
			}
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
			else
			{
				return FALSE;
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
			\SeanMorris\Ids\Log::warn('Cannot crop image without extension.', $original);
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

	public function fit($size, $useExisting = TRUE)
	{
		$original = $this;

		while($this->original)
		{
			if(!$original = static::getOneById($this->original))
			{
				return;
			}
		}

		if(1 || $useExisting)
		{
			\SeanMorris\Ids\Log::debug(sprintf(
				'Checking for existing FIT "%s" for image #%d.'
				, $size
				, $original->id
			));

			if($existingFit = static::loadOneByFit($original->id, $size))
			{
				\SeanMorris\Ids\Log::debug('Existing fit found.');
				return $existingCrop;
			}
		}

		if(isset($size))
		{
			if(isset(static::$crops[ $size ]))
			{
				list($width, $height) = static::$crops[ $size ];
			}
			else
			{
				return FALSE;
			}
		}
		else
		{
			return FALSE;
		}

		if(!$image = $this->content())
		{
			return;
		}

		try
		{
			list($originalWidth, $originalHeight,)
				 = $info
				 = getimagesizefromstring($image);
		}
		catch (\Exception $e)
		{
			\SeanMorris\Ids\Log::warn('Cannot scale image', $this);
			return;
		}

		$ratio = $originalWidth / $originalHeight;

		if($originalWidth > $originalHeight)
		{
			$width = $width * $ratio;
		}
		else
		{
			$height = $height * (1/$ratio);
		}

		if(!$scaledImage = $original->scaled($width, $height))
		{
			return FALSE;
		}

		$tmpFile = new \SeanMorris\Ids\Disk\File('/tmp/' . uniqid(), $original->url);
		$tmpFile->write($scaledImage);

		if(!preg_match('/\.(gif|png|jpe?g)$/', $original->url, $m))
		{
			\SeanMorris\Ids\Log::warn('Cannot crop image without extension.', $original);
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
			, 'fit'      => $size
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

	public function warmCrops($useExisting = TRUE)
	{
		foreach(static::$crops as $cropName => $size)
		{
			$this->crop($cropName, $useExisting);
		}
	}

	public static function getCrops()
	{
		return static::$crops;
	}
}
