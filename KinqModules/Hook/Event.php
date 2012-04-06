<?php

namespace Katrine\KinqModules;
use \Nette\InvalidStateException
, \Nette\InvalidArgumentException
, \Nette\Object;

/**
 * Třída události, která obsahuje data o dané události z kinqModules Hook
 */
class Event extends Object {

    
    /**
     * @var \Nette\Callback
     */
    protected $callback = false;
    protected $eventType,  $options = false, $eventFactory;

    /**
     * Vytvoří událost
     * @param string $eventType typ události
     * @param mixed $callbackFunction callback funkce události
     * @param array $args argumenty přidávané k volání callback funkce
     * @param EventFactory $eventFactory továrna která vytvořila tento event
     */
    public function __construct($eventType, $callbackFunction, array $args, EventFactory $eventFactory) {
	$this->eventFactory = $eventFactory;
	$this->setEventType($eventType);
	if ($callbackFunction !== false)
	    $this->setCallbackFunction($callbackFunction);
	$this->setOptionArgs($args);
    }

    



    /**
     * Vrací typ události
     * @return string typ události
     */
    public function getEventType() {
	return $this->eventType;
    }

    /**
     * Vrací callback události
     * @return \Nette\Callback callback události
     */
    public function getCallback() {
	return $this->callback;
    }

    /**
     * Vrací nastavení události
     * Vrací jednu položku pokud je zadán název položky
     * @param string $param název položky nastavení
     * @return mixed vrací danné nastavení
     */
    public function getOptions($param = false) {
	if ($param === false) {
	    return $this->options;
	}
	if (isset($this->options[$param])) {
	    return $this->options[$param];
	}
	throw new \InvalidArgumentException("Zadaný parametr '$param' není v nastavení 'Event->options'");
    }


    /**
     * Vrací validní argumenty pro zadanou notifikaci dle nastavení v EventFactory
     * @param self $event notifikacekterá spouští danou událost
     * @return array seznam parametrů ve správném pořadí, pokud jsou nastaveny požadované argumenty v configu
     */
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

    
    

    /**
     * Nastaví typ události
     * @param string $eventType typ události
     */
    protected function setEventType($eventType) {
	if (!key_exists($eventType, $this->eventFactory->getEventTypes())) {
	    throw new InvalidArgumentException("Event name '$eventType' is not supported event type");
	}
	$this->eventType = $eventType;
    }






    /**
     * Nastaví callback funkci podle funkce, názvu funkce nebo callbacku
     * @param string|\Nette\Callback|\Closure $function callback funkce
     */
    protected function setCallbackFunction($function) {
	
	//$func = new \ReflectionFunction($callback->getNative());
	//$types = self::getFunctionParametersTypesByParameters($func->getParameters());
	//self::checkEventTypes($types, $event);
	
	if (is_string($function))
	    $function = callback($function);
	if (is_callable($function))
	    $function = callback($function);

	if (get_class($function) !== 'Nette\Callback')
	    throw new InvalidArgumentException("Callback '$function' is not Nette\callback, string or function");

	if (!$function->isCallable())
	    throw new InvalidStateException("Callback '$function' is not callable.");

	$this->callback = $function;
    }
    /**
     * Nastaví přídavné argumenty volané k funkci callbacku
     * @param array $args argumenty k callback funkci
     */
    protected function setOptionArgs(array $args = array()) {
	$options = array(
	    'caller' => null,
	    'args' => $args
	);
	list(, $options['caller']) = debug_backtrace(false);
	$this->options = $options;
    }

}

?>
