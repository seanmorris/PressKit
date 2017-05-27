<?php
namespace SeanMorris\PressKit\Route;
class ScaffoldController extends \SeanMorris\PressKit\Controller
{
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
		$file = fopen('/home/sean/yoga_2017-05-10.csv', 'r');
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

			$name = 'y_test_5';

			$info = [
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
					'id'         => [NULL, 'INT UNSIGNED NOT NULL AUTO_INCREMENT']
					, 'name'     => [NULL, 'VARCHAR(255)']
					, 'place_id' => [NULL, 'VARCHAR(255)']
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
			catch(\PDOException $exception)
			{
				if($exception->errorInfo[1] != 1062)
				{
					throw $exception;
				}
			}
		}

		return $name;
	}
}
