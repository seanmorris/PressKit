<?php
namespace SeanMorris\PressKit\Route;
class ScaffoldController extends \SeanMorris\PressKit\Controller
{
	protected function metaScaffold()
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

		return \SeanMorris\PressKit\Scaffold::produceScaffold($config);
	}

	public function app()
	{
		return ' ';
	}

	public function _dynamic($router)
	{
		$id = $router->path()->getNode();
		$meta = $this->metaScaffold();
		$metaObj = $meta::loadOneById($id);

		if($action = $router->path()->consumeNode())
		{
			switch(TRUE)
			{
				case 'list' == $action:
					$return = [];
					$metaMeta = \SeanMorris\PressKit\Scaffold::produceScaffold($metaObj->info);
					
					if($id = $router->path()->consumeNode())
					{

						if($object = $metaMeta::loadOneById($id))
						{
							return $object;
						}
					}
					$metaLoader = $metaMeta::generatePage(0,10);
					foreach($metaLoader() as $metaObj)
					{
						$return[] = $metaObj;

						if(count($return) >= 1)
						{
							break;
						}
					}
					break;
				case is_numeric($action):
					$return = $meta::loadOneById($action);
					break;
			}
		}
		else
		{
			$return = $metaObj;
		}

		return $return;
	}

	public function index($router)
	{
		$scaffolds = [];
		foreach($this->metaScaffold()::x()() as $m)
		{
			$scaffolds[] = $m;
		}

		return $scaffolds;
	}

	public function create($router)
	{
		$meta = $this->metaScaffold();
		//$name = $router->path()->consumeNode();
		$meta->name = $name = 'yoga';
		$meta->info = $info = [
			'table'    => $name
			, 'name'   => $name
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
			, 'schema' => [
				'id'         => [NULL, 'INT(11) UNSIGNED NOT NULL AUTO_INCREMENT']
				, 'name'     => [NULL, 'VARCHAR(255) NOT NULL']
				, 'place_id' => [NULL, 'VARCHAR(255) NOT NULL']
			]
		];

		$meta->save();

		$file = fopen('/home/sean/yoga_2017-05-10.csv', 'r');
		$header = [];

		while($line = fgetcsv($file))
		{
			set_time_limit(10);

			if(!$header)
			{
				$header = $line;
				
				foreach($header as $k)
				{
					if(!preg_match('/^\w/', $k))
					{
						continue;
					}

					$info['schema'][$k] = [NULL, 'INT(11) SIGNED NULL'];

					$meta->info = $info;
				}

				continue;
			}

			if(count($header) !== count($line))
			{
				continue;
			}
			
			$line = array_combine($header, $line);

			$line['detail'] = NULL;

			$addressParts = json_decode($line['addressComponents'], TRUE);
			$addressPartsNamed = [];

			if($addressParts)
			{
				foreach($addressParts as $addressPart)
				{
					$addressPartsNamed[ $addressPart['types'][0] ] = $addressPart;
				}

				if(isset($addressPartsNamed['administrative_area_level_2']))
				{
					$line['city'] = $addressPartsNamed['administrative_area_level_2']['long_name'];
				}

				if(isset($addressPartsNamed['administrative_area_level_1']))
				{
					$line['state'] = $addressPartsNamed['administrative_area_level_1']['short_name'];
				}

				if(isset($addressPartsNamed['country']['short_name']))
				{
					$line['country'] = $addressPartsNamed['country']['short_name'];
				}
			}

			foreach($line as $k => $v)
			{
				if(!preg_match('/^\w/', $k))
				{
					continue;
				}

				if(!is_numeric($v))
				{
					if(strlen($v) < 1024 && (!isset($info['schema'][$k]) || $info['schema'][$k][1] != 'VARCHAR(1024) NULL'))
					{
						$info['schema'][$k] = [NULL, 'VARCHAR(1024) NULL'];
					}
					else if(strlen($v) >= 1024 && (!isset($info['schema'][$k]) || $info['schema'][$k][1] != 'LONGTEXT NULL'))
					{
						$info['schema'][$k] = [NULL, 'LONGTEXT NULL'];
					}

					$meta->info = $info;
				}
			}

			//$meta->save();

			$model = \SeanMorris\PressKit\Scaffold::produceScaffold($meta->info);

			foreach($line as $k => $v)
			{
				$model->{$k} = $v;
			}

			try
			{
				$model->save();	
			}
			catch(\PDOException $exception)
			{

			}
		}

		return $name;
	}
}