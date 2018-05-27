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

	public function stateCleanUp($router)
	{
		$args  = $router->path()->consumeNodes();

		$namespace = array_shift($args);

		$namespace = preg_replace('/\//', '\\', $namespace) . '\\';

		$classes = \SeanMorris\Ids\Meta::classes('SeanMorris\Ids\Model');

		$models = array_filter($classes, function($class) use($namespace){
			if(is_a($class, 'SeanMorris\PressKit\State', TRUE))
			{
				return FALSE;
			}
			return substr($class, 0, strlen($namespace)) == $namespace;
		});

		foreach($models as $class)
		{
			if(!$stateClass = $class::canHaveOne('state'))
			{
				continue;
			}

			printf(
				'Checking models of class %s' . PHP_EOL
					. "\t" . 'Should have states of class %s' . PHP_EOL
				, $class
				, $stateClass
			);

			$generator = $class::generate();

			foreach($generator() as $model)
			{
				printf(
					"\t\t" . 'Checking model %d of class %s' . PHP_EOL
					, $model->id
					, $class
				);

				if($state = $model->getSubject('state'))
				{
					printf(
						"\t\t" . 'Model %d of class %s' . PHP_EOL
							. "\t\t" . 'has a state of class %s' . PHP_EOL
							. PHP_EOL
						, $model->id
						, $class
						, get_class($state)
					);
				}

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

	public function subclassCleanup()
	{
		$db = \SeanMorris\Ids\Database::get('main');

		$classes = \SeanMorris\Ids\Meta::classes('SeanMorris\Ids\Model');
		$heights = [];
		foreach($classes as $class)
		{
			$height = [];
			$_class = $class;

			do
			{
				$tableProp = new \ReflectionProperty($_class, 'table');

				if($_class !== $tableProp->class)
				{
					continue;
				}

				if(!$_class::table())
				{
					continue;
				}

				$height[] = $_class;
			}
			while($_class = get_parent_class($_class));

			if(count($height) < 2)
			{
				continue;
			}

			$heights[$class] = $height;
		}

		foreach($heights as $class => $chain)
		{
			for($i = count($chain)-1; $i >= 0; $i--)
			{
				if(!isset($chain[$i-1]))
				{
					continue;
				}

				$select = $db->prepare(sprintf(
					"SELECT a.id FROM `%s` a\n\tLEFT JOIN `%s` b\n\tON a.id = b.id\nWHERE b.id IS NULL\n"
					, $chain[$i-1]::table()
					, $chain[$i]::table()
				));

				$select->execute();

				while($row = $select->fetchObject())
				{
					$delete = $db->prepare($deleteString = sprintf(
						'DELETE FROM `%s` WHERE id = %d'
						, $chain[$i-1]::table()
						, $row->id
					));

					print $deleteString . PHP_EOL;

					$delete->execute();
				}
			}
		}
	}

	public function uinfo($router)
	{
		$args  = $router->path()->consumeNodes();

		$userId = array_shift($args);

		if(!$user = \SeanMorris\Access\User::loadOneSubmodelById($userId))
		{
			$user = \SeanMorris\Access\User::loadOneSubmodelByUsername($userId);
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

		if(!$user = \SeanMorris\Access\User::loadOneSubmodelById($userId))
		{
			$user = \SeanMorris\Access\User::loadOneSubmodelByUsername($userId);
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