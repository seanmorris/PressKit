<?php
namespace SeanMorris\PressKit;
class Model extends \SeanMorris\Ids\Model
{
	/*
	protected static function beforeCreate($instance, &$skeleton)
	{
		foreach($instance::$hasOne as $column => $class)
		{
			if($column == 'state' && !$instance->state)
			{
				$owner = 0;

				if(isset($skeleton['state']['owner']))
				{
					$owner = $skeleton['state']['owner'];
				}

				$state = new $class;
				$state->consume([
					'state' => 0
					, 'owner' => 0
				]);

				$state->save();

				$instance->{$column} = $state->id;
			}
		}
	}
	*/
}