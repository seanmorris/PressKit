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

			if($namespace && $namespace != '\\')
			{
				return substr($class, 0, strlen($namespace)) == $namespace;
			}

			return TRUE;
		});

		foreach($models as $class)
		{
			if(!$stateClass = $class::canHaveOne('state'))
			{
				continue;
			}

			if($class == 'SeanMorris\TheWhtRbt\Event')
			{
				continue;
			}

			if($class == 'SeanMorris\TheWhtRbt\Location')
			{
				continue;
			}

			printf(
				'Checking models of class %s' . PHP_EOL
					. "\t" . 'Should have states of class %s' . PHP_EOL
				, $class
				, $stateClass
			);

			$generator = $class::generateFlatByNull();

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
					if($state)
					{
						$stateSkeleton = $state->unconsume();

						unset($stateSkeleton['id']);
					}

					if(!$state)
					{
						$stateSkeleton = [
							'state' => 0
							, 'owner' => 0
						];

						$state = new $stateClass;
					}

					$state->consume($stateSkeleton);

					$state->save();

					$model->addSubject('state', $state, TRUE);

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

	public function userStateCleanup()
	{
		$generator = \SeanMorris\Access\User::generateSubmodels();

		foreach($generator() as $user)
		{
			$state = $user->getSubject('state');
			$state->consume([
				'owner' => $user
			], TRUE);

			$state->save();
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

	public function roleCleanup()
	{
		$userGen = \SeanMorris\Access\User::generateSubmodel();

		foreach($userGen() as $user)
		{
			$user = $user::loadOneById($user->id);

			$roles = $user->getSubjects('roles');

			$newRoles = [];

			foreach($roles as $role)
			{
				$newRoles[get_class($role)] = $role;
			}

			$user->consume(['roles' => array_values($newRoles)]);

			$user->save();
		}
	}

	public function rebuildTables()
	{
		$db = \SeanMorris\Ids\Database::get('main');

		$tableQuery = $db->prepare('SHOW TABLES');

		$tableQuery->execute();

		while($table = $tableQuery->fetchColumn())
		{
			printf("Rebuilding %s...\n", $table);
			$rebuildQuery = $db->prepare($rebuildString = sprintf(
				'ALTER TABLE `%s` ENGINE = InnoDB;'
				, $table
			));

			$rebuildQuery->execute();
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

		if(!$user || !$user = $user::loadOneById($user->id))
		{
			printf('User "%s" not found.', $userId);
			return;
		}

		// var_dump($user->getSubjects('roles', TRUE));

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

		$user = $user::loadOneById($user->id);

		if(!$password)
		{
			\SeanMorris\Ids\Idilic\Cli::error("Password:");
			$password = \SeanMorris\Ids\Idilic\Cli::in();
		}

		$user->consume(['password' => $password], TRUE);
		$user->forcesave();

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

		if(!$user = \SeanMorris\Access\User::loadOneSubmodelById($userId))
		{
			$user = \SeanMorris\Access\User::loadOneSubmodelByUsername($userId);
		}

		$user = $user::loadOneById($user->id);

		if(!$user)
		{
			printf('User "%s" not found.', $userId);
			return;
		}

		while($roleClass = array_shift($args))
		{
			$role = new $roleClass;

			$role->consume([
				'grantedBy' => 1
			], TRUE);

			$role->forceSave();

			$user->addSubject('roles', $role, TRUE);
		}

		\SeanMorris\Ids\Log::debug('Saving user!');

		$user->forceSave();

		\SeanMorris\Ids\Log::debug('Saving user done!');

		print new \SeanMorris\PressKit\Idilic\View\UserInfo([
			'user' => $user
		]);
	}

	public function migrate($router)
	{
		$migrations = \SeanMorris\PressKit\Migration::list();

		$args = $router->path()->consumeNodes();
		$real = array_shift($args);

		if(!$real)
		{
			printf(
				"The following migrations will be applied:\n%s\n"
				, implode(PHP_EOL, array_map(
					function($migration)
					{
						return sprintf(
							"[%05d] %s\n\tfrom %s"
							, $migration->version
							, $migration->short
							, $migration->namespace
						);
					}
					, $migrations
				))
			);

			while(1)
			{
				$answer = \SeanMorris\Ids\Idilic\Cli::question(
					'Apply the above migrations? (y/n)'
				);

				if($answer === 'y')
				{
					break;
				}

				if($answer === 'n')
				{
					return;
				}
			}
		}

		$package       = \SeanMorris\Ids\Package::getRoot();
		$lastMigration = \SeanMorris\PressKit\MigrationRecord::loadOnebyPackage(
			$package::name()
		);

		array_map(
			function($migration) use($package)
			{
				$currentPackage = $package->getVar('migration:' . $migration->namespace);

				if($currentPackage === NULL)
				{
					$package->setVar('migration:' . $migration->namespace, -1);
				}

				\SeanMorris\Ids\Idilic\Cli::error($migration->class . '... ');

				if(($migration->class)::apply() === TRUE)
				{
					$migrationRecord = new \SeanMorris\PressKit\MigrationRecord;

					$migrationRecord->consume([
						'migration' => $migration->short
						, 'applied' => time()
						, 'package' => $migration->namespace
						, 'version' => $migration->version
					]);

					$migrationRecord->save();

					$currentPackage = $package->setVar(
						'migration:' . $migration->namespace
						, $migration->version
					);

					\SeanMorris\Ids\Idilic\Cli::error("[ Ok ]\n");
				}
				else
				{
					\SeanMorris\Ids\Idilic\Cli::error("[ Error ]\n");
				}
			}
			, $migrations
		);
	}

	public function sitemap($router, ...$args)
	{
		$entrypoint = \SeanMorris\Ids\Settings::read('entrypoint');
		$rootRoute  = new $entrypoint;
		$cmdArgs   = $router->path()->consumeNodes();

		// '127.0.0.1:3333'

		$domain = array_shift($cmdArgs);
		$models = $cmdArgs;

		$xmlWriter = new \XMLWriter();
		$xmlWriter->openMemory();
		$xmlWriter->setIndent(true);
		$xmlWriter->startDocument('1.0', 'UTF-8');
		$xmlWriter->startElement('urlset');

		$staticUrls = [
			'/'
			, '/login'
			, '/register'
		];

		foreach($staticUrls as $staticUrl)
		{
			$xmlWriter->startElement('url');
			$xmlWriter->writeElement('loc', sprintf(
				'%s%s'
				, $domain
				, $staticUrl
			));
			$xmlWriter->endElement();
		}

		fwrite(STDOUT, $xmlWriter->flush(true));

		$entries   = 0;
		$flushOn   = 1000;

		foreach($models as $modelClass)
		{
			$url = sprintf(
				'%s%s'
				, $domain
				, $rootRoute->_pathTo($modelClass)
			);

			fwrite(STDERR, $url . PHP_EOL);

			$xmlWriter->startElement('url');
			$xmlWriter->writeElement('loc', $url);
			$xmlWriter->endElement();

			static::all(
				$modelClass
				, $router
				, function($model, $done = FALSE) use(
					$rootRoute
					, $xmlWriter
					, $flushOn
					, $domain
					, &$entries
				){
					if($done)
					{
						return;
					}

					$state = $model->getSubject('state');

					if(!$state || ($state->state < 0))
					{
						return;
					}

					$path = (new \SeanMorris\Ids\Path(

						$rootRoute->_pathTo($model)

					))->append($model->publicId);

					$url = sprintf(
						'%s%s'
						, $domain
						, $path->string()
					);

					fwrite(STDERR, $url . PHP_EOL);

					$xmlWriter->startElement('url');
					$xmlWriter->writeElement('loc', $url);
					$xmlWriter->writeElement('lastmod', date(
						'Y-m-d', $model->updated ?? $model->created
					));
					$xmlWriter->endElement();

					if(++$entries > $flushOn)
					{
						fwrite(STDOUT, $xmlWriter->flush(true));
						$entries = 0;
					}
				}
			);
		}


		$xmlWriter->endElement();

		fwrite(STDOUT, $xmlWriter->flush(true));
	}

	protected static function all(
		$class
		, $router
		, callable $callback
		, $selector = []
		, callable $done = NULL
	){
		$args    = $router->path()->consumeNodes();

		$lastId   = (int) (array_shift($args) ?? 0);
		$pageSize = (int) (array_shift($args) ?? 0);
		$max      = (int) (array_shift($args) ?? 0);

		if(!$selector)
		{
			$selector = ['byNull' => []];
		}

		$processed = 0;

		foreach($selector as $by => $args)
		{
			$pageArgs   = $args;
			$pageArgs[] = $lastId;
			$pageArgs[] = $pageSize;

			$by[0] = strtoupper($by[0]);

			$selectorFunction = 'generateCursor' . $by;

			$models = $class::$selectorFunction(...$pageArgs);

			while(true)
			{
				$loaded = FALSE;

				foreach($models() as $model)
				{
					$loaded = TRUE;

					$callback($model);

					if($max)
					{
						if(++$processed >= $max)
						{
							break 3;
						}
					}
				}

				$callback(NULL, $class, $selectorFunction);

				if(!$loaded)
				{
					break;
				}

				$lastId = $model->id;

				\SeanMorris\Ids\Model::clearCache(TRUE);

				$pageArgs   = $args;
				$pageArgs[] = $lastId;
				$pageArgs[] = $pageSize;

				$models = $class::$selectorFunction(...$pageArgs);

				break;
			}
		}

		if($done)
		{
			$done();
		}
	}

	public function queueDaemon($router)
	{
		[$class,] = $router->path()->consumeNodes();

		if(!is_subclass_of($class, '\SeanMorris\Ids\Queue'))
		{
			throw new Exception(sprintf(
				"Provided class does not inherit: %s\n\t%s"
				, '\SeanMorris\Ids\Queue'
				, $class
			));
		}

		$class::listen();
	}
}
