<?php
namespace Darya\Routing;

/**
 * Representation of a route in Darya's routing system.
 * 
 * @property string $namespace  Matched namespace
 * @property string $controller Matched controller
 * @property callable|string $action Matched action (callable or controller method)
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Route {
	
	/**
	 * @var array Reserved route parameter keys
	 */
	protected $reserved = array('namespace', 'controller', 'action');

	/**
	 * @var string URI path that matches the route - e.g. "/:controller/:action/:params"
	 */
	protected $path;
	
	/**
	 * @var array Default path parameters
	 */
	protected $defaults = array();
	
	/**
	 * @var array Matched path parameters
	 */
	protected $parameters = array();
	
	/**
	 * @var Darya\Routing\Router The router that matched this route
	 */
	public $router;
	
	/**
	 * Instantiate a new route.
	 * 
	 * @param string $path     Path that matches the route
	 * @param mixed  $defaults Default route parameters
	 */
	public function __construct($path, $defaults = array()) {
		$this->path = $path;
		$this->defaults($defaults);
	}
	
	/**
	 * Magic method that determines whether an existing route parameter is set.
	 * 
	 * @return bool
	 */
	public function __isset($property) {
		return isset($this->parameters[$property]) || isset($this->defaults[$property]);
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
		
		if (isset($this->defaults[$property])) {
			return $this->defaults[$property];
		}
	}

	/**
	 * Set default route parameters using the given array.
	 * 
	 * If a callable is given it becomes the route's default action.
	 * 
	 * If a string or object is given it becomes the route's default controller.
	 * 
	 * @param mixed $parameters
	 * @return array The route's default parameters
	 */
	public function defaults($parameters = array()) {
		if (is_array($parameters)) {
			foreach ($parameters as $key => $value) {
				$this->defaults[$key] = $value;
			}
		} else if(is_callable($parameters)) {
			$this->defaults['action'] = $parameters;
		} else if(is_string($parameters) || is_object($parameters)) {
			$this->defaults['controller'] = $parameters;
		}
		
		return $this->defaults;
	}
	
	/**
	 * Set route parameters using the given array.
	 * 
	 * Returns the currently set parameters merged into defaults.
	 * 
	 * @param array $parameters [optional]
	 * @return array Route parameters
	 */
	public function parameters(array $parameters = array()) {
		if (is_array($parameters)) {
			foreach ($parameters as $key => $value) {
				$this->parameters[$key] = $value;
			}
		}
		
		return array_merge($this->defaults, $this->parameters);
	}
	
	/**
	 * Retrieve the path that matches the route.
	 * 
	 * @return string
	 */
	public function path() {
		return $this->path;
	}
	
	/**
	 * Get the currently set parameters excluding those with reserved keys.
	 * 
	 * @return array
	 */
	public function pathParameters() {
		return array_diff_key($this->parameters(), array_flip($this->reserved));
	}
	
	/**
	 * Retrieve the URL that the route was matched by.
	 * 
	 * @return string
	 */
	public function url() {
		if ($this->router) {
			return $this->router->url($this->path, $this->parameters());
		}
	}
	
}
