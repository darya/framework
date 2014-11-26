<?php
namespace Darya\Routing;

/**
 * Representation of a route in Darya's routing system.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Route {
	
	/**
	 * @var array Reserved route parameter names
	 */
	protected $reserved = array('namespace', 'controller', 'action', 'params');
	
	/**
	 * @var string Pattern for matching the route e.g. "/:controller/:action/:params"
	 */
	public $pattern;
	
	/**
	 * @var string Matched namespace
	 */
	public $namespace;
	
	/**
	 * @var object|string Matched controller
	 */
	public $controller;
	
	/**
	 * @var callable|string Matched action (controller method)
	 */
	public $action;
	
	/**
	 * @var array Default or matched parameters
	 */
	public $parameters = array(),
	
	/**
	 * @var Darya\Routing\Router The router that matched this route
	 */
	$router = null;
	
	/**
	 * Instantiate a new route
	 * 
	 * @param string $pattern Pattern for matching the route
	 * @param callable|array $defaults
	 */
	public function __construct($pattern, $defaults = array()) {
		$this->pattern = $pattern;
		$this->setDefaults($defaults);
	}
	
	/**
	 * Magic method for determining whether an existing property or route 
	 * parameter is set.
	 * 
	 * @return bool
	 */
	public function __isset($property) {
		return isset($this->$property) || isset($this->parameters[$property]);
	}
	
	/**
	 * Setter magic method for setting route parameters if the property does not
	 * exist.
	 * 
	 * @param string $property
	 * @param mixed $value
	 */
	public function __set($property, $value) {
		if (property_exists($this, $property)) {
			$this->$property = $value;
		} else {
			$this->parameters[$property] = $value;
		}
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
	 * Set default parameters using the given $parameters array.
	 * 
	 * If a callable is given it is set as the route's action parameter. If a
	 * string or object is given it is set as the route's controller parameter.
	 * 
	 * @param mixed $parameters
	 */
	public function setDefaults($parameters = array()) {
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
	 * Set parameters using the given $parameters array.
	 * 
	 * @param array $parameters
	 */
	public function addparameters($parameters = array()) {
		if (is_array($parameters)) {
			foreach ($parameters as $key => $value) {
				$this->parameters[$key] = $value;
			}
		}
	}
	
	/**
	 * Get the currently set parameters excluding those reserved for 
	 * dispatching, unless specified by $withReserved.
	 * 
	 * @param bool $withReserved Whether to include reserved parameters
	 * @return array
	 */
	public function getparameters($withReserved = false) {
		if ($withReserved) {
			return $this->parameters;
		}
		
		return array_diff_key($this->parameters, array_flip($this->reserved));
	}
	
}
