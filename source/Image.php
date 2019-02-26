<?php
namespace SeanMorris\PressKit;
class Image extends \SeanMorris\PressKit\Model
{
	const JPEG_QUALITY = -1;
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
		, $byOriginal = [
			'where' => [
				['original' => '?']
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

		if(array_key_exists('url', $skeleton) && !$skeleton['url'] && $instance->url)
		{
			$skeleton['url'] = $instance->url;
		}

		if(isset($skeleton['image']))
		{
			$tmpFile = $skeleton['image'];

			if(isset($skeleton['image'])
				&& $skeleton['image']
				&& is_string($skeleton['image'])
				&& !is_numeric($skeleton['image'])
			){
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

	protected static function afterDelete($instance)
	{
		$publicDir = \SeanMorris\Ids\Settings::read('public');
		$filename  = $publicDir . $instance->url;
		$file      = new \SeanMorris\Ids\Disk\File($filename);
		if($file->check())
		{
			$file->delete();
		}

		if($instance->original)
		{
			return;
		}

		$crops = static::getByOriginal($instance);

		foreach($crops as $crop)
		{
			$crop->delete();
		}
	}

	protected function store($tmpFile)
	{
		$originalName = is_string($tmpFile)
			? $tmpFile
			: $tmpFile->originalName();

		if(is_string($tmpFile))
		{
			$tmpFile = new \SeanMorris\Ids\Disk\File($tmpFile);
		}

		preg_match(
			'/\.(gif|png|jpe?g|webp)$/i'
			, $originalName
			, $m
		);

		if(!$m)
		{
			\SeanMorris\Ids\Log::debug('Not an image.');
			return FALSE;
		}

		$extension = strtolower($m[1]);

		$publicDir = \SeanMorris\Ids\Settings::read('public');

		$newName = sprintf(
			'/Static/Dynamic/%s.%s.%s'
			, $this->publicId ?? uniqid()
			, microtime(TRUE)
			, $extension
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

		$imagick = new \Imagick($this->location());

		if($imagick->getImageMimeType() == 'image/jpeg')
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

		$image       = new \Imagick($this->location());
		$orientation = $image->getImageOrientation(); 


		switch ($exif['Orientation'] ?? 0)
		{
			case \Imagick::ORIENTATION_BOTTOMRIGHT:
				$image->rotateimage("#000", 180);
				// $orientedImage = imagerotate($orientedImage, 180, 0);
				break;

			case \Imagick::ORIENTATION_RIGHTTOP:
				$image->rotateimage("#000", 90);
				// $orientedImage = imagerotate($orientedImage, -90, 0);
				break;

			case \Imagick::ORIENTATION_LEFTBOTTOM:
				$image->rotateimage("#000", -90);
				// $orientedImage = imagerotate($orientedImage, 90, 0);
				break;
		}

		ob_start();
		imagejpeg($orientedImage, NULL, static::JPEG_QUALITY);
		$imageData = ob_get_contents();
		ob_end_clean();

		$file->write($imageData, false);

		\SeanMorris\Ids\Log::debug('Oriented', $this);
	}

	public function scaled($width, $height)
	{
		preg_match(
			'/\.(gif|png|jpe?g|webp)$/'
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
			// list($originalWidth, $originalHeight,)
			// 	 = $info
			// 	 = getimagesizefromstring($image);

			// $imageData = imagecreatefromstring($image);
			$imagick = new \Imagick($this->location());

			$imageGeometry  = $imagick->getImageGeometry();

			$originalWidth  = $imageGeometry['width']; 
			$originalHeight = $imageGeometry['height']; 

			$originalRatio  = $originalWidth/$originalHeight;

			$newRatio = $width/$height;

			$widthRatio = $originalWidth / $width;
			$heightRatio = $originalHeight / $height;
			
			if($originalRatio > $newRatio)
			{
				$sampleHeight = $height;
				$sampleWidth  = $height * $newRatio;

				$sampleTop    = 0;
				$sampleLeft   = ($width / 2) - ($sampleWidth / 2);
			}
			else
			{
				$sampleHeight = $width / $newRatio;
				$sampleWidth  = $width;

				$sampleTop    = ($height / 2) - ($sampleHeight / 2);
				$sampleLeft   = 0;
			}

			if($width > $originalWidth || $height > $originalHeight)
			{
				$imagick->cropThumbnailImage($width, $height);
			}
			else
			{
				$imagick->cropImage($width, $height, $sampleLeft, $sampleTop);
			}

			$imagick->setImageFormat('jpeg');

			return $imagick->getImageBlob();
		}
		catch (\Exception $e)
		{
			\SeanMorris\Ids\Log::warn('Cannot scale image', $this);
			\SeanMorris\Ids\Log::logException($e);

			return;
		}

		// $resizedImageData = imagecreatetruecolor($width, $height);

		// $originalRatio = $originalWidth/$originalHeight;
		// $newRatio = $width/$height;

		// $widthRatio = $originalWidth / $width;
		// $heightRatio = $originalHeight / $height;
		
		// if($originalRatio > $newRatio)
		// {
		// 	$sampleHeight = $originalHeight;
		// 	$sampleWidth  = $originalHeight * $newRatio;

		// 	$sampleTop    = 0;
		// 	$sampleLeft   = ($originalWidth / 2) - ($sampleWidth / 2);
		// }
		// else
		// {
		// 	$sampleHeight = $originalWidth / $newRatio;
		// 	$sampleWidth  = $originalWidth;

		// 	$sampleTop    = ($originalHeight / 2) - ($sampleHeight / 2);
		// 	$sampleLeft   = 0;
		// }

		// imagecopyresampled(
		// 	$resizedImageData
		// 	, $imageData
		// 	, 0 // dst_x
		// 	, 0 // dst_y
		// 	, $sampleLeft // src_x
		// 	, $sampleTop // src_y
		// 	, $width // dst_w
		// 	, $height // dst_h
		// 	, $sampleWidth // src_w
		// 	, $sampleHeight // src_h
		// );

		// ob_start();
		// imagejpeg($resizedImageData);
		// return(ob_get_clean());
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

		if(!preg_match('/\.(gif|png|jpe?g|webp)$/i', $original->url, $m))
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
				return $existingFit;
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
			// list($originalWidth, $originalHeight,)
			// 	 = $info
			// 	 = getimagesizefromstring($image);

			$imagick = new \Imagick($this->location());

			$imagick->scaleImage($width, $height, TRUE);

			$imagick->setImageFormat('jpeg');

			$scaledImage = $imagick->getImageBlob();

		}
		catch (\Exception $e)
		{
			\SeanMorris\Ids\Log::warn('Cannot scale to fit image', $this);
			return;
		}

		// if($originalHeight == 0)
		// {
		// 	return $this;
		// }

		// $ratio = $originalWidth / $originalHeight;

		// if($originalWidth > $originalHeight)
		// {
		// 	$width = $width * $ratio;
		// }
		// else
		// {
		// 	$height = $height * (1/$ratio);
		// }

		// if(!$scaledImage = $original->scaled($width, $height))
		// {
		// 	return FALSE;
		// }

		$tmpFile = new \SeanMorris\Ids\Disk\File('/tmp/' . uniqid(), $original->url);
		$tmpFile->write($scaledImage);

		if(!preg_match('/\.(gif|png|jpe?g|webp)$/', $original->url, $m))
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

	public function stub()
	{
		$stub        = parent::stub();
		$stub->url_s = $this->url;

		return $stub;
	}

	protected static function instantiateStub($skeleton)
	{
		$stub = parent::instantiateStub($skeleton);

		if(is_string($skeleton))
		{
			if($skeletonObject = json_decode($skeleton))
			{
				// var_dump($skeletonObject);
				$skeleton  = $skeletonObject;
			}
			else
			{
				return parent::instantiateStub((object)[
					'url' => $skeleton
				]);
			}
		}

		$stub->url = $skeleton->url_s ?? NULL;

		return $stub;
	}

	protected static function beforeWrite($instance, &$skeleton)
	{
		if(
			!($instance->url)
			 && !($skeleton['image'] ?? FALSE)
			 && !($skeleton['image'] ?? FALSE)
		){
			return FALSE;
		}
	}
}
