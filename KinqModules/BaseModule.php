<?php

namespace Katrine\KinqModules;

abstract class BaseModule extends \Nette\Object {



	const ROUTE_TRANSLATION = 'ROUTE_TRANSLATION';

	private $namespace;

	public static function setupRouter(\Nette\Application\IRouter $router) { }

	public static function setupPermission(\Nette\Security\IAuthorizator $permission) { }

	public static function setupHooks(Hook\IHookContainer $hook) {

	}

	public function setupEvents(kinq\EventContainer $events) {
		//
		//$events[] = new kinq\Event($this, 'order.createPayment', array('@Nette\Application\Application'));
	}


	/**
	 * @static
	 * @param \Nette\Application\IRouter $route
	 * @return \Nette\Application\IRouter
	 */
	protected static function createRouter($mask, $metadata) {

		//add lang mask if omitted
		if (strpos($mask, '<lang') === false) {
			//$mask = "[!<lang [a-z]{2,3}>/]$mask";
		}

		//add lang default if omitted
		if (is_array($metadata) && !isset($metadata['lang'])) {
			$metadata['lang'] = array(
			    \Nette\Application\Routers\Route::VALUE => 'cs',
				'fixity' => \Nette\Application\Routers\Route::CONSTANT,
			);
		}

		$new_route = new \Nette\Application\Routers\Route($mask, $metadata);

		foreach($metadata as $part => $value) {
			if (is_array($value) && in_array(self::ROUTE_TRANSLATION, $value))
				self::addTranslationFilter($new_route, $part);
		}
        return $new_route;
	}

	protected static function addTranslationFilter(IRouter &$route, $urlPart) {
		$route->addFilter($urlPart, '\kinq\Appication\Routers\UrlResolve::urlToPresenter', '\kinq\Appication\Routers\UrlResolve::presenterToUrl');
		return $route;
	}

	public function setNamespace($namespace) {
		$this->namespace;
	}
}
?>
