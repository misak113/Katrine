<?php

namespace Katrine\KinqModules;

use \Nette\Object
, \Nette\Application\IRouter
, \Nette\Security\IAuthorizator
, Katrine\KinqModules\Hook\IHookContainer
, \Nette\Application\Routers\Route;

/**
 * Abstraktní třída pro každý modul z kinqModules
 */
abstract class BaseModule extends Object {



	const ROUTE_TRANSLATION = 'ROUTE_TRANSLATION';

	/**
	 * @var array Výčet možných povolených událostí daného modulu
	 */
	public static $events = array();

	public static function setupRouter(IRouter $router) { }

	public static function setupPermission(IAuthorizator $permission) { }

	public static function setupHooks(IHookContainer $hook) { }

	public static function setupEvents(array $events) { }


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
			    Route::VALUE => 'cs',
				'fixity' => Route::CONSTANT,
			);
		}

		$new_route = new Route($mask, $metadata);

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

}
?>
