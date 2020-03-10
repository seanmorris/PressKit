<?php
namespace SeanMorris\PressKit;
class Listener
{
	protected static $hub, $agent;

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
		if(static::$agent)
		{
			return static::$agent;
		}

		static::$agent = new \SeanMorris\Kallisti\Agent;

		static::$agent->register(static::getHub());

		return static::$agent;
	}

	protected static function channel()
	{
		return '*';
	}

	public static function publish($channel, ...$message)
	{
		$agent = static::getAgent();

		\SeanMorris\Ids\Log::trace($channel, $message);

		return $agent->send($channel, $message);
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
