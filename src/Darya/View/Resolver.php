<?php
namespace Darya\View;

/**
 * Finds and instantiates views of the given implementation using the given base
 * paths and file extensions.
 * 
 * Optionally shares variables and configurations with all templates that are
 * resolved.
 * 
 * TODO: $vars -> $variables
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Resolver
{
	/**
	 * View implementor to resolve.
	 * 
	 * @var string
	 */
	protected $engine;
	
	/**
	 * Paths to search for templates within.
	 * 
	 * @var array
	 */
	protected $basePaths = array();
	
	/**
	 * Template file extensions to search for.
	 * 
	 * @var array
	 */
	protected $extensions = array();
	
	/**
	 * Extra directories to search within.
	 * 
	 * @var array
	 */
	protected $directories = array('views');
	
	/**
	 * Variables to assign to all views that are resolved.
	 * 
	 * @var array
	 */
	protected $shared = array();
	
	/**
	 * Config variables to set for all views that are resolved.
	 * 
	 * @var array
	 */
	protected $config = array();
	
	/**
	 * Normalise the given path.
	 * 
	 * @param string $path
	 * @return path
	 */
	public static function normalise($path)
	{
		return preg_replace('~[\\\|/]+~', '/', rtrim($path, '\/'));
	}
	
	/**
	 * Create a new view resolver.
	 * 
	 * @param string       $engine     View implementor to resolve
	 * @param string|array $basePath   [optional] Single path or set of paths
	 * @param string|array $extensions [optional] Template file extensions
	 */
	public function __construct($engine, $basePath = null, $extensions = array())
	{
		$this->setEngine($engine);
		
		if ($basePath) {
			$this->registerBasePaths($basePath);
		}
		
		if (!empty($extensions)) {
			$this->registerExtensions($extensions);
		}
	}
	
	/**
	 * Set the engine (View implementor) to resolve.
	 * 
	 * @param string $engine
	 */
	public function setEngine($engine)
	{
		if (!class_exists($engine) || !is_subclass_of($engine, 'Darya\View\View')) {
			throw new \Exception("View engine $engine does not exist or does not extend Darya\View\View");
		}
		
		$this->engine = $engine;
	}
	
	/**
	 * Register a base path or set of base paths to resolve views from.
	 * 
	 * @param string|array $path Single path or set of paths
	 */
	public function registerBasePaths($path)
	{
		if (is_array($path)) {
			$this->basePaths = array_merge($this->basePaths, $path);
		} else {
			$this->basePaths[] = $path;
		}
	}
	
	/**
	 * Register file extensions to consider when resolving template files.
	 * 
	 * @param string|array $extensions
	 */
	public function registerExtensions($extensions)
	{
		foreach ((array) $extensions as $extension) {
			$this->extensions[] = '.' . ltrim($extension, '.');
		}
	}
	
	/**
	 * Register extra directory names to search within when resolving template
	 * files.
	 * 
	 * 'views' is registered by default.
	 * 
	 * @param string|array $directories
	 */
	public function registerDirectories($directories)
	{
		$directories = (array) $directories;
		
		foreach ($directories as $key => $directory) {
			$directories[$key] = ltrim($directory, '\/');
		}
		
		$this->directories = array_merge($this->directories, $directories);
	}
	
	/**
	 * Set variables to assign to all resolved views.
	 * 
	 * @param array $vars
	 */
	public function share(array $vars = array())
	{
		$this->shared = array_merge($this->shared, $vars);
	}
	
	/**
	 * Set config variables to set for all resolved views.
	 * 
	 * @param array $config
	 */
	public function shareConfig(array $config = array())
	{
		$this->config = array_merge($this->config, $config);
	}
	
	/**
	 * Generate file paths to attempt when resolving template files.
	 * 
	 * @param string $path
	 * @return array
	 */
	public function generate($path)
	{
		$dirname = dirname($path);
		$dir = $dirname != '.' ? $dirname : '';
		$file = basename($path);
		$paths = array();
		
		foreach ($this->basePaths as $basePath) {
			foreach ($this->extensions as $extension) {
				$paths[] = "$basePath/$path$extension";
				
				foreach ($this->directories as $directory) {
					$paths[] = "$basePath/$dir/$directory/$file$extension";
				}
			}
		}
		
		return $paths;
	}
	
	/**
	 * Find a template file using the given path.
	 * 
	 * @param string $path
	 * @return string
	 */
	public function resolve($path)
	{
		$path = static::normalise($path);
		
		if (is_file($path)) {
			return $path;
		}
		
		$filePaths = $this->generate($path);
		
		foreach ($filePaths as $filePath) {
			if (is_file($filePath)) {
				return $filePath;
			}
		}
	}
	
	/**
	 * Determine whether a template exists at the given path.
	 * 
	 * @param string $path
	 * @return bool
	 */
	public function exists($path)
	{
		return $this->resolve($path) !== null;
	}
	
	/**
	 * Resolve a view instance with a template at the given path, as well as
	 * shared variables and config.
	 * 
	 * @param string $path [optional] Template path
	 * @param array  $vars [optional] Variables to assign to the View
	 * @return View
	 */
	public function create($path = null, $vars = array())
	{
		$file = $this->resolve($path);
		
		$engine = $this->engine;
		$engine = new $engine($file, array_merge($this->shared, $vars), $this->config);
		$engine->setResolver($this);
		
		return $engine;
	}
}