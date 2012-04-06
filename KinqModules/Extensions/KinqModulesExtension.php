<?php

namespace Katrine\KinqModules\Extensions;

use \Nette\Config;

/**
 * Třída rozšiřující Nette o kinqModules
 */
class KinqModulesExtension extends \Nette\Config\CompilerExtension {

    /**
     * @var array defaultní nastavení config
     */
    public $defaults = array(
	'kinqModules' => array(),
	'kinqModulesEvents' => array(),
    );

    /** @var \Nette\DI\ContainerBuilder */
    protected $container;
    /** @var array konfigurace */
    protected $config;

    /**
     * Načtení konfigurace
     */
    public function loadConfiguration() {
	$this->container = $this->getContainerBuilder();
	$this->config = $this->getConfig($this->defaults);

	$this->loadModuleConfigs($this->config['kinqModules']);

	$this->createHookService();
	$this->createPresenterFactory();

	//$this->setConfig($this->config);
    }

    /**
     * Vytvoření potřebných servisů
     */
    protected function createHookService() {

	$this->container
		->addDefinition($this->prefix('modulesHook'))
		->setAutowired(true)
		->addTag('modulesHook')
		->setShared(true)
		->setFactory('Katrine\KinqModules\Hook\HookContainer', array(
		    $this->container,
		    $this->config,
		));
	$this->container
		->addDefinition($this->prefix('eventFactory'))
		->setAutowired(true)
		->addTag('eventFactory')
		->setShared(true)
		->setFactory('Katrine\KinqModules\EventFactory');
    }

    /**
     * naloaduje konfigurační soubory pro jednotlivé moduly
     * @param array $modules názvy modulů
     */
    protected function loadModuleConfigs(array $modules) {
	$loader = $this->createLoader();
	foreach ($modules as $module) {
	    $path = $this->formatConfigFile($module);
	    try {
		$conf = Config\Helpers::merge($loader->load($path, $this->container->parameters['environment']), $this->config);
		$this->config = $conf;
	    } catch (\Nette\FileNotFoundException $e) {
		// v pohodě když není konfigurák
		continue;
	    }
	}
    }

    /**
     * Zjišťuje adresu configu pro daný modul
     * @param string $module název modulu
     * @return string cesta ke konfiguračnímu souboru
     */
    public function formatConfigFile($module) {
	$path = APP_DIR . '/Modules/' . $module . '/config/config.neon';
	return $path;
    }

    /**
     * Zamění původní presenter factory za factory pro kinqModules
     */
    protected function createPresenterFactory() {
	$this->container->removeDefinition('nette.presenterFactory');
	$this->container->addDefinition($this->prefix('presenterFactory'))
		->setClass('Katrine\Application\PresenterFactory', array(
		    isset($this->container->parameters['appDir']) ? $this->container->parameters['appDir'] : NULL
		));
    }

    /**
     * Po zkompilování přidá danou službu do SystemContainer
     * @param \Nette\Utils\PhpGenerator\ClassType $class
     */
    public function afterCompile(\Nette\Utils\PhpGenerator\ClassType $class) {
	$initialize = $class->methods['initialize'];

	$initialize->addBody('$this->getService(?);', array($this->prefix('modulesHook')));
    }

    /**
     * Vytvoří loader pro loadování configů
     * @return Loader
     */
    protected function createLoader() {
	return new Config\Loader();
    }

}

?>
