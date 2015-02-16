<?php
namespace Darya\Mvc;

use Darya\Mvc\ViewInterface;
use Darya\Mvc\ViewResolver;

/**
 * Darya's base functionality for MVC views.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
abstract class View implements ViewInterface {
	
	/**
	 * @var string Optional shared base path for selecting template files
	 */
	protected static $basePath;
	
	/**
	 * @var array Set of template file extensions compatible with this view 
	 */
	protected static $extensions = array();
	
	/**
	 * @var array Variables to assign to all templates
	 */
	protected static $shared = array();
	
	/**
	 * @var \Darya\Mvc\ViewResolver Shared resolver for selecting template files
	 */
	protected static $sharedResolver;
	
	/**
	 * @var \Darya\Mvc\ViewResolver Instance resolver for selecting template files
	 */
	protected $resolver;
	
	/**
	 * @var array Variables for configuring the view
	 */
	protected $config = array();
	
	/**
	 * @var string Path to the directory containing the view template
	 */
	protected $dir;
	
	/**
	 * @var string Filename of the view template
	 */
	protected $file;
	
	/**
	 * @var array Variables to assign to the template
	 */
	protected $vars = array();
	
	/**
	 * Set a shared base path for selecting template files
	 * 
	 * @param string $path
	 */
	public static function setBasePath($path) {
		$path = realpath($path);
		
		if (is_dir($path)) {
			static::$basePath = $path;
		}
	}
	
	/**
	 * Register template file extensions.
	 * 
	 * @param string|array $extensions
	 */
	public static function registerExtensions($extensions) {
		$newExtensions = array();
		
		foreach ((array) $extensions as $extension) {
			$extension = '.' . ltrim(trim($extension), '.');
			$newExtensions[] = $extension;
		}
		
		static::$extensions = array_merge(static::$extensions, $newExtensions);
	}
	
	/**
	 * Instantiate a new View object.
	 * 
	 * @param string $file   [optional] Path to the template file to use
	 * @param array  $vars   [optional] Variables to assign to the template
	 * @param array  $config [optional] Configuration variables for the view
	 */
	public function __construct($file = null, $vars = array(), $config = array()) {
		$this->select($file, $vars, $config);
	}
	
	/**
	 * Evaluate the template as a string by rendering it.
	 * 
	 * @return string
	 */
	public function __toString() {
		return $this->render();
	}

	/**
	 * Sets a ViewResolver for all views.
	 * 
	 * @param \Darya\Mvc\ViewResolver $resolver
	 */
	public static function setSharedResolver(ViewResolver $resolver) {
		static::$sharedResolver = $resolver;
	}
	
	/**
	 * Sets a ViewResolver for this view.
	 * 
	 * @param \Darya\Mvc\ViewResolver $resolver
	 */
	public function setResolver(ViewResolver $resolver) {
		$this->resolver = $resolver;
	}
	
	/**
	 * Select a template, optionally assigning variables and config values.
	 * 
	 * @param string $file 	 The template file to be used
	 * @param array  $vars 	 [optional] Variables to assign to the template immediately
	 * @param array  $config [optional] Config variables for the view
	 */
	public function select($file, array $vars = array(), array $config = array()) {
		if (!empty($config)) {
			$this->setConfig($config);
		}
		
		if (!empty($vars)) {
			$this->assign($vars);
		}
		
		if ($file) {
			$this->setFile($file);
		}
	}
	
	/**
	 * Attempt to load a template file at the given absolute path.
	 * 
	 * @param string $path Absolute file path
	 * @return bool
	 */
	protected function attempt($path) {
		$path = realpath($path);
		
		if ($path && is_file($path)) {
			$dirname = dirname($path);
			$this->setDir($dirname != '.' ? $dirname : '');
			$this->file = basename($path);
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Attempt to resolve the given view path to a file path using the view's
	 * resolver or that class's shared resolver.
	 * 
	 * @param string $path
	 * @return string
	 */
	protected function resolve($path) {
		if ($this->resolver) {
			return $this->resolver->resolve($path);
		} else if (static::$sharedResolver) {
			return static::$sharedResolver->resolve($path);
		}
		
		return null;
	}
	
	/**
	 * Find and set a template file using a given path. Attempts with the shared
	 * base path and extensions.
	 * 
	 * @param string $path Path to template file
	 * @return bool
	 */
	public function setFile($path) {
		$paths = array();
		
		$paths[] = $this->resolve($path);
		
		$extensions = array_merge(array(''), static::$extensions);
		
		foreach ($extensions as $extension) {
			if (static::$basePath) {
				$paths[] = static::$basePath . "/$path$extension";
			}
			
			$paths[] = "$path$extension";
		}
		
		foreach ($paths as $p) {
			if ($this->attempt($p)) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Set the template's working directory.
	 * 
	 * @param string $dir Template directory
	 */
	protected function setDir($dir) {
		$this->dir = $dir;
	}
	
	/**
	 * Get view configuration variables.
	 * 
	 * @return array
	 */
	public function getConfig() {
		return $this->config;
	}
	
	/**
	 * Set view configuration variables. This merges with any previously set.
	 * 
	 * @param array $config
	 */
	public function setConfig(array $config) {
		$this->config = array_merge($this->config, $config);
	}

	/**
	 * Assign an array of key/value pairs to the template.
	 * 
	 * @param array $vars
	 */
	public function assign(array $vars = array()) {
		if (is_array($vars)) {
			$this->vars = array_merge($this->vars, $vars);
		}
	}
	
	/**
	 * Get all variables or a particular variable assigned to the template.
	 * 
	 * @param string $key Key of a variable to return
	 * @return mixed The value of variable $key if set, all variables otherwise
	 */
	public function getAssigned($key = null) {
		return !is_null($key) && isset($this->vars[$key]) ? $this->vars[$key] : $this->vars;
	}
	
	/**
	 * Assign an array of key/value pairs to all templates.
	 * 
	 * @param array $vars
	 */
	public static function share(array $vars) {
		static::$shared = array_merge(static::$shared, $vars);
	}
	
	/**
	 * Get all variables or a particular variable shared to all templates.
	 * 
	 * @param string $key Key of a variable to return
	 * @return mixed The value of variable $key if set, all variables otherwise
	 */
	public static function getShared($key = null) {
		return !is_null($key) && isset(static::$shared[$key]) ? static::$shared[$key] : static::$shared;
	}

}
