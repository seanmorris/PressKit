<?php
namespace SeanMorris\PressKit;
class Model extends \SeanMorris\Ids\Model
{
	protected static function beforeConsume($instance, &$skeleton)
	{
		foreach($instance::$hasOne as $column => $class)
		{
			if($column == 'state' && !$instance->state)
			{
				var_dump($column, $class);

				$state = new $class;
				$state->consume([
					'state' => 0
					, 'owner' => 0
				]);

				$state->save();

				var_dump($state);

				$instance->{$column} = $state->id;
			}
		}
	}
}