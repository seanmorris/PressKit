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
				if(isset(self::$registry[$config['name']]) && $config != self::$registry[$config['name']]['config'])
				{
					throw new \Exception(
						'Cannot register the same Scaffold name twice: '
						. $config['name']
					);
				}

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
					$database->query(sprintf(
						'ALTER TABLE `%s` MODIFY COLUMN `%s` %s'
						, static::table()
						, $column->Field
						, static::$config['schema'][$column->Field][1]
					));

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
				$keys[] = sprintf('PRIMARY KEY (`%s`)', implode('`, `', static::$config['keys']['primary']));	
			}
		}

		$engine = 'InnoDB';

		if(isset(static::$config['engine']))
		{
			$engine = static::$config['engine'];
		}

		$database = static::database();

		return $database->query(sprintf(
			"CREATE TABLE IF NOT EXISTS `%s` (\n%s\n%s\n) ENGINE = %s"
			, static::$config['table']
			, implode("\n, ", $columns)
			, $keys ? (', ' . implode("\n, ", $keys)) : NULL
		, $engine
		));
	}

	public static function produceScaffold($config)
	{
		static $classes = [];

		$namespace = 'SeanMorris\PressKit\Scaffold';

		if(!isset($config['name']))
		{
			$config['name'] = sha1(print_r($config, 1));
		}

		$fullClass = $namespace . '\\' . $config['name'];

		if(!isset($classes[$fullClass]))
		{
			eval(sprintf(
				'namespace %s; class %s extends \SeanMorris\PressKit\Scaffold {}'
				, $namespace
				, $config['name']
			));

			$classes[$fullClass] = TRUE;
		}

		return new $fullClass($config);
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
}