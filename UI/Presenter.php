<?php

namespace Katrine\UI;

class Presenter extends \Nette\Application\UI\Presenter {

    protected $modulesHook;

    public function __construct(\Nette\DI\IContainer $context) {
	parent::__construct($context);
	$this->modulesHook = $this->getService('kinqModulesExtension.modulesHook');
    }

    	/**
	 * Formats view template file names.
	 * @return array
	 */
	public function formatTemplateFiles()
	{
		$name = $this->getName();
		$exp = explode('\\', $name);
		$presenter = end($exp);
		//$presenter = substr($name, strrpos(':' . $name, ':'));
		$dir = dirname(dirname($this->getReflection()->getFileName()));
		return array(
			"$dir/templates/$presenter/$this->view.latte",
			"$dir/templates/$presenter.$this->view.latte",
			"$dir/templates/$presenter/$this->view.phtml",
			"$dir/templates/$presenter.$this->view.phtml",
		);
	}
}

?>
