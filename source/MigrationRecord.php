<?php
namespace SeanMorris\PressKit;
class MigrationRecord extends \SeanMorris\Ids\Model
{
	protected static
	$table       = 'PressKitMigration'
	, $byPackage = [
		'order'   =>  ['id' => 'desc']
		, 'where' =>  [['package' => '?']]
	];

	protected
		$migration
		, $applied
		, $package
		, $version;
}
