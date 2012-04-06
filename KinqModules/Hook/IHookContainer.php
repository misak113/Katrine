<?php

namespace Katrine\KinqModules\Hook;
use Nette;

interface IHookContainer
{
	
	public function bind($event, $function, array $args);

	public function unbind($event);
	
	public function notify($eventType, array $args);
}