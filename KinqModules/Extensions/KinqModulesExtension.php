<?php

namespace Katrine\KinqModules\Extensions;

class KinqModulesExtension extends \Nette\Config\CompilerExtension {

    public $defaults = array(
	'kinqModules' => array(),
	'kinqModulesEvents' => array(),
    );

    /** @var \Nette\DI\ContainerBuilder */
    protected $container;

    public function loadConfiguration() {
	$this->container = $this->getContainerBuilder();
	$config = $this->getConfig($this->defaults);

	$this->createHookService($config);
	$this->createPresenterFactory();
    }



    protected function createHookService($config) {
	
	$this->container
		->addDefinition($this->prefix('modulesHook'))
		->setAutowired(true)
		->addTag('modulesHook')
		->setShared(true)
		->setFactory('Katrine\KinqModules\Hook\HookContainer', array(
		    $this->container,
		    $config['kinqModules'],
		    $config['kinqModulesEvents']
		));
	    //->addSetup('Katrine\KinqModules\Hook\HookContainer');

    }

    protected function createPresenterFactory() {
	$this->container->removeDefinition('nette.presenterFactory');
	$this->container->addDefinition($this->prefix('presenterFactory'))
		->setClass('Katrine\Application\PresenterFactory', array(
		    isset($this->container->parameters['appDir']) ? $this->container->parameters['appDir'] : NULL
		));
    }

    public function afterCompile(\Nette\Utils\PhpGenerator\ClassType $class) {
	$initialize = $class->methods['initialize'];

	$initialize->addBody('$this->getService(?);', array($this->prefix('modulesHook')));
    }

}

?>
