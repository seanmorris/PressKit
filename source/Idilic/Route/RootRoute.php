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
						, 'owner' => 'c3bcc29cc29908c2aec2916b37c3a732c3a763c3'
					]);

					$state->save();

					$model->consume([
						'state' => $state->id
					]);

					$model->save();

					printf(
						'Generated state for %s #%d'
						, $classa
						, $model->id
					);
					print PHP_EOL;
				}
			}
		}
	}
} 