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
    kinq;

class HookContainer extends Nette\Object implements \Katrine\KinqModules\Hook\IHookContainer {

    const MODULES_NAMESPACE = 'App\\Modules\\';

    private $registry = array();

    /** @var Nette\DI\IContainer */
    private $context;
    protected $eventTypes = array();
    protected $options = array(
	'binder' => null,
	'args' => null
    );

    public function __construct(Nette\DI\IContainer $context, array $modules, array $eventTypes) {
	$this->context = $context;
	$this->setEventTypes($eventTypes);
	$this->loadModules($modules);
    }

    protected function loadModules($modules) {
	foreach ($modules as $moduleName) {
	    //$moduleNameParse = explode('.',$moduleName);
	    $moduleName = self::MODULES_NAMESPACE.str_replace('.', '\\', $moduleName);//end($moduleNameParse);
	    if (!preg_match('~Module$~', $moduleName)) {
		$moduleName = $moduleName . 'Module';
	    }
	    try {
		$moduleName::setupHooks($this);
		$moduleName::setupRouter($this->context->getService('router'));
	    } catch (Exception $e) {
		_d('Module "' . $moduleName . '" doesn\'t exists.');
	    }
	}
    }

    public function bind($event, $callback, $args = array()) {
	if (!key_exists($event, $this->eventTypes)) {
	    throw new InvalidArgumentException("Event name '$event' is not supported event type");
	}
	//$func = new \ReflectionFunction($callback->getNative());
	//$types = self::getFunctionParametersTypesByParameters($func->getParameters());
	//self::checkEventTypes($types, $event);
	if (!isset($this->registry[$event]))
	    $this->registry[$event] = self::buildStorage();

	if (is_string($callback))
	    $callback = new Callback($callback);

	if (get_class($callback) !== 'Nette\Callback')
	    throw new InvalidArgumentException("Callback '$callback' is not Nette\callback or string");

	if (!$callback->isCallable())
	    throw new InvalidStateException("Callback '$callback' is not callable.");

	$options = array(
	    'caller' => null,
	    'args' => $args
	);
	list(, $options['caller']) = debug_backtrace(false);
	$this->registry[$event]->attach($callback, $options);
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

    public function notify($event, array $options = array()) {
	//list(, $caller) = debug_backtrace(false);
	$types = self::getFunctionParametersTypesByArgs($options);
	self::checkEventTypes($types, $event);

	if (!isset($this->registry[$event]))
	    return new SplObjectStorage();
	foreach ($this->registry[$event] as $object) {
	    call_user_func_array($object, $options);
	}
    }







    protected function checkEventTypes($types, $event) {
	foreach ($this->eventTypes[$event] as $i => $arg) {
	    if ($types[$i] != $arg && $types[$i] !== false) {
		throw new InvalidArgumentException("Event want type '$arg' in parameter $i, but '".$types[$i]."' given");
	    }
	}
    }


    protected static function buildStorage() {
	return new SplObjectStorage();
    }

    protected function setEventTypes(array $eventTypes) {
	$this->eventTypes = $eventTypes;
    }

    protected static function getFunctionParametersTypesByArgs($paramArgs) {
	$types = array();
	foreach ($paramArgs as $arg) {
	    $types[] = get_class($arg);
	}
	return $types;
    }

    protected static function getFunctionParametersTypesByParameters($params) {
	foreach ($params as $param) {
	    $paramTypes[] = $param->getName();
	}
	return $paramTypes;
    }

}