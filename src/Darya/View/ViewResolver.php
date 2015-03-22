<?php
namespace Darya\Mvc;

/**
 * Finds and instantiates views of the given implementation using the given base
 * paths and extensions.
 * 
 * Optionally shares variables and configurations with all templates that are
 * resolved.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class ViewResolver {
	
	/**
	 * @var string ViewInterface implementor to resolve
	 */
	protected $engine;
	
	/**
	 * @var array Paths to search for templates within
	 */
	protected $basePaths = array();
	
	/**
	 * @var array Template file extensions to search for
	 */
	protected $extensions = array();
	
	/**
	 * @var array Variables to assign to all views that are resolved
	 */
	protected $shared = array();
	
	/**
	 * @var array Config variables to set for all views that are resolved
	 */
	protected $config = array();
	
	/**
	 * Create a new ViewResolver.
	 * 
	 * @param string $engine ViewInterface implementor to resolve
	 * @param string|array [optional] $path Single path or set of paths
	 * @param string|array [optional] $extensions Template file extensions
	 */
	public function __construct($engine, $basePath = null, $extensions = array()) {
		$this->setEngine($engine);
		
		if ($basePath) {
			$this->registerBasePaths($basePath);
		}
		
		if (!empty($extensions)) {
			$this->registerExtensions($extensions);
		}
	}
	
	/**
	 * Set the engine (ViewInterface implementor) to resolve.
	 * 
	 * @param string $engine
	 */
	public function setEngine($engine) {
		if (!class_exists($engine) || !is_subclass_of($engine, 'Darya\Mvc\ViewInterface')) {
			throw new \Exception("View engine $engine does not exist or does not extend Darya\Mvc\ViewInterface");
		}
		
		$this->engine = $engine;
	}
	
	/**
	 * Register a base path or set of base paths to resolve views from.
	 * 
	 * @param string|array $path Single path or set of paths
	 */
	public function registerBasePaths($path) {
		if (is_array($path)) {
			$this->basePaths = array_merge($path, $this->basePaths);
		} else {
			$this->basePaths[] = $path;
		}
	}
	
	/**
	 * Register file extensions to consider when resolving template files.
	 * 
	 * @param string|array $extensions
	 */
	public function registerExtensions($extensions) {
		foreach ((array) $extensions as $extension) {
			$this->extensions[] = '.' . ltrim($extension, '.');
		}
	}
	
	/**
	 * Set variables to assign to all resolved views.
	 * 
	 * @param array $vars
	 */
	public function share(array $vars = array()) {
		$this->shared = array_merge($this->shared, $vars);
	}
	
	/**
	 * Set config variables to set for all resolved views.
	 * 
	 * @param array $config
	 */
	public function shareConfig(array $config = array()) {
		$this->config = array_merge($this->config, $config);
	}
	
	/**
	 * Find a template file using the given path.
	 * 
	 * @param  string $path
	 * @return string
	 */
	public function resolve($path) {
		$path = preg_replace('#[\\\|/]+#', '/', trim($path, '\/'));
		
		if (is_file($path)) {
			return $path;
		}
		
		$dirname = dirname($path);
		$dir = $dirname != '.' ? $dirname : '';
		$file = basename($path);
		
		foreach ($this->basePaths as $basePath) {
			foreach ($this->extensions as $extension) {
				$filePaths = array("$basePath/$dir/views/$file$extension", "$basePath/$path$extension");
				
				foreach ($filePaths as $filePath) {
					if (is_file($filePath)) {
						return $filePath;
					}
				}
			}
		}
	}
	
	/**
	 * Resolve a view instance with a template at the given path, as well as
	 * shared variables and config.
	 * 
	 * @param string $path [optional] Template path
	 * @param array  $vars [optional] Variables to assign to the View
	 * @return ViewInterface
	 */
	public function create($path = null, $vars = array()) {
		$file = $this->resolve($path);
		
		$engine = $this->engine;
		$engine = new $engine($file, array_merge($this->shared, $vars), $this->config);
		$engine->setResolver($this);
		
		return $engine;
	}
	
}