<?php
namespace Katrine\KinqModules;
use Nette\Object;

class EventFactory extends Object {

    protected $eventTypes = array();

    public function event($event, $callbackFunction = false, $args = array()) {
	return new Event($event, $callbackFunction, $args, $this);
    }

    public function notification($event, $args = array()) {
	return new Event($event, false, $args, $this);
    }


    public function addEventTypes(array $eventTypes) {
	foreach ($eventTypes as $eventType => $eventParams) {
	    $this->eventTypes[$eventType] = $eventParams;
	}
    }

    public function getEventTypes() {
	return $this->eventTypes;
    }


}

?>
