<?php
namespace SeanMorris\PressKit\Idilic\Route;
class RootRoute implements \SeanMorris\Ids\Routable
{
	public function stateCleanUp()
	{
		$models = [
			'SeanMorris\PressKit\Post'
			, 'SeanMorris\PressKit\Image'
			, 'SeanMorris\PressKit\Comment'
		];

		foreach($models as $class)
		{
			$generator = $class::generateByNull();

			foreach($generator() as $model)
			{
				$state = $model->getSubject('state');

				if(!$state)
				{
					$state = new \SeanMorris\PressKit\State;
					$state->consume([
						'state' => 0
						, 'owner' => 0
					]);

					$state->save();

					$model->consume([
						'state' => $state->id
					]);

					$model->save();

					printf(
						'Generated state for %s #%d'
						, $class
						, $model->id
					);
					print PHP_EOL;
				}
			}
		}
	}
} 