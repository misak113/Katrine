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
	,  \Katrine\KinqModules\Event;

class HookContainer extends Nette\Object implements \Katrine\KinqModules\Hook\IHookContainer {

    const MODULES_NAMESPACE = 'App\\Modules\\';

    private $registry = array();

    
    /** @var \SystemContainer */
    private $context;
    protected $config;

    public function __construct(Nette\DI\IContainer $context, array $config) {
	$this->context = $context;
	$this->config = $config;
	$modules = $config['kinqModules'];
	$this->loadModules($modules);
    }

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




    
    public function bind($eventType, $function, $args = array()) {
	$event = $this->context->getService('kinqModulesExtension.eventFactory')->event($eventType, $function, $args);
	$name = $event->getEventType();
	if (!isset($this->registry[$name]))
	    $this->registry[$name] = self::buildStorage();
	$this->registry[$name]->attach($event);
	return;
    }

    /**
     *
     * @return void
     */
    public function unbind($object, $event = null) {

	//unbind from one event
	if ($event && isset($this->registry[$event]))
	    return $this->registry[$event]->detach($object);

	//unbind from all events
	foreach ($this->registry as $event) {
	    $event->detach($object);
	}
    }

    public function notify($eventType, $args) {
	$event = $this->context->getService('kinqModulesExtension.eventFactory')->notification($eventType, $args);
	$name = $event->getEventType();
	if (!isset($this->registry[$name]))
	    return new SplObjectStorage();
	foreach ($this->registry[$name] as  $notifiedEvent) {
	    $args = $notifiedEvent->getValidArgs($event);
	    call_user_func_array(
		    $notifiedEvent->getCallback(),
		    $args
	    );
	}
    }







    protected function loadEventTypes($origModuleName, $eventTypes) {
	$eventTypesParams = array();
	$configEventTypes = isset($this->config['kinqModules'.$origModuleName]['eventTypes']) ?$this->config['kinqModules'.$origModuleName]['eventTypes'] :array();
	foreach ($eventTypes as $type) {
	    $eventTypesParams[$type] = isset($configEventTypes[$type]) ?array(
		'argTypes' => $configEventTypes[$type],
	    ) :false;
	}
	return $eventTypesParams;
    }

    protected static function buildStorage() {
	return new SplObjectStorage();
    }

    protected function addEventTypes($eventTypes) {
	if (!is_array($eventTypes)) {
	    $eventTypes = array();
	}
	$this->context->getService('kinqModulesExtension.eventFactory')->addEventTypes($eventTypes);
    }


}