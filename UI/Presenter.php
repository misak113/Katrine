<?php

namespace Katrine\UI;

use Nette\Config\Helpers;

/**
 * Presenter obohacující původní o potřebné služby
 */
class Presenter extends \Nette\Application\UI\Presenter {

    /**
     * @var \Katrine\KinqModules\Hook\IHookContainer kontejner pro háčkování událostí
     */
    protected $modulesHook;

    /**
     * V konstruktoru se přidají služby
     * @param \Nette\DI\IContainer $context context aplikace
     */
    public function __construct(\Nette\DI\IContainer $context) {
	parent::__construct($context);
	$this->modulesHook = $context->hasService('kinqModulesExtension.modulesHook') ?$context->getService('kinqModulesExtension.modulesHook') :null;
    }

    	/**
	 * Formats view template file names.
	 * Naloaduje správné cesty k šablonám pro kinqModules
	 * @return array
	 */
	public function formatTemplateFiles()
	{
		$name = $this->getName();
		$exp = explode('\\', $name);
		$presenter = end($exp);
		$dir = dirname(dirname($this->getReflection()->getFileName()));
		$templates = array(
			"$dir/templates/$presenter/$this->view.latte",
			"$dir/templates/$presenter.$this->view.latte",
			"$dir/templates/$presenter/$this->view.phtml",
			"$dir/templates/$presenter.$this->view.phtml",
		);
		return Helpers::merge($templates, parent::formatTemplateFiles());
	}
}

?>
