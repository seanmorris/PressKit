<?php
namespace SeanMorris\PressKit;
class Listener
{
	protected static $hub, $agents = [];

	protected static function getHub()
	{
		if(static::$hub)
		{
			return static::$hub;
		}

		self::$hub = new \SeanMorris\Kallisti\Hub;

		return self::$hub;
	}

	protected static function getAgent()
	{
		if(static::$agents[get_called_class()] ?? FALSE)
		{
			return static::$agents[get_called_class()];
		}

		static::$agents[get_called_class()] = new \SeanMorris\Kallisti\Agent;

		static::$agents[get_called_class()]->register(static::getHub());

		return static::$agents[get_called_class()];
	}

	protected static function channel()
	{
		return '*';
	}

	public static function publish($channel, ...$messages)
	{
		$agent = static::getAgent();

		foreach($messages as $message)
		{
			return $agent->send($channel, $message);
		}
	}

	public static function listen()
	{
		static::getHub()->unsubscribe(
			'*'
			, static::getAgent()
		);

		static::getHub()->subscribe(
			static::channel()
			, static::getAgent()
		);

		static::getAgent()->expose(
			function($content, $output, $origin, $channel, $originalChannel){

				static::receive($content, $channel, $originalChannel);

			}
		);
	}

	public static function receive($content, $channel, $originalChannel)
	{

	}
}
