<?php
namespace SeanMorris\PressKit;
class MigrationRecord extends \SeanMorris\Ids\Model
{
	protected static $table = 'PressKitMigration';
	protected
		$migration
		, $applied
		, $package
		, $version;
}
