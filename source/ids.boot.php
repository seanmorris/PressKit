<?php
$listeners = \SeanMorris\Ids\Linker::classes('SeanMorris\\PressKit\\Listener');

if($listeners)
{
	foreach($listeners as $listener)
	{
		$listener::listen();
	}
}
