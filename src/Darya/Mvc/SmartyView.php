<?php
namespace Darya\Mvc;

use Smarty;
use Darya\Mvc\View;

/**
 * Darya's Smarty templating view.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class SmartyView extends View {
	
	/**
	 * @var Smarty
	 */
	protected $smarty;
	
	/**
	 * @var array Variables for configuring the view
	 */
	protected $config = array(
		'base'    => '',
		'cache'   => 'temp/views_cache',
		'compile' => 'temp/views_compiled',
		'plugins' => 'plugins'
	);
	
	/**
	 * @var array Variables to assign to the template
	 */
	protected $vars = array(
		'template' => array(
			'css' => array(),
			'js' => array()
		)
	);
	
	/**
	 * Instantiate a new Template object.
	 * 
	 * @param string $file   [optional] Path to the template file to use
	 * @param array  $vars   [optional] Variables to assign to the template
	 * @param array  $config [optional] Configuration variables for the view
	 */
	public function __construct($file = null, $vars = array(), $config = array()){
		$this->smarty = new Smarty;
		$this->smarty->error_reporting = E_ALL & ~E_NOTICE & ~E_WARNING;
		
		parent::__construct($file, $vars, $config);
	}
	
	/**
	 * Set the template's working directory.
	 * 
	 * @param string $dir Template directory
	 */
	protected function setDir($dir) {
		if ($dir != $this->dir) {
			parent::setDir($dir);
			
			$base = isset($this->config['base']) ? $this->config['base'] : $this->dir;
			
			$this->smarty->setTemplateDir($this->dir)
						 ->setCompileDir($base.'/'.$this->config['compile'])
						 ->setCacheDir($base.'/'.$this->config['cache'])
						 ->addPluginsDir($base.'/'.$this->config['plugins']);
		}
	}
	
	/**
	 * Render the template.
	 * 
	 * @return string The result of the rendered template, false if it fails.
	 */
	public function render() {
		$template = $this->dir . '/' . $this->file;
		
		if (is_file($template)) {
			$this->smarty->assign(static::$shared);
			$this->smarty->assign($this->vars);
			return $this->smarty->fetch($template, $this->dir, $this->dir);
		} else {
			throw new \Exception("Could not find template \"$template\"");
			// return "Could not find template \"$template\" when rendering<br/>";
		}
		
		return false;
	}
	
	/**
	 * Assign a JavaScript file to the template
	 * 
	 * @param string $file Url to a JavaScript file
	 */
	public function js($file) {
		if ($file) {
			array_push($this->vars['template']['js'], $file);
		}
	}
	
	/**
	 * Assign a CSS file to the template
	 * 
	 * @param string $file Url to a CSS file
	 */
	public function css($file) {
		if ($file) {
			array_push($this->vars['template']['css'], $file);
		}
	}
	
}
