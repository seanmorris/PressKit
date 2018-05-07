<?php
namespace SeanMorris\PressKit\Idilic\Route;
class RootRoute implements \SeanMorris\Ids\Routable
{
	public function importScaffold($router)
	{
		$args     = $router->path()->consumeNodes();

		$name     = array_shift($args);
		$filename = array_shift($args);

		if(!file_exists($filename))
		{
			return 'File does not exist.';
		}

		$file = fopen($filename, 'r');
		$header = [];
		$i = 0;

		while($line = fgetcsv($file))
		{
			$i++;
			set_time_limit(10);

			if(!$header || $line[0] == '*')
			{
				$header = $line;

				continue;
			}

			if(count($header) !== count($line))
			{
				\SeanMorris\Ids\log::error(
					"Line does not have the correct number of cells!"
					, $header
					, $line
				);
				continue;
			}

			$line = array_combine($header, $line);

			$addressParts = json_decode($line['addressComponents'], TRUE);

			$addressPartsNamed = [];

			if($addressParts)
			{
				foreach($addressParts as $addressPart)
				{
					$addressPartsNamed[ $addressPart['types'][0] ] = $addressPart;
				}

				if(isset($addressPartsNamed['street_number']))
				{
					$line['street_number'] = $addressPartsNamed['street_number']['long_name'];
				}

				if(isset($addressPartsNamed['route']))
				{
					$line['route'] = $addressPartsNamed['route']['long_name'];
				}

				if(isset($addressPartsNamed['locality']))
				{
					$line['city'] = $addressPartsNamed['locality']['long_name'];
				}

				if(isset($addressPartsNamed['administrative_area_level_1']))
				{
					$line['state'] = $addressPartsNamed['administrative_area_level_1']['short_name'];
				}

				if(isset($addressPartsNamed['postal_code']))
				{
					$line['postal_code'] = $addressPartsNamed['postal_code']['short_name'];
				}

				if(isset($addressPartsNamed['country']['short_name']))
				{
					$line['country'] = $addressPartsNamed['country']['short_name'];
				}
			}

			$info = [
				'name'   => $name
				, 'frag'   => FALSE
				, 'engine' => 'InnoDB'
				, 'keys'   => [
					'primary'  => ['id']
					, 'unique' => [
						'place_id' => ['place_id']
					]
					, 'index' => [
						'name' => ['name']
					]
				]
			];

			$model = \SeanMorris\PressKit\Scaffold::produceScaffold($info);

			foreach($line as $k => $v)
			{
				$model->{$k} = $v;
			}

			try
			{
				$model->save();
			}
			catch(\Exception $exception)
			{
				if($exception->getCode() != 1062)
				{
					throw $exception;
				}
			}
		}

		return $name;
	}

	public function stateCleanUp()
	{
		$models = [
			'SeanMorris\PressKit\Post'
			, 'SeanMorris\Access\User'
		];

		foreach($models as $class)
		{
			$generator = $class::generateByNull();

			foreach($generator() as $model)
			{
				if(!$stateClass = $model->canHaveOne('state'))
				{
					continue;
				}

				$state = $model->getSubject('state');

				if(!$state || !$state instanceof $stateClass)
				{
					$stateSkeleton = [
						'state' => 0
						, 'owner' => 0
					];

					if($state)
					{
						$stateSkeleton = $state->unconsume();

						unset($stateSkeleton['id']);
					}

					$state = new $stateClass;

					$state->consume($stateSkeleton);

					$state->save();

					$model->consume(['state' => $state->id], TRUE);

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

	public function relationshipCleanup()
	{
		$relLoader = \SeanMorris\Ids\Relationship::loadByNull();
		$i = 0;

		foreach($relLoader() as $rel)
		{
			if(!$rel->ownerObject || !$rel->subjectObject)
			{
				$rel->delete();
				$i++;
			}
		}

		var_dump($i);
	}

	public function uinfo($router)
	{
		$args  = $router->path()->consumeNodes();

		$userId = array_shift($args);

		if(!$user = \SeanMorris\Access\User::loadOneById($userId))
		{
			$user = \SeanMorris\Access\User::loadOneByUsername($userId);
		}

		if(!$user)
		{
			printf('User "%s" not found.', $userId);
			return;
		}

		$view = new \SeanMorris\PressKit\Idilic\View\UserInfo([
			'user' => $user
		]);

		print $view;
	}

	public function upwd($router)
	{
		$args  = $router->path()->consumeNodes();

		$userId   = array_shift($args);
		$password = array_shift($args);

		if(!$user = \SeanMorris\Access\User::loadOneById($userId))
		{
			$user = \SeanMorris\Access\User::loadOneByUsername($userId);
		}

		$user->consume(['password' => $password], TRUE);
		$user->save();

		if(!$user)
		{
			printf('User "%s" not found.', $userId);
			return;
		}

		$view = new \SeanMorris\PressKit\Idilic\View\UserInfo([
			'user' => $user
		]);

		print $view;
	}

	public function urol($router)
	{
		$args  = $router->path()->consumeNodes();

		$userId = array_shift($args);

		while($role = array_shift($args))
		{
			var_dump($role);
		}
	}
}