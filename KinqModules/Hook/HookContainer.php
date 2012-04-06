<?php

namespace Katrine\KinqModules\Hook;

use Nette
,
    SplObjectStorage
,
    Nette\Callback
,
    Nette\InvalidStateException
,
    Nette\InvalidArgumentException
,
    kinq
	,  \Katrine\KinqModules\Event
	, Nette\DI\IContainer
	, \Katrine\KinqModules\Hook\IHookContainer
	, Nette\Object;

/**
 * Kontejner pro vytváření a notifikace událostí a jiných háčků
 */
class HookContainer extends Object implements IHookContainer {

    /**
     * namespace prefix Aplikace modulů
     */
    const MODULES_NAMESPACE = 'App\\Modules\\';

    /**
     * @var array uložené události
     */
    private $registry = array();

    
    /** @var \SystemContainer */
    private $context;
    /** @var array nastavení */
    protected $config;

    /**
     * Vytvoření kontejneru
     * @param Nette\DI\IContainer $context context aplikace
     * @param array $config nastavení aplikace
     */
    public function __construct(IContainer $context, array $config) {
	$this->context = $context;
	$this->config = $config;
	$modules = $config['kinqModules'];
	$this->loadModules($modules);
    }

    /**
     * Naloadování zadaných modulů
     * @param array $modules moduly k naloadování
     */
    protected function loadModules($modules) {
	$moduleNames = array();
	foreach ($modules as $moduleName) {
	    $origModuleName = $moduleName;
	    $moduleName = self::MODULES_NAMESPACE.str_replace('.', '\\', $moduleName);
	    if (!preg_match('~Module$~', $moduleName)) {
		$moduleName = $moduleName . 'Module';
	    }
	    $this->addEventTypes($this->loadEventTypes($origModuleName, $moduleName::$events));
	    $modulNames[] = $moduleName;
	}
	foreach ($modulNames as $moduleName) {
	    try {
		$moduleName::setupHooks($this);
		$moduleName::setupRouter($this->context->getService('router'));
	    } catch (Exception $e) {
		_d('Module "' . $moduleName . '" doesn\'t exists.');
	    }
	}
    }




    /**
     * Slouží k zachycení události pokud je zadána přímo Event událost, je zachycena ta
     * @param string $event typ události
     * @param string|array|Callback|\Closure $function callback funkce události
     * @param array $args dodatečné argumenty ke callback funkci (výchozí parametry pokud jsou v configu nastaveny typy vstupních parametrů události)
     * @return Event Instance události, která byla navázána
     */
    public function bind($event, $function = false, array $args = array()) {
	if ($event instanceof Event) {
	    // zkontroluje zda událost má všechny náležitosti
	}
	if (is_string($event)) {
	    if ($function === false) {
		throw new \InvalidArgumentException('Funkce v argumentu musí být Callable, jedna z uvedených v anotaci a musí být zadána');
	    }
	    $event = $this->getEventFactory()->event($event, $function, $args);
	}
	$name = $event->getEventType();
	if (!isset($this->registry[$name])) {
	    $this->registry[$name] = self::buildStorage();
	}
	$this->registry[$name]->attach($event);
	return $event;
    }

    /**
     * Odvázání zadaného typu události, či pouze dané události podle instance události
     * @param string|Event $event pokud je zadán string znamená typ události a jsou odvázány všechny události tohoto typu. Pokud je zadána instance události, je odvázána pouze tato událost
     * @return Event|array vrátí odvázanou událost nebo pole událostí
     */
    public function unbind($event) {

	//unbind from one event
	if ($event instanceof Event) {
	    $this->registry[$event->getEventType()]->detach($event);
	    return $event;
	}

	if (is_string($event)) {
	    $eventName = $event;
	    $events = array();
	    //unbind from all events
	    if (isset($this->registry[$eventName])) {
		foreach ($this->registry[$eventName] as $event) {
		    $events[] = $event;
		}
		$this->registry[$eventName] = array();
	    }
	    return $events;
	}
	
	throw new \InvalidArgumentException('Zadaný parametr musí být buď string s typem události nebo událost Event');
    }

    /**
     * Spustí všechny navázané události zadaného typu usálosti
     * @param string $eventType typ události
     * @param array $args parametry se kterými se spouští události
     * @return array seznam spuštěných událostí
     */
    public function notify($eventType, array $args) {
	$event = $this->getEventFactory()->notification($eventType, $args);
	$name = $event->getEventType();
	if (!isset($this->registry[$name])) {
	    return new SplObjectStorage();
	}
	$events = array();
	foreach ($this->registry[$name] as  $notifiedEvent) {
	    $args = $notifiedEvent->getValidArgs($event);
	    call_user_func_array(
		    $notifiedEvent->getCallback(),
		    $args
	    );
	    $events[] = $notifiedEvent;
	}
	return $events;
    }



    /**
     * Vrátí továrnu na události
     * @return \Katrine\KinqModules\EventFactory továrna na události
     */
    public function getEventFactory() {
	return $this->context->getService('kinqModulesExtension.eventFactory');
    }


    /**
     * Vrátí všechny události daného modulu obohacené o jejich požadované typy z configu
     * @param string $origModuleName název modulu
     * @param array $eventTypes seznam událostí daného modulu
     * @return array seznam události s požadovanými typy (pokud nejsou požadované typ, je hodnota typu události false)
     */
    protected function loadEventTypes($origModuleName, array $eventTypes) {
	$eventTypesParams = array();
	$configEventTypes = isset($this->config['kinqModules'.$origModuleName]['eventTypes']) ?$this->config['kinqModules'.$origModuleName]['eventTypes'] :array();
	foreach ($eventTypes as $type) {
	    $eventTypesParams[$type] = isset($configEventTypes[$type]) ?array(
		'argTypes' => $configEventTypes[$type],
	    ) :false;
	}
	return $eventTypesParams;
    }

    /**
     * Vytvoří uložiště
     * @return SplObjectStorage
     */
    protected static function buildStorage() {
	return new SplObjectStorage();
    }

    /**
     * Přidá do továrny událostí povolené typy událostí
     * @param array $eventTypes povolené typy událostí
     */
    protected function addEventTypes($eventTypes) {
	if (!is_array($eventTypes)) {
	    $eventTypes = array();
	}
	$this->getEventFactory()->addEventTypes($eventTypes);
    }


}