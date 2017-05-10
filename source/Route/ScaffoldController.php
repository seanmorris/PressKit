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
		];

		return \SeanMorris\PressKit\Scaffold::produceScaffold($config);
	}

	protected function hydrate($meta)
	{
		if(!$meta->info)
		{
			return;
		}

		return \SeanMorris\PressKit\Scaffold::produceScaffold($meta->info);
	}	

	public function index($router)
	{
		foreach($this->metaScaffold()::x()() as $m)
		{
			var_dump($m);
		}

		die;
	}

	public function create($router)
	{
		$meta = $this->metaScaffold();
		$name = $router->path()->consumeNode();
		$meta->info = $info = [
			'table'    => $name
			, 'name'   => $name
			, 'engine' => 'InnoDB'
			, 'keys'   => ['primary' => ['id']]
			, 'schema' => [
				'id' => [NULL, 'INT(11) UNSIGNED NOT NULL AUTO_INCREMENT']
			]
		];

		$file = fopen('/home/sean/yoga_cleaned.csv', 'r');
		$header = [];

		while($line = fgetcsv($file))
		{
			set_time_limit(10);

			if(!$header)
			{
				$header = $line;
				
				foreach($header as $k)
				{
					$info['schema'][$k] = [NULL, 'INT(11) SIGNED NULL'];

					$meta->info = $info;
				}

				continue;
			}
			
			$line = array_combine($header, $line);
			
			foreach($line as $k => $v)
			{
				if(!is_numeric($v) && $info['schema'][$k][1] != 'VARCHAR(512) NULL')
				{
					$info['schema'][$k] = [NULL, 'VARCHAR(512) NULL'];

					$meta->info = $info;
				}
			}

			//$meta->save();

			$model = $this->hydrate($meta);

			foreach($line as $k => $v)
			{
				$model->{$k} = $v;
			}

			$model->save();
		}

		return $name;
	}
}