<?php
namespace Darya\Routing;

/**
 * Representation of a route in Darya's routing system.
 * 
 * @property string          $namespace  Matched namespace
 * @property string          $controller Matched controller
 * @property string|callable $action     Matched action (callable or controller method)
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Route
{
	/**
	 * Reserved route parameter keys.
	 * 
	 * @var array
	 */
	protected $reserved = array('namespace', 'controller', 'action');

	/**
	 * URI path that matches the route - e.g. "/:controller/:action/:params"
	 * 
	 * @var string
	 */
	protected $path;
	
	/**
	 * Default path parameters.
	 * 
	 * @var array
	 */
	protected $defaults = array();
	
	/**
	 * Matched path parameters.
	 * 
	 * @var array
	 */
	protected $matches = array();
	
	/**
	 * Matched path parameters prepared as controller arguments.
	 * 
	 * @var array
	 */
	protected $parameters = array();
	
	/**
	 * The router that matched this route.
	 * 
	 * @var \Darya\Routing\Router
	 */
	public $router;
	
	/**
	 * Prepare the given matches.
	 * 
	 * Removes all non-numeric properties of the given matches.
	 * 
	 * @param array $matches
	 * @return array
	 */
	public static function prepareMatches($matches)
	{
		$prepared = array();
		
		foreach ($matches as $key => $value) {
			if (!is_numeric($key)) {
				$prepared[$key] = $value;
			}
		}
		
		return $prepared;
	}
	
	/**
	 * Prepare the given matches as parameters
	 * 
	 * Splits the matched "params" property by forward slashes and appends these
	 * to the parent array.
	 * 
	 * @param array $matches Set of matches to prepare
	 * @return array Set of route parameters to pass to a matched action
	 */
	public static function prepareParameters($matches)
	{
		$parameters = array();
		
		foreach ($matches as $key => $value) {
			if (!is_numeric($key)) {
				switch ($key) {
					case 'params':
						$pathParameters = explode('/', $value);
						
						foreach ($pathParameters as $pathParameter) {
							$parameters[] = $pathParameter;
						}
						
						break;
					default:
						$parameters[$key] = $value;
				}
			}
		}
		
		return $parameters;
	}
	
	/**
	 * Instantiate a new route.
	 * 
	 * @param string $path     Path that matches the route
	 * @param mixed  $defaults Default route parameters
	 */
	public function __construct($path, $defaults = array())
	{
		$this->path = $path;
		$this->defaults($defaults);
	}
	
	/**
	 * Magic method that determines whether an existing route parameter is set.
	 * 
	 * @return bool
	 */
	public function __isset($property)
	{
		return isset($this->parameters[$property]) || isset($this->defaults[$property]);
	}
	
	/**
	 * Setter magic method that sets route parameters if the attempted property
	 * does not exist.
	 * 
	 * @param string $property
	 * @param mixed  $value
	 */
	public function __set($property, $value)
	{
		$this->parameters[$property] = $value;
	}
	
	/**
	 * Getter magic method for retrieving route parameters.
	 * 
	 * Tries defaults if the parameter is not set.
	 * 
	 * @param string $property
	 * @return mixed
	 */
	public function __get($property)
	{
		if (isset($this->parameters[$property])) {
			return $this->parameters[$property];
		}
		
		if (isset($this->defaults[$property])) {
			return $this->defaults[$property];
		}
	}
	
	/**
	 * Get the currently set parameters, excluding those with reserved keys,
	 * for use as action arguments.
	 * 
	 * @return array
	 */
	public function arguments()
	{
		return array_diff_key($this->parameters(), array_flip($this->reserved));
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
	public function defaults($parameters = array())
	{
		if (is_array($parameters)) {
			$this->defaults = array_merge($this->defaults, $parameters);
		}
		
		if (is_callable($parameters)) {
			$this->defaults['action'] = $parameters;
		}
		
		if (is_string($parameters) || is_object($parameters)) {
			$this->defaults['controller'] = $parameters;
		}
		
		return $this->defaults;
	}
	
	/**
	 * Determine whether the route has been matched by a router.
	 * 
	 * @return bool
	 */
	public function matched()
	{
		return !!$this->matches;
	}
	
	/**
	 * Set route matches and parameters using the given matches array.
	 * 
	 * This completely replaces any existing matches and parameters.
	 * 
	 * Returns the currently set matches.
	 * 
	 * @param array $matches
	 * @return array
	 */
	public function matches(array $matches = array())
	{
		$this->matches = static::prepareMatches($matches);
		
		$this->parameters = static::prepareParameters($matches);
		
		return $this->matches;
	}
	
	/**
	 * Merge route parameters using the given array.
	 * 
	 * Returns the currently set parameters merged into defaults.
	 * 
	 * @param array $parameters [optional]
	 * @return array Route parameters
	 */
	public function parameters(array $parameters = array())
	{
		$this->parameters = array_merge($this->parameters, $parameters);
		
		return array_merge($this->defaults, $this->parameters);
	}
	
	/**
	 * Retrieve the path that matches the route.
	 * 
	 * @return string
	 */
	public function path()
	{
		return $this->path;
	}
	
	/**
	 * Retrieve the URL that the route was matched by.
	 * 
	 * Optionally accepts parameters to merge into matched parameters.
	 * 
	 * @param array $parameters [optional]
	 * @return string
	 */
	public function url(array $parameters = array())
	{
		if ($this->router) {
			$parameters = array_merge($this->matches, $parameters);
			
			return $this->router->url($this->path, $parameters);
		}
	}
}
