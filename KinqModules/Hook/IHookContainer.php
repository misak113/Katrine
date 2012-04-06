<?php

namespace Katrine\KinqModules\Hook;
use Nette;

interface IHookContainer
{
	
	public function bind($eventType, $function, $args);

	public function unbind($object, $event = null);
	
	public function notify($eventType, $args);
}