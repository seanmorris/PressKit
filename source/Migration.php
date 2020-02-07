<?php
namespace SeanMorris\PressKit;
class Migration
{
	public static function list($all = FALSE)
	{
		$package = \SeanMorris\Ids\Package::getRoot();

		$classes = \SeanMorris\Ids\Meta::classes(static::class);

		$classes = array_filter($classes, function($class) {
			return $class !== static::class;
		});

		if(!$all)
		{
			$classes = array_filter(static::classify($classes), function($class) use($package) {

				$lastMigration = \SeanMorris\PressKit\MigrationRecord::loadOnebyPackage(
					$class->namespace
				);

				$currentVersion = -1;

				if($lastMigration)
				{
					$currentVersion = $lastMigration->version;
				}

				return $class->version > $currentVersion;
			});
		}

		usort($classes, function($a, $b) {
			return $a->version <=> $b->version;
		});

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
