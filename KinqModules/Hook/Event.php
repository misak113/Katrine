<?php

namespace Katrine\KinqModules;
use \Nette\InvalidStateException
, \Nette\InvalidArgumentException;


class Event extends \Nette\Object {

    
    /**
     *
     * @var \Nette\Callback
     */
    protected $callback = false;
    protected $eventType,  $options = false, $eventFactory;

    public function __construct($eventType, $callbackFunction, $args, $eventFactory) {
	$this->eventFactory = $eventFactory;
	$this->setEventType($eventType);
	if ($callbackFunction !== false)
	    $this->setCallbackFunction($callbackFunction);
	$this->setOptionArgs($args);
    }

    



    public function getEventType() {
	return $this->eventType;
    }

    public function getCallback() {
	return $this->callback;
    }

    public function getOptions($param = false) {
	if ($param === false) {
	    return $this->options;
	}
	if (isset($this->options[$param])) {
	    return $this->options[$param];
	}
	throw new \InvalidArgumentException("Zadaný parametr '$param' není v nastavení 'Event->options'");
    }


    public function getValidArgs(self $event) {
	$allArgs = array_merge(
			    $event->getOptions('args'),
			    $this->getOptions('args')
		    );
	$allArgTypes = array();
	foreach ($allArgs as $arg) {
	    $allArgTypes[] = get_class($arg);
	}
	$args = array();
	$eventTypes = $this->eventFactory->getEventTypes();
	$argTypes = isset($eventTypes[$this->eventType]['argTypes']) ?$eventTypes[$this->eventType]['argTypes'] :false;
	if (!$argTypes) {
	    $args = $allArgs;
	} else {
	    foreach ($argTypes as $type) {
		foreach ($allArgs as $arg) {
		    if ($arg instanceof $type) {
			$args[] = $arg;
			continue 2;
		    }
		}
		//$args[] = null;
		throw new \InvalidArgumentException(
			"Konkrétní notifikace událostí s typem '".$this->getEventType()
			."' neobsahuje požadované parametry ".implode(', ', $argTypes)
			." Obdrženy parametry '".implode(', ', $allArgTypes)."'"
		);
	    }
	}
	return $args;
    }

    
    

    public function setEventType($eventType) {
	if (!key_exists($eventType, $this->eventFactory->getEventTypes())) {
	    throw new InvalidArgumentException("Event name '$eventType' is not supported event type");
	}
	$this->eventType = $eventType;
    }






    
    protected function setCallbackFunction($function) {
	
	//$func = new \ReflectionFunction($callback->getNative());
	//$types = self::getFunctionParametersTypesByParameters($func->getParameters());
	//self::checkEventTypes($types, $event);
	
	if (is_string($function))
	    $function = new \Nette\Callback($function);
	if (is_callable($function))
	    $function = new \Nette\Callback($function);

	if (get_class($function) !== 'Nette\Callback')
	    throw new InvalidArgumentException("Callback '$function' is not Nette\callback, string or function");

	if (!$function->isCallable())
	    throw new InvalidStateException("Callback '$function' is not callable.");

	$this->callback = $function;
    }
    protected function setOptionArgs(array $args = array()) {
	$options = array(
	    'caller' => null,
	    'args' => $args
	);
	list(, $options['caller']) = debug_backtrace(false);
	$this->options = $options;
    }

    protected function checkEventTypes($types, $event) {
	$eventTypes = $this->eventFactory->getEventTypes();
	foreach ($eventTypes[$event] as $i => $arg) {
	    if ($types[$i] != $arg && $types[$i] !== false) {
		throw new InvalidArgumentException("Event want type '$arg' in parameter $i, but '".$types[$i]."' given");
	    }
	}
    }















    /*protected static function getFunctionParametersTypesByArgs(array $paramArgs) {
	$types = array();
	foreach ($paramArgs as $arg) {
	    $types[] = get_class($arg);
	}
	return $types;
    }*/

    /**
     * Vrátí typy parametrů z parametrů funkce (Parameters Reflection)
     *
     * @param array $params parametry (Parameters Reflection)
     * @return array typy parametrů
     */
    protected static function getFuncParams(array $params) {
	foreach ($params as $param) {
	    $paramTypes[] = $param->getName();
	}
	return $paramTypes;
    }


}

?>
