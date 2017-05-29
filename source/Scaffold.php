<?php
namespace SeanMorris\PressKit;
class Scaffold extends Model
{
	/**
	 *	TODO: Forms, relationships, truncations and dropping.
	 */

	protected static
		$descriptor
		, $tablesCreated = []
	;

	final public function __construct($config = [])
	{
		$this->class = get_called_class();

		if(!isset(static::$descriptor))
		{
			if($descriptor = Descriptor::loadOneByName($config['name']))
			{
				static::$descriptor = $descriptor;
			}
			else
			{
				static::$descriptor = new Descriptor();
				static::$descriptor->name = $config['name'];
				static::$descriptor->info = $config;
				static::$descriptor->save();
			}
		}

		if(!isset(static::$table))
		{
			static::$table = static::$descriptor->name;
		}
		
		if(isset(static::$descriptor->info['hasOne']))
		{
			static::$hasOne = static::$descriptor->info['hasOne'];
		}
		
		if(isset(static::$descriptor->info['hasMany']))
		{
			static::$hasMany = static::$descriptor->info['hasMany'];
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
		$keys    = ['PRIMARY KEY(`id`)'];

		foreach(['id' => NULL] as $property => $value)
		{
			if(preg_match('/^[\W_]/', $property))
			{
				continue;
			}

			$columns[] = sprintf(
				'`%s` %s'
				, $property
				, static::$descriptor->getColumnDef($property)
			);
		}

		$engine = 'InnoDB';

		$database = static::database();

		$query = sprintf(
			"CREATE TABLE IF NOT EXISTS `%s` (\n%s\n%s\n) ENGINE = %s"
			, static::table()
			, implode("\n, ", $columns)
			, $keys ? (', ' . implode("\n, ", $keys)) : NULL
		, $engine
		);

		return $database->query($query);
	}

	public function updateTable($columnsChanged)
	{
		$database = static::database();

		$columns = $database->query(sprintf(
			'SHOW FULL COLUMNS FROM `%s`'
			, static::$table
		));

		$addressedColumns = [];

		while($column = $columns->fetchObject())
		{
			if(in_array($column->Field, $columnsChanged)
				&& $def = static::$descriptor->getColumnDef($column->Field)
			){
				$database->query(sprintf(
					'ALTER TABLE `%s` MODIFY COLUMN `%s` %s'
					, static::$table
					, $column->Field
					, $def
				));
			}

			$addressedColumns[] = $column->Field;
		}

		$columns = static::$descriptor->columns();

		foreach($this as $property => $value)
		{
			$columns[] = $property;
		}

		foreach($columns as $column)
		{
			if(!preg_match('/^[a-z]\w+/i', $column))
			{
				continue;
			}
			
			if(in_array($column, $addressedColumns))
			{
				continue;
			}
			try
			{
				$database->query(sprintf(
					'ALTER TABLE `%s` ADD `%s` %s'
					, static::$table
					, $column
					, static::$descriptor->getColumnDef($column)
				));	
			}
			catch(\PDOException $exception)
			{
				if($exception->errorInfo[1] != 1060)
				{
					throw $exception;
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
				'namespace %s;class %s extends \SeanMorris\PressKit\Scaffold{
					protected static
						$hasOne = []
						, $hasMany = []
						, $table
						, $descriptor;
					%s
				}'
				, $namespace
				, $config['name']
				, isset($config['traits'])
					? 'use ' . implode(', ', $config['traits']) . '; '
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
		if(isset(static::$descriptor))
		{
			return static::$descriptor->name;
		}
	}

	public static function getProperties($all = FALSE)
	{
		$properties = parent::getProperties($all);

		if(isset(static::$descriptor))
		{
			foreach(static::$descriptor->columns() as $property)
			{
				if(!preg_match('/^[a-z]\w+/i', $property))
				{
					continue;
				}

				if(!in_array($property, $properties))
				{
					$properties[] = $property;
				}
			}
		}

		foreach($properties as $property => $value)
		{
			if(!preg_match('/^[a-z]\w+/i', $property))
			{
				unset($properties[$property]);
			}
		}

		return $properties;
	}

	protected function properties()
	{
		$properties = parent::properties();

		foreach($this as $property => $value)
		{
			$properties[$property] = $value;
		}

		if(isset(static::$descriptor))
		{
			foreach(static::$descriptor->columns() as $property)
			{
				if(!isset($properties[$property]))
				{
					$properties[$property] = NULL;
				}
			}
		}

		foreach($properties as $property => $value)
		{
			if(!preg_match('/^[a-z]\w+/i', $property))
			{
				unset($properties[$property]);
			}
		}

		return $properties;
	}

	protected static function getColumns($type = null, $all = true)
	{
		$columns = parent::getColumns($type, $all);

		if(isset(static::$descriptor))
		{
			foreach(static::$descriptor->columns() as $property)
			{
				if(!isset($columns[$property]))
				{
					$columns[$property] = $property;
				}
			}
		}

		foreach($columns as $property => $value)
		{
			if(!preg_match('/^[a-z]\w+/i', $property))
			{
				unset($columns[$property]);
			}
		}

		return $columns;
	}

	protected function _create($curClass)
	{
		$schemaChanged = [];

		foreach($this as $property => $value)
		{
			$arr = $value;

			if(!is_array($value))
			{
				if(is_string($value))
				{
					$arr = json_decode($value, TRUE);
				}

				if(is_object($value))
				{
					$arr = (array) $value;
				}
			}

			$isList = FALSE;

			if(is_array($arr) && count(array_filter(array_keys($arr), 'is_numeric')) == count($arr))
			{
				$isList = TRUE;
			}

			$fragmentMode = FALSE;

			if(isset(static::$descriptor->info['frag']))
			{
				$fragmentMode = static::$descriptor->info['frag'];
			}

			if(is_array($arr) && $arr && !$isList && $fragmentMode)
			{
				$submodel = \SeanMorris\PressKit\Scaffold::produceScaffold([
					'name' => '__' . $property . '__' . sha1(static::table())
				]);

				foreach($arr as $k => $v)
				{
					if($k == 'id')
					{
						$k = 'original_id';
					}

					$submodel->{$k} = $v;
				}

				$submodel->save();

				$properties[$property] = $this->$property = $submodel;

				static::$descriptor->addHas($property, get_class($submodel));
				static::$descriptor->save();

				static::$hasOne[$property] = get_class($submodel);
			}
			else if(is_array($arr) && array_filter(array_keys($arr), 'is_numeric'))
			{
				$properties[$property] = json_encode($arr);
			}
			else
			{
				$properties[$property] = $value; 
			}
		}

		if(isset(static::$descriptor))
		{
			foreach(static::$descriptor->columns() as $property)
			{
				if(!preg_match('/^[a-z]\w+/i', $property))
				{
					continue;
				}

				if(!isset($properties[$property]))
				{
					$schemaChanged[] = $property;
					$properties[$property] = NULL;
				}
			}
		}

		foreach($properties as $property => $value)
		{
			if(!preg_match('/^[a-z]\w+/i', $property))
			{
				continue;
			}

			if(!is_scalar($value) && !is_null($value))
			{
				$value = json_encode($value);
			}

			if(static::$descriptor->columnChanged($property, $value))
			{
				$schemaChanged[] = $property;
			}
		}

		if($schemaChanged)
		{
			static::$descriptor->save();
			$this->updateTable($schemaChanged);
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
}
