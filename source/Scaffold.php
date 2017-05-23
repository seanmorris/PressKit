<?php
namespace SeanMorris\PressKit;
class Scaffold extends Model
{
	/**
	 *	TODO: Forms, relationships, truncations and dropping.
	 */

	protected static
		$config = []
		, $ignore = ['class', 'config']
		, $registry = []
		, $classRegistry
		, $metaScaffold
		, $tablesCreated = []
	;

	final public function __construct($config = [], $meta = FALSE)
	{
		$this->class = get_called_class();

		if(!$meta && !isset(static::$metaScaffold[get_called_class()]))
		{
			static::$metaScaffold[get_called_class()] = static::metaScaffold();
		}

		if($config)
		{
			static::$config[get_called_class()] = $config;

			if($config['name'])
			{
				self::$registry[$config['name']] = [
					'name'   => $config['name'],
					'class'  => $this->class,
					'config' => $config,
				];

				self::$classRegistry[$this->class] =& self::$registry[$config['name']];

				if(!$meta)
				{
					self::$registry[$config['name']] =& static::$metaScaffold[get_called_class()]->info;
				}
			}
		}

		if(isset(static::$config[get_called_class()]['schema']))
		{
			foreach(static::$config[get_called_class()]['schema'] as $property => &$value)
			{
				if($value === FALSE)
				{
					continue;
				}
				$this->{$property} = $value[0];
			}
		}

		$this->createTable();
	}

	public function createTable()
	{
		if(isset(static::$tablesCreated[get_called_class()]))
		{
			return;
		}

		static::$tablesCreated[get_called_class()] = TRUE;

		$columns = [];
		$keys    = [];

		if(isset(static::$config[get_called_class()]['schema']))
		{
			foreach(static::$config[get_called_class()]['schema'] as $property => $value)
			{
				$columns[] = sprintf('`%s` %s', $property, $value[1]);
			}
		}

		if(isset(static::$config[get_called_class()]['keys']))
		{
			if(isset(static::$config[get_called_class()]['keys']['primary']))
			{
				$keys[] = sprintf(
					'PRIMARY KEY (`%s`)'
					, implode('`, `', static::$config[get_called_class()]['keys']['primary'])
				);
			}

			if(isset(static::$config[get_called_class()]['keys']['unique']))
			{
				foreach(static::$config[get_called_class()]['keys']['unique'] as $keyName => $keyCols)
				{
					$keys[] = sprintf(
						'UNIQUE KEY `%s` (`%s`)'
						, $keyName
						, implode('`, `', $keyCols)
					);
				}
			}

			if(isset(static::$config[get_called_class()]['keys']['index']))
			{
				foreach(static::$config[get_called_class()]['keys']['index'] as $keyName => $keyCols)
				{
					$keys[] = sprintf(
						'KEY `%s` (`%s`)'
						, $keyName
						, implode('`, `', $keyCols)
					);
				}
			}
		}

		$engine = 'InnoDB';

		if(isset(static::$config[get_called_class()]['engine']))
		{
			$engine = static::$config[get_called_class()]['engine'];
		}

		$database = static::database();

		$query = sprintf(
			"CREATE TABLE IF NOT EXISTS `%s` (\n%s\n%s\n) ENGINE = %s"
			, static::$config[get_called_class()]['table']
			, implode("\n, ", $columns)
			, $keys ? (', ' . implode("\n, ", $keys)) : NULL
		, $engine
		);

		return $database->query($query);
	}

	public function updateTable()
	{
		$database = static::database();

		$columns = $database->query(sprintf(
			'SHOW FULL COLUMNS FROM `%s`'
			, static::table()
		));

		$addressedColumns = [];

		while($column = $columns->fetchObject())
		{
			if(!is_numeric($default = $column->Default))
			{
				$default = '"' . $default . '"';
			}

			$columnString = sprintf(
				"%s %sNULL%s%s"
				, strtoupper($column->Type)
				, $column->Null    === 'NO' ? 'NOT ' : NULL
				, $column->Default !== NULL ? ' DEFAULT ' . $default : NULL
				, $column->Extra ? ' ' . strtoupper($column->Extra) : NULL
			);

			if(isset(static::$config[get_called_class()]['schema'][$column->Field]))
			{
				if(static::$config[get_called_class()]['schema'][$column->Field] === FALSE)
				{
					$database->query(sprintf(
						'ALTER TABLE `%s` DROP COLUMN IF EXISTS `%s`'
						, static::table()
						, $column->Field
					));

					$addressedColumns[] = $column->Field;
				}
				else if(static::$config[get_called_class()]['schema'][$column->Field][1] !== $columnString)
				{
					try
					{
						$database->query(sprintf(
							'ALTER TABLE `%s` MODIFY COLUMN `%s` %s'
							, static::table()
							, $column->Field
							, static::$config[get_called_class()]['schema'][$column->Field][1]
						));
					}
					catch(\PDOException $exception)
					{
						if($exception->getCode() != 22001)
						{
							throw $exception;
						}
					}

					$addressedColumns[] = $column->Field;
				}
				else if(static::$config[get_called_class()]['schema'][$column->Field][1] == $columnString)
				{
					$addressedColumns[] = $column->Field;
				}
			}
		}

		if(isset(static::$config[get_called_class()]['schema']))
		{
			foreach(static::$config[get_called_class()]['schema'] as $column => $def)
			{
				if(in_array($column, $addressedColumns))
				{
					continue;
				}
				else
				{
					$database->query(sprintf(
						'ALTER TABLE `%s` ADD `%s` %s'
						, static::table()
						, $column
						, $def[1]
					));
				}
			}
		}
	}

	public static function produceScaffold($config, $meta = FALSE)
	{
		static $classes = [];

		$namespace = 'SeanMorris\PressKit\Scaffold';

		if(is_string($config))
		{
			if(!$config = json_decode($config))
			{
				return;
			}
		}

		if(!isset($config['name']))
		{
			$config['name'] = sha1(print_r($config, 1));
		}

		$fullClass = $namespace . '\\' . $config['name'];

		if(!isset($classes[$fullClass]))
		{
			eval(sprintf(
				'namespace %s;class %s extends \SeanMorris\PressKit\Scaffold{%s}'
				, $namespace
				, $config['name']
				, isset($config['traits'])
					? NULL //'use ' . implode(', ', $config['traits']) . '; '
					: NULL
			));

			$classes[$fullClass] = TRUE;
		}

		$obj = new $fullClass($config, $meta);

		if($meta)
		{
			$obj->name = $config['name'];
		}

		\SeanMorris\Ids\Log::trace();

		return $obj;
	}

	protected static function database($database = 'main')
	{
		return \SeanMorris\Ids\Database::get($database);
	}

	protected static function table()
	{
		if(isset(static::$config[get_called_class()]))
		{
			return static::$config[get_called_class()]['table'];
		}
	}

	public static function getProperties($all = FALSE)
	{
		$properties = parent::getProperties($all);

		foreach(static::$config[get_called_class()]['schema'] as $property => $def)
		{
			if(!preg_match('/^[^_]\w+$/', $property))
			{
				continue;
			}

			if(!in_array($property, $properties))
			{
				$properties[] = $property;
			}
		}

		return $properties;
	}

	protected function properties()
	{
		$properties = [];

		if(isset(static::$config[get_called_class()]['schema']))
		{
			foreach(static::$config[get_called_class()]['schema'] as $property => $value)
			{
				if(!preg_match('/^[^_]\w+$/', $property))
				{
					continue;
				}

				if($value === FALSE)
				{
					//continue;
				}
				$properties[$property] = $this->{$property};
			}
		}

		return $properties;
	}

	protected static function getColumns($type = null, $all = true)
	{
		$columns = [];

		if(isset(static::$config[get_called_class()]['schema']))
		{
			foreach(static::$config[get_called_class()]['schema'] as $property => $value)
			{
				if(empty($property) || !preg_match('/^[^_]\w+$/', $property))
				{
					continue;
				}

				if($value === FALSE)
				{
					continue;
				}
				$columns[$property] = $property;
			}
		}

		return $columns + parent::getColumns($type, $all);
	}

	protected function _create($curClass)
	{
		$schemaChanged = FALSE;

		foreach($this as $property => &$value)
		{
			if(is_scalar($value) && !is_numeric($value))
			{
				if(strlen($value) < 1024 && !isset(static::$config[get_called_class()]['schema'][$property]))
				{
					static::$config[get_called_class()]['schema'][$property] = [NULL, 'VARCHAR(1024) NULL'];
					$schemaChanged = TRUE;
				}
				else if(strlen($value) >= 1024
					&& (!isset(static::$config[get_called_class()]['schema'][$property])
						|| static::$config[get_called_class()]['schema'][$property][1] != 'LONGTEXT NULL'
					)
				){
					static::$config[get_called_class()]['schema'][$property] = [NULL, 'LONGTEXT NULL'];
					$schemaChanged = TRUE;
				}
			}
			else if(is_numeric($value))
			{
				if(!isset(static::$config[get_called_class()]['schema'][$property]))
				{
					static::$config[get_called_class()]['schema'][$property] = [NULL, 'INT UNSIGNED NULL'];
					$schemaChanged = TRUE;
				}
				else if($value < 0
					&& (!isset(static::$config[get_called_class()]['schema'][$property])
						|| static::$config[get_called_class()]['schema'][$property][1] != 'INT SIGNED NULL'
					)
				){
					static::$config[get_called_class()]['schema'][$property] = [NULL, 'INT SIGNED NULL'];
					$schemaChanged = TRUE;
				}
			}
		}

		unset($value);

		if($schemaChanged)
		{
			if(isset(static::$metaScaffold[get_called_class()]))
			{
				static::$metaScaffold[get_called_class()]->info = static::$config[get_called_class()];
				static::$metaScaffold[get_called_class()]->save();
			}

			$this->updateTable();
		}

		foreach($this as $property => $value)
		{
			if(!preg_match('/^\w+$/', $property))
			{
				unset($this->$property);
			}
		}

		return parent::_create($curClass);
	}

	protected static function beforeWrite($instance, &$skeleton)
	{
		foreach($skeleton as $property => $value)
		{
			if(!is_scalar($value) && !is_null($value))
			{
				$skeleton[$property] = json_encode($value);
			}
		}
	}

	protected static function afterRead($instance)
	{
		foreach($instance as $property => &$value)
		{
			\SeanMorris\Ids\Log::debug(sprintf(
				'Checking if %s is an array...'
				, $property
			));
			if(is_string($value) && is_array($obj = json_decode($value, TRUE)))
			{
				$value = $obj;
			}
		}
	}

	protected static function metaScaffold()
	{
		$config = [
			'table'    => 'MetaScaffold'
			, 'name'   => 'MetaScaffold'
			, 'engine' => 'InnoDB'
			, 'keys'   => ['primary' => ['id'], 'unique' => [
				'name' => ['name']
			]]
			, 'schema' => [
				'id'     => [NULL, 'INT(11) UNSIGNED NOT NULL AUTO_INCREMENT']
				, 'name' => [NULL, 'VARCHAR(512) NOT NULL']
				, 'info' => [NULL, 'LONGTEXT NOT NULL']
			]
			, 'traits' => [
				'\SeanMorris\PressKit\MetaScaffold'
			]
		];

		return static::produceScaffold($config, TRUE);
	}
}
