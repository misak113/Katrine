<?php

namespace Katrine\Application;

class PresenterFactory extends \Nette\Application\PresenterFactory implements \Nette\Application\IPresenterFactory {

	/** @var string */
	protected $baseDir;

	/** @var Nette\DI\IContainer */
	protected $context;



	/**
	 * @param  string
	 */
	public function __construct($baseDir, \Nette\DI\IContainer $context)
	{
	    parent::__construct($baseDir, $context);
		$this->baseDir = $baseDir;
		$this->context = $context;
	}



	/**
	 * @param  string  presenter name
	 * @return string  class name
	 * @throws \Nette\Application\InvalidPresenterException
	 */
	public function getPresenterClass(& $name)
	{
		if (isset($this->cache[$name])) {
			list($class, $name) = $this->cache[$name];
			return $class;
		}

		if (!is_string($name) || !\Nette\Utils\Strings::match($name, "#^[a-zA-Z\x7f-\xff][a-zA-Z0-9\x7f-\xff:\.]*$#")) {
			throw new \Nette\Application\InvalidPresenterException("Presenter name must be alphanumeric string, '$name' is invalid.");
		}

		$class = $this->formatPresenterClass($name);

		if (!class_exists($class)) {
			// internal autoloading
			$file = $this->formatPresenterFile($name);
			if (is_file($file) && is_readable($file)) {
				\Nette\Utils\LimitedScope::load($file, TRUE);
			}

			if (!class_exists($class)) {
				throw new \Nette\Application\InvalidPresenterException("Cannot load presenter '$name', class '$class' was not found in '$file'.");
			}
		}

		$reflection = new \Nette\Reflection\ClassType($class);
		$class = $reflection->getName();

		if (!$reflection->implementsInterface('Nette\Application\IPresenter')) {
			throw new \Nette\Application\InvalidPresenterException("Cannot load presenter '$name', class '$class' is not Nette\\Application\\IPresenter implementor.");
		}

		if ($reflection->isAbstract()) {
			throw new \Nette\Application\InvalidPresenterException("Cannot load presenter '$name', class '$class' is abstract.");
		}

		// canonicalize presenter name
		$realName = $this->unformatPresenterClass($class);
		if ($name !== $realName) {
			if ($this->caseSensitive) {
				throw new \Nette\Application\InvalidPresenterException("Cannot load presenter '$name', case mismatch. Real name is '$realName'.");
			} else {
				$this->cache[$name] = array($class, $realName);
				$name = $realName;
			}
		} else {
			$this->cache[$name] = array($class, $realName);
		}

		return $class;
	}

	/**
	 * Formats presenter class name from its name.
	 * @param  string
	 * @return string
	 */
	public function formatPresenterClass($presenter)
	{
		list($module, $presenterName, $parentModule) = self::parsePresenter($presenter);
		if ($module) {
		    $namespace = 'App\\Modules\\' . ($parentModule ? $parentModule.'\\' :'') . $module . '\\' . $presenterName . 'Presenter';
		    return $namespace;
		}
		return parent::formatPresenterClass($presenter);
	}

	protected static function parsePresenter($presenter) {
	    $hasModule = preg_match('~^([^:]+):([^:]+)$~', $presenter, $matches);
	    if ($hasModule) {
		$moduleNameParse = explode('.', $matches[1]);
		$module = end($moduleNameParse);
		unset($moduleNameParse[count($moduleNameParse)-1]);
		$parentModule = implode('.', $moduleNameParse);
	    	$presenterName = $matches[2];
		return array($module, $presenterName, $parentModule);
	    }
	    return array(false, false, false);
	}

		/**
	 * Formats presenter name from class name.
	 * @param  string
	 * @return string
	 */
	public function unformatPresenterClass($class)
	{
	    $hasModule = preg_match('~^App\\\\Modules\\\\(.+)\\\\(.+)Presenter$~', $class, $matches);
	    if ($hasModule) {
		$presenter = /*$matches[1].':'.*/$matches[2];
		return $presenter;
	    }
	    return parent::unformatPresenterClass($class);
	}



	/**
	 * Formats presenter class file name.
	 * @param  string
	 * @return string
	 */
	public function formatPresenterFile($presenter)
	{
		list($module, $presenterName, $parentModule) = self::parsePresenter($presenter);
		if ($module) {
		    $path = '/Modules/'.$parentModule.'.'.$module.'/presenters/'.$presenterName.'Presenter.php';
		    $pres = $this->baseDir . $path;
		    return $pres;
		}
		return parent::formatPresenterFile($presenter);
	}

}

?>
