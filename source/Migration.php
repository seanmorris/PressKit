<?php
namespace SeanMorris\PressKit;
class Migration
{
	public static function list($all = FALSE)
	{
		$package = \SeanMorris\Ids\Package::getRoot();
		$current = $package->getVar('migration');
		$classes = \SeanMorris\Ids\Meta::classes(static::class);

		$classes = array_filter($classes, function($class) {
			return $class !== static::class;
		});

		if($current === NULL)
		{
			$package->setVar('migration', (object)[]);
		}

		$current = $package->getVar('migration');

		if(!$all)
		{
			return array_filter(
				static::classify($classes)
				, function($class) use($current, $package)
				{
					$currentPackage = $package->getVar('migration:' . $class->namespace);

					if($currentPackage === NULL)
					{
						$package->setVar('migration:' . $class->namespace, -1);
					}

					$currentPackage = $package->getVar('migration:' . $class->namespace);

					return $class->version > $currentPackage;
				}
			);
		}

		return $classes;
	}

	protected static function classify($classes)
	{
		return array_map(
			function($class)
			{
				$splitSpace = explode('\\', $class);
				$splitClass = explode('_', $class);

				$namespace  = implode('\\', array_slice($splitSpace, 0 , -1));
				$migration  = current($splitClass);
				$versionNo  = end($splitClass);
				$short      = end($splitSpace);

				return (object)[
					'namespace' => $namespace
					, 'version' => $versionNo
					, 'short'   => $short
					, 'class'   => $class
				];
			}
			, $classes
		);
	}

	public static function apply()
	{

	}
}