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
	protected $reserved = array('namespace', 'controller', 'action');
	
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
	 * @var array Default or matched params
	 */
	public $params = array(),
	
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
		return isset($this->$property) || isset($this->params[$property]);
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
			$this->params[$property] = $value;
		}
	}
	
	/**
	 * Getter magic method for retrieving route parameters. 
	 * 
	 * @param string $property
	 * @return mixed
	 */
	public function __get($property) {
		if (isset($this->params[$property])) {
			return $this->params[$property];
		}
	}

	/**
	 * Set default parameters using the given $params array.
	 * 
	 * @param array $params
	 */
	public function setDefaults($params = array()) {
		if (is_array($params)) {
			foreach ($params as $key => $value) {
				$this->params[$key] = $value;
			}
		} else if(is_callable($params)) {
			$this->action = $params;
		} else if(is_string($params)) {
			$this->controller = $params;
		}
	}
	
	/**
	 * Set parameters using the given $params array.
	 * 
	 * @param array $params
	 */
	public function addParams($params = array()) {
		if (is_array($params)) {
			foreach ($params as $key => $value) {
				$this->params[$key] = $value;
			}
		}
	}
	
	/**
	 * Get the currently set parameters excluding those reserved for 
	 * dispatching, unless specified by $withReserved.
	 * 
	 * @param bool $withReserved Whether to include reserved params
	 * @return array
	 */
	public function getParams($withReserved = false) {
		if ($withReserved) {
			return $this->params;
		}
		
		return array_diff_key($this->params, array_flip($this->reserved));
	}
	
}
