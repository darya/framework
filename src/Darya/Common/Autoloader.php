<?php
namespace Darya\Common;

/**
 * Darya's class autoloader.
 * 
 * TODO: Simplify Autoloader::load.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Autoloader {
	
	/**
	 * @var array Common subdirectories used as a last resort when autoloading
	 */
	private $commonSubdirs = array('Common', 'Classes', 'Controllers', 'Models', 'Tests');
	
	/**
	 * @var array Map of namespaces to paths to use when autoloading
	 */
	private $registeredNamespaces = array();
	
	/**
	 * @var string Base path to use when autoloading
	 */
	private $basePath;
	
	/**
	 * Instantiate an autoloader.
	 * 
	 * @param string $basePath   Base directory path to load from
	 * @param array  $namespaces Namespace to directory mappings to register with the autoloader
	 */
	public function __construct($basePath = null, array $namespaces = array()) {
		$this->setBasePath($basePath);
		$this->registerNamespaces($namespaces);
	}
	
	/**
	 * Return the base name of a namespaced class.
	 * 
	 * @param string $class Fully qualified (namespaced) class name
	 * @return string
	 */
	public static function classBaseName($class) {
		return basename(str_replace('\\', '/', $class));
	}
	
	/**
	 * Set the base directory to load from.
	 * 
	 * @param string $basePath
	 */
	public function setBasePath($basePath = null) {
		$this->basePath = $basePath ?: realpath(__DIR__.'/../../');
	}
	
	/**
	 * Get the base directory to load from.
	 * 
	 * @return string
	 */
	public function getBasePath() {
		return $this->basePath;
	}
	
	/**
	 * Register this autoloader.
	 * 
	 * @return bool
	 */
	public function register() {
		return spl_autoload_register(array($this, 'load'));
	}
	
	/**
	 * Register namespace to directory mappings to attempt before the
	 * autoloader's default behaviour.
	 * 
	 * @param array $namespaces Namespace keys and directory values
	 */
	public function registerNamespaces(array $namespaces = array()) {
		foreach ($namespaces as $ns => $paths) {
			foreach ((array) $paths as $path) {
				if (file_exists($this->basePath . "/$path") || file_exists($path)) {
					$this->registeredNamespaces[] = array($ns, $path);
				}
			}
		}
	}

	/**
	 * Attempt to load the class at the given path.
	 * 
	 * @param string $path
	 * @return bool
	 */
	public function attempt($path) {
		if (is_file($path)) {
			require_once $path;
			return true;
		}
		
		return false;
	}

	/**
	 * Load a class assuming the namespace is a path.
	 * 
	 * Checks common subdirectory names as a last resort if nothing is found.
	 * 
	 * @param string $class Class name
	 * @return bool
	 */
	public function load($class) {
		// Separate the class name and its namespace
		$parts = explode('\\', $class);
		$className = array_pop($parts);
		$dir = implode('/', $parts);
		
		// Try registered namespace to directory mappings
		foreach ($this->registeredNamespaces as $registered) {
			list($ns, $nsPaths) = $registered;
			
			foreach ((array) $nsPaths as $nsPath) {
				$nsBasePaths = array('');
				
				if ($this->basePath) {
					$nsBasePaths[] = $this->basePath . '/';
				}
				
				foreach ($nsBasePaths as $nsBasePath) {
					if ($class == $ns) {
						if ($this->attempt($nsBasePath . "$nsPath")) {
							return true;
						}
						
						if ($this->attempt($nsBasePath . "$nsPath/$className.php")) {
							return true;
						}
					}
					
					if(strpos($class, $ns) === 0){
						if ($this->attempt($nsBasePath . "$nsPath/$dir/$className.php")) {
							return true;
						}
						
						$nsRemain = str_replace('\\', '/', substr($class, strlen($ns)));
						if ($this->attempt($nsBasePath . "$nsPath/$nsRemain.php")) {
							return true;
						}
					}
				}
			}
		}
		
		// Try using the namespace as an exact directory mapping
		$file = $this->basePath . "/$dir/$className.php";
		if ($this->attempt($file)) {
			return true;
		}
		
		// Try using the namespace in lowercase as a directory mapping, with 
		// only the class name in its original case
		$dirLowercase = strtolower($dir);
		$fileLowercase = $this->basePath . "/$dirLowercase/$className.php";
		if ($this->attempt($fileLowercase)) {
			return true;
		}
		
		// Last try using the last part of the namespace as a subdirectory, with 
		// and without a trailing 's', as well as any common subdirectory names
		$subdirs = array_merge($this->commonSubdirs, array(
			$className,
			$className . 's',
		));
		
		foreach($subdirs as $subdir) {
			$file = $this->basePath . "/$dir/$subdir/$className.php";
			if ($this->attempt($file)) {
				return true;
			}
			
			$subdirLowercase = strtolower($subdir);
			$file = $this->basePath . "/$dirLowercase/$subdirLowercase/$className.php";
			if ($this->attempt($file)) {
				return true;
			}
		}
		
		return false;
	}
	
}
