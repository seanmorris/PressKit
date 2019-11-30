<?php
namespace SeanMorris\PressKit;
class Package extends \SeanMorris\Ids\Package
{
	protected static
		$assetManager = 'SeanMorris\Rhino\AssetManager'
		, $tables = [
			'main' => [
				'Cache'
				, 'PressKitPost'
				, 'PressKitImage'
				, 'PressKitComment'
				, 'PressKitMigration'
				, 'PressKitRelationship'
				, 'StateFlowState'
			]
		]
	;
}
