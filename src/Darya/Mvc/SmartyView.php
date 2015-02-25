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
		'cache'   => 'storage/cache/views',
		'compile' => 'storage/views',
		'plugins' => 'plugins'
	);
	
	/**
	 * @var array Variables to assign to the template
	 */
	protected $vars = array(
		'template' => array(
			'css' => array(),
			'js'  => array()
		)
	);
	
	/**
	 * Instantiate a new Template object.
	 * 
	 * @param string $file   [optional] Path to the template file to use
	 * @param array  $vars   [optional] Variables to assign to the template
	 * @param array  $config [optional] Configuration variables for the view
	 */
	public function __construct($file = null, $vars = array(), $config = array()) {
		$this->smarty = new Smarty;
		$this->smarty->error_reporting = E_ALL & ~E_NOTICE & ~E_WARNING;
		
		parent::__construct($file, $vars, $config);
	}
	
	/**
	 * Get and optionally set the template's working directory.
	 * 
	 * @param string $directory [optional] Working directory path
	 * @return string
	 */
	protected function directory($directory = null) {
		if (!is_null($directory) && $directory != $this->directory) {
			parent::directory($directory);
			
			$base = isset($this->config['base']) ? $this->config['base'] : $this->directory;
			
			$this->smarty->setTemplateDir($this->directory)
						 ->setCacheDir($base . '/' . $this->config['cache'])
						 ->setCompileDir($base . '/' . $this->config['compile'])
						 ->addPluginsDir($base . '/' . $this->config['plugins']);
		}
		
		return $this->directory;
	}
	
	/**
	 * Retrieve the view's smarty instance.
	 * 
	 * @return Smarty
	 */
	public function smarty() {
		return $this->smarty;
	}
	
	/**
	 * Render the template.
	 * 
	 * @return string The result of the rendered template
	 */
	public function render() {
		$template = $this->directory . '/' . $this->file;
		
		if (is_file($template)) {
			$this->smarty->assign(static::$shared);
			$this->smarty->assign($this->vars);
			return $this->smarty->fetch($template, $this->directory, $this->directory);
		} else {
			throw new \Exception("Could not find template when rendering: \"$template\"");
		}
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
