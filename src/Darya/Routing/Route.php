<?php
namespace Darya\Routing;

/**
 * Representation of a route in Darya's routing system.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Route {
	
	/**
	 * @var array Reserved route parameter keys
	 */
	protected static $reserved = array('namespace', 'controller', 'action');
	
	/**
	 * @var string URI path that matches the route - e.g. "/:controller/:action/:params"
	 */
	public $path;
	
	/**
	 * @var string Matched namespace
	 */
	public $namespace;
	
	/**
	 * @var object|string Matched controller
	 */
	public $controller;
	
	/**
	 * @var callable|string Matched action (callable or a controller method)
	 */
	public $action;
	
	/**
	 * @var array Default or matched path parameters
	 */
	public $parameters = array();
	
	/**
	 * @var Darya\Routing\Router The router that matched this route
	 */
	public $router = null;
	
	/**
	 * Instantiate a new route
	 * 
	 * @param string         $path     Path that matches the route
	 * @param callable|array $defaults
	 */
	public function __construct($path, $defaults = array()) {
		$this->path = $path;
		$this->setDefaults($defaults);
	}
	
	/**
	 * Magic method that determines whether an existing property or route
	 * parameter is set.
	 * 
	 * @return bool
	 */
	public function __isset($property) {
		return isset($this->$property) || isset($this->params[$property]);
	}
	
	/**
	 * Setter magic method that sets route parameters if the attempted property
	 * does not exist.
	 * 
	 * @param string $property
	 * @param mixed  $value
	 */
	public function __set($property, $value) {
		$this->parameters[$property] = $value;
	}
	
	/**
	 * Getter magic method for retrieving route parameters. 
	 * 
	 * @param string $property
	 * @return mixed
	 */
	public function __get($property) {
		if (isset($this->parameters[$property])) {
			return $this->parameters[$property];
		}
	}

	/**
	 * Set default parameters using the given array.
	 * 
	 * If a callable is given it becomes the route's default action.
	 * 
	 * If a string or object is given it becomes the route's default controller.
	 * 
	 * @param callable|array|string $parameters
	 */
	public function defaults($parameters) {
		if (!$parameters) {
			return $this->parameters;
		}
		
		if (is_array($parameters)) {
			foreach ($parameters as $key => $value) {
				$this->parameters[$key] = $value;
			}
		} else if(is_callable($parameters)) {
			$this->action = $parameters;
		} else if(is_string($parameters) || is_object($parameters)) {
			$this->controller = $parameters;
		}
	}
	
	/**
	 * Set multiple parameters using the given array.
	 * 
	 * @param array $parameters [optional]
	 */
	public function parameters($parameters = array()) {
		if (is_array($parameters)) {
			foreach ($parameters as $key => $value) {
				$this->parameters[$key] = $value;
			}
		}
	}
	
	/**
	 * Get the currently set parameters excluding those with reserved keys.
	 * 
	 * @return array
	 */
	public function pathParameters() {
		return array_diff_key($this->parameters, array_flip($this->reserved));
	}
	
}
