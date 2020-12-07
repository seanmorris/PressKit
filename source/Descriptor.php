<?php
namespace SeanMorris\PressKit;
class Descriptor extends Model
{
	protected $id, $name, $info = [];
	protected static
		$table = 'Descriptor'
		, $byName = [
			'where' => [['name' => '?']]
		]
	;

	const DEFAULT_COLUMN = [
		'sqlDef'  => 0,
		'numeric' => TRUE,
		'null'    => FALSE,
		'length'  => 11
	];

	const NUMBER_PRECEDENCE = [
		'INT(%d) UNSIGNED'
		, 'INT(%d) SIGNED'
		, 'DOUBLE'
	];

	const TEXT_PRECEDENCE = [
		'VARCHAR(%d)'
		, 'LONGTEXT'
	];

	public function addHas($property, $class, $many = FALSE)
	{
		if($many)
		{
			$this->info['hasMany'][$property] = $class;
		}
		else
		{
			$this->info['hasOne'][$property] = $class;
		}
	}

	public static function beforeWrite($instance, &$skeleton)
	{
		$skeleton['info'] = json_encode($instance->info);
	}

	public static function afterWrite($instance, &$skeleton)
	{
		$instance->info = json_decode($instance->info, TRUE);
	}

	public static function afterRead($instance)
	{
		$instance->info = json_decode($instance->info, TRUE);
	}

	public function columns()
	{
		return array_keys($this->info['schema']);
	}

	public function getColumnDef($column)
	{
		if(!$this->info)
		{
			$this->info = [];
		}

		if(!isset($this->info['schema']))
		{
			$this->info['schema'] = [];
		}

		if(!isset($this->info['schema'][$column]))
		{
			$this->info['schema'][$column] = static::DEFAULT_COLUMN;
		}

		$prec = static::NUMBER_PRECEDENCE;

		if(!$this->info['schema'][$column]['numeric'] && $column !== 'id')
		{
			$prec = static::TEXT_PRECEDENCE;
		}

		$def = $prec[ $this->info['schema'][$column]['sqlDef'] ];

		$def .= $this->info['schema'][$column]['null'] ? ' NULL' : ' NOT NULL';

		$def = sprintf($def, $this->info['schema'][$column]['length']);

		if($column == 'id')
		{
			$def .= ' AUTO_INCREMENT';
		}

		return $def;
	}

	public function columnChanged($column, $value)
	{
		$changed = FALSE;

		if(!isset($this->info['schema'][$column]))
		{
			$this->info['schema'][$column] = static::DEFAULT_COLUMN;
		}

		$currentType = $this->info['schema'][$column]['sqlDef'];

		if(!is_null($value))
		{
			if(is_numeric($value) && $this->info['schema'][$column]['numeric'])
			{
				if($currentType == 0 && $value < 0)
				{
					$this->info['schema'][$column]['sqlDef'] = 1;
					$changed = TRUE;
				}

				if($currentType <= 1 && is_float($value))
				{
					$this->info['schema'][$column]['sqlDef'] = 2;
					$changed = TRUE;
				}
			}
			else if(is_string($value) && $column !== 'id')
			{
				if($this->info['schema'][$column]['numeric'])
				{
					$this->info['schema'][$column]['numeric'] = FALSE;
					$changed = TRUE;
				}

				$newLength = strlen($value);

				$currentLength = $this->info['schema'][$column]['length'];

				if($currentType == 0 && $newLength > $currentLength)
				{
					$this->info['schema'][$column]['length'] = $newLength;

					if($newLength > 1024)
					{
						$this->info['schema'][$column]['sqlDef'] = 1;
					}

					$changed = TRUE;
				}
			}
		}
		else if($column !== 'id')
		{
			if(!$this->info['schema'][$column]['null'])
			{
				$this->info['schema'][$column]['null'] = TRUE;
				$changed = TRUE;
			}
		}

		return $changed;
	}
}
