<?php
namespace Katrine\KinqModules;
use Nette\Object;

/**
 * Třída továrny tovřící události kinqModules
 */
class EventFactory extends Object {

    /**
     * Seznam povolených typů událostí
     * @var array povolené typy událostí
     */
    protected $eventTypes = array();

    /**
     * Vytvoří novou událost
     * @param string $event typ události
     * @param \Nette\Callback|string|\Closure $callbackFunction callback funkce
     * @param array $args argumenty doplňující argumenty callback funkce události
     * @return Event vytvořená událost
     */
    public function event($event, $callbackFunction = false, array $args = array()) {
	return new Event($event, $callbackFunction, $args, $this);
    }

    /**
     * Vytvoří novou notifikace událostí
     * @param string $event typ události
     * @param array $args argumenty předané událostem
     * @return Event Vytvořená notifikace
     */
    public function notification($event, array $args = array()) {
	return new Event($event, false, $args, $this);
    }

    /**
     * Přidá do seznamu povolené typy událostí s předpoklady argumentů
     * Příklad:
     *	array(
     *	    'menu' => array('\Nette\Object', '\Katrine\UI\Presenter'),
     *	)
     * @param array $eventTypes povolené typy událostí
     */
    public function addEventTypes(array $eventTypes) {
	foreach ($eventTypes as $eventType => $eventParams) {
	    if ((!is_array($eventParams) && !is_bool($eventParams))) {
		throw new \InvalidArgumentException('Hodnoty seznamu událostí musí být seznam parametrů nebo false');
	    }
	    $this->eventTypes[$eventType] = $eventParams;
	}
    }

    /**
     * Vrátí všechny povolené typy událostí s jejich předpoklady
     * @return array povolené typy událostí
     */
    public function getEventTypes() {
	return $this->eventTypes;
    }


}

?>
