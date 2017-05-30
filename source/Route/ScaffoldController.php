<?php
namespace SeanMorris\PressKit\Route;
class ScaffoldController extends \SeanMorris\PressKit\Controller
{
	public function app()
	{
		return ' ';
	}

	public function map()
	{
		
		print new \SeanMorris\PressKit\View\Map();
		die;
	}

	public function mapData()
	{
		$model = \SeanMorris\PressKit\Scaffold::produceScaffold(['name' => 'y_test']);
		$gen = $model::generate();

		print '{
			"type": "FeatureCollection",
			"crs": { "type": "name", "properties": { "name": "urn:ogc:def:crs:OGC:1.3:CRS84" } },
			                                                                                
			"features": [' . PHP_EOL;

		$first = TRUE;

		foreach($gen() as $model)
		{
			if(!isset($model->detail, $model->detail['geometry'], $model->detail['geometry']['location']))
			{
				continue;
			}
			if($first)
			{
				$first = FALSE;
			}
			else
			{
				print ',' . PHP_EOL;
			}
			print json_encode([
				"type"=> "Feature",
				"geometry"=> [
				  "type"=> "Point",
				  "coordinates"=> [
				  	$model->detail['geometry']['location']['lng'],
				  	$model->detail['geometry']['location']['lat']
				  ]
				],
				"properties"=> [
				  "marker-color"=> "#3ca0d3",
				  "marker-size"=> "large",
				  "marker-symbol"=> "rocket",
				  "name"=> $model->name,
				  "description"=> $model->name,
				  "title"=> $model->name,
				  "id"=> $model->id,
				],
			]);
		}

		print ']}' . PHP_EOL;

		die;
	}

	public function _dynamic($router)
	{
		return;

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
		return $scaffolds;
	}

	public function create($router)
	{
		$file = fopen('/home/sean/thewhtrbt/util/super_scraper/result/yoga.csv', 'r');
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
}
