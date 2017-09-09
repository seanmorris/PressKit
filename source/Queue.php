<?php
namespace SeanMorris\PressKit;

use PhpAmqpLib\Message\AMQPMessage,
	PhpAmqpLib\Connection\AMQPStreamConnection;

abstract class Queue
{
	const RABBIT_MQ_SERVER  = 'default'
		, QUEUE_PASSIVE     = FALSE
		, QUEUE_DURABLE     = FALSE
		, QUEUE_EXCLUSIVE   = FALSE
		, QUEUE_AUTO_DELETE = FALSE
		, CHANNEL_LOCAL     = FALSE
		, CHANNEL_NO_ACK    = TRUE
		, CHANNEL_EXCLUSIVE = FALSE
		, CHANNEL_WAIT      = FALSE;
	protected static $channel;
	abstract protected static function recieve($message);
	public static function send($message)
	{
		$channel = static::getChannel(get_called_class());
		$channel->basic_publish(new AMQPMessage(serialize($message)), '', get_called_class());
	}
	public static function manageReciept($message)
	{
		if(static::recieve(unserialize($message->body), $message) !== FALSE && !static::CHANNEL_NO_ACK)
		{
			$message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
		}
	}
	protected static function getChannel()
	{
		if(!static::$channel)
		{

			$servers = \SeanMorris\Ids\Settings::read('rabbitMq');
			
			if(!$servers)
			{
				throw new \Exception('No RabbitMQ servers specified.');
			}
			if(!isset($servers->{static::RABBIT_MQ_SERVER}))
			{
				throw new \Exception(sprintf(
					'No RabbitMQ server "%s" specified.'
					, static::RABBIT_MQ_SERVER
				));
			}
			$connection = new AMQPStreamConnection(
				$servers->{static::RABBIT_MQ_SERVER}->{'server'}
				, $servers->{static::RABBIT_MQ_SERVER}->{'port'}
				, $servers->{static::RABBIT_MQ_SERVER}->{'user'}
				, $servers->{static::RABBIT_MQ_SERVER}->{'pass'}
			);
			$channel = $connection->channel();
			$channel->queue_declare(
				get_called_class()
				, static::QUEUE_PASSIVE
				, static::QUEUE_DURABLE
				, static::QUEUE_EXCLUSIVE
				, static::QUEUE_AUTO_DELETE
			);
			register_shutdown_function(function() use($connection, $channel){
				$channel->close();
				$connection->close();
			});
			static::$channel = $channel;
		}
		return static::$channel;
	}
	public static function listen()
	{
		$callback = [get_called_class(), 'manageReciept'];
		$channel = static::getChannel(get_called_class());
		$channel->basic_consume(
			get_called_class()
			, ''
			, static::CHANNEL_LOCAL
			, static::CHANNEL_NO_ACK
			, static::CHANNEL_EXCLUSIVE
			, static::CHANNEL_WAIT
			, $callback
		);
		while(count($channel->callbacks))
		{
			$channel->wait();
		}
	}
}