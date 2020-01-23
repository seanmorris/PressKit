<?php
namespace SeanMorris\PressKit;
class Cache extends \SeanMorris\PressKit\Model
{
	protected
		$name
		, $bucket
		, $expiry
		, $content;
	protected static
		$table = 'Cache'
		, $byName = [
			'where' =>  ['AND' => [
				['name' => '?']
			]]
		];

	protected static function beforeWrite($instance, &$skeleton)
	{
		$skeleton['content'] = serialize($instance->content);
	}

	protected static function afterRead($instance)
	{
		if($instance->expiry > 0 && $instance->expiry <= time())
		{
			$instance->delete(TRUE);

			return FALSE;
		}

		$instance->content = unserialize($instance->content);
	}
}