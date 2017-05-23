<?php
namespace SeanMorris\PressKit;
class Scaffold extends Model
{
	/**
	 *	TODO: Forms, relationships, truncations and dropping.
	 */

	protected static $config = [], $ignore = ['class', 'config'], $registry = [], $classRegistry;

	final public function __construct($config = [])
	{
		$this->class = get_called_class();

		if($config)
		{
			static::$config = $config;

			if($config['name'])
			{
				self::$registry[$config['name']] = [
					'name'   => $config['name'],
					'class'  => $this->class,
					'config' => $config,
				];

				self::$classRegistry[$this->class] =& self::$registry[$config['name']];
			}
		}

		if(isset(static::$config['schema']))
		{
			foreach(static::$config['schema'] as $property => &$value)
			{
				if($value === FALSE)
				{
					continue;
				}
				$this->{$property} = $value[0];
			}
		}

		$this->createTable();
		$this->updateTable();
	}

	public function createTable()
	{
		$columns = [];
		$keys    = [];
		
		if(isset(static::$config['schema']))
		{
			foreach(static::$config['schema'] as $property => $value)
			{
				$columns[] = sprintf('`%s` %s', $property, $value[1]);
			}
		}

		if(isset(static::$config['keys']))
		{
			if(isset(static::$config['keys']['primary']))
			{
				$keys[] = sprintf(
					'PRIMARY KEY (`%s`)'
					, implode('`, `', static::$config['keys']['primary'])
				);	
			}

			if(isset(static::$config['keys']['unique']))
			{
				foreach(static::$config['keys']['unique'] as $keyName => $keyCols)
				{
					$keys[] = sprintf(
						'UNIQUE KEY `%s` (`%s`)'
						, $keyName
						, implode('`, `', $keyCols)
					);	
				}
			}

			if(isset(static::$config['keys']['index']))
			{
				foreach(static::$config['keys']['index'] as $keyName => $keyCols)
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

		if(isset(static::$config['engine']))
		{
			$engine = static::$config['engine'];
		}

		$database = static::database();

		$query = sprintf(
			"CREATE TABLE IF NOT EXISTS `%s` (\n%s\n%s\n) ENGINE = %s"
			, static::$config['table']
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

			if(isset(static::$config['schema'][$column->Field]))
			{
				if(static::$config['schema'][$column->Field] === FALSE)
				{
					$database->query(sprintf(
						'ALTER TABLE `%s` DROP COLUMN IF EXISTS `%s`'
						, static::table()
						, $column->Field
					));

					$addressedColumns[] = $column->Field;
				}
				else if(static::$config['schema'][$column->Field][1] !== $columnString)
				{
					try
					{
						$database->query(sprintf(
							'ALTER TABLE `%s` MODIFY COLUMN `%s` %s'
							, static::table()
							, $column->Field
							, static::$config['schema'][$column->Field][1]
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
				else if(static::$config['schema'][$column->Field][1] == $columnString)
				{
					$addressedColumns[] = $column->Field;
				}
			}
		}

		if(isset(static::$config['schema']))
		{
			foreach(static::$config['schema'] as $column => $def)
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

	public static function produceScaffold($config)
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

		$obj = new $fullClass($config);

		\SeanMorris\Ids\Log::trace();

		return $obj;
	}

	protected static function database($database = 'main')
	{
		return \SeanMorris\Ids\Database::get($database);
	}

	protected static function table()
	{
		$class = get_called_class();

		if(isset(
			self::$classRegistry[$class]
			, self::$classRegistry[$class]['config']
			, self::$classRegistry[$class]['config']['table']
		)){
			return self::$classRegistry[$class]['config']['table'];
		}
	}

	public static function getProperties($all = FALSE)
	{
		$properties = parent::getProperties($all);

		foreach(static::$config['schema'] as $property => $def)
		{
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
		
		if(isset(static::$config['schema']))
		{
			foreach(static::$config['schema'] as $property => $value)
			{
				if($value === FALSE)
				{
					continue;
				}
				$properties[$property] =& $this->{$property};
			}
		}

		return $properties;
	}

	protected static function getColumns($type = null, $all = true)
	{
		$columns = [];

		if(isset(static::$config['schema']))
		{
			foreach(static::$config['schema'] as $property => $value)
			{
				if($value === FALSE)
				{
					continue;
				}
				$columns[$property] = $property;
			}
		}

		return $columns + parent::getColumns($type, $all);
	}

	protected static function beforeWrite($instance, &$skeleton)
	{
		foreach($skeleton as $property => &$value)
		{
			if(!is_scalar($value))
			{
				$value = json_encode($value);
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
			if(is_array($obj = json_decode($value, TRUE)))
			{
				$value = $obj;
			}
		}
	}
}