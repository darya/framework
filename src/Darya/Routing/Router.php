<?php
namespace Darya\Routing;

use Darya\Common\Tools;
use Darya\Http\Request;
use Darya\Http\Response;
use Darya\Routing\Route;

/**
 * Darya's request router.
 * 
 * TODO: Optionally make use of a service container to replace 
 *       call_user_func_array calls and make Dispatcher redundant.
 * 
 * TODO: Implement setting named routes.
 *
 * TODO: Implement route groups.
 * 
 * TODO: Event dispatcher.
 * 
 * @author Chris Andrew <chris.andrew>
 */
class Router {
	
	/**
	 * @var array Regular expression replacements for matching route paths to request URIs
	 */
	protected $patterns = array(
		'#/:params#' => '(?:/(?<params>.*))?',
		'#/:([A-Za-z0-9_-]+)#' => '(?:/(?<$1>[^/]+))'
	);
	
	/**
	 * @var string Base URI to expect when matching routes
	 */
	protected $base;
	
	/**
	 * @var array Collection of routes to match
	 */
	protected $routes = array();
	
	/**
	 * @var array Default values for the router to apply to matched routes
	 */
	protected $defaults = array(
		'namespace'  => null,
		'controller' => 'IndexController',
		'action'     => 'index'
	);
	
	/**
	 * @var array Set of callbacks for filtering matched routes and their parameters
	 */
	protected $filters = array();
	
	/**
	 * @var callable Callable for handling dispatch errors
	 */
	protected $errorHandler;
	
	/**
	 * Replace a route path's placeholders with regular expressions using the 
	 * router's registered replacement patterns.
	 * 
	 * @param string $path Route path to prepare
	 * @return string Regular expression that matches a route's path
	 */
	public function preparePattern($path) {
		foreach ($this->patterns as $pattern => $replacement) {
			$path = preg_replace($pattern, $replacement, $path);
		}
		
		return '#/?^'.$path.'/?$#';
	}
	
	/**
	 * Remove all non-numeric properties of a route's matched parameters.
	 * Additionally split the matched "params" property by forward slashes.
	 * 
	 * @param array $matches Set of matches to prepare
	 * @return array Set of parameters to pass to a matched action
	 */
	public static function prepareMatches($matches) {
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
	 * Prepares a controller name by CamelCasing the given value and appending
	 * 'Controller', if the provided name does not already end as such. The
	 * resulting string will start with an uppercase letter.
	 * 
	 * For example, 'super-swag' would become 'SuperSwagController'
	 * 
	 * @param $controller Route path parameter controller string
	 * @return string Controller class name
	 */
	public static function prepareController($controller) {
		return Tools::endsWith($controller, 'Controller') ? $controller : Tools::delimToCamel($controller) . 'Controller';
	}
	
	/**
	 * Prepares an action name by camelCasing the given value. The resulting
	 * string will start with a lowercase letter.
	 * 
	 * For example, 'super-swag' would become 'superSwag'
	 * 
	 * @param $controller URL controller name
	 * @return string Controller class name
	 */
	public static function prepareAction($action) {
		return lcfirst(Tools::delimToCamel($action));
	}
	
	/**
	 * Instantiates a new Request if the given argument is a string.
	 *
	 * @param Darya\Http\Request|string $request
	 * @return Darya\Http\Request
	 */
	public static function prepareRequest($request) {
		if (!($request instanceof Request) && is_string($request)) {
			$request = new Request($request);
		}
		
		return $request;
	}
	
	/**
	 * Initialise router with given array of routes where keys are patterns and 
	 * values are either default controllers or a set of default values.
	 * 
	 * Optionally accepts an array of default values for reserved route
	 * parameters to use for routes that don't match with them. These include 
	 * 'namespace', 'controller' and 'action'.
	 * 
	 * @param array $routes   Routes to match
	 * @param array $defaults Default router properties
	 */
	public function __construct(array $routes = array(), array $defaults = array()) {
		$this->add($routes);
		$this->defaults($defaults);
		$this->filter(array($this, 'resolve'));
	}
	
	/**
	 * Append unnamed routes to the router.
	 * 
	 * @param string|array   $routes   Path => defaults route definitions or a route path
	 * @param callable|array $defaults Default parameters for the route
	 */
	public function add($routes, $defaults = null) {
		if (is_array($routes)) {
			foreach ($routes as $path => $defaults) {
				$this->routes[] = new Route($path, $defaults);
			}
		} else if ($defaults) {
			$path = $routes;
			$this->routes[] = new Route($path, $defaults);
		}
	}
	
	/**
	 * Get or set the router's base URI.
	 * 
	 * @param string $url [optional]
	 * @return string
	 */
	public function base($uri = null) {
		if ($uri) {
			$this->base = $uri;
		}
		
		return $this->base;
	}
	
	/**
	 * Get and optionally set the router's default values for matched routes.
	 * 
	 * Given key value pairs are merged with the current defaults.
	 * 
	 * These are used when a route and the matched route's parameters haven't
	 * provided default values.
	 * 
	 * @param array $defaults [optional]
	 * @return array Router defaults
	 */
	public function defaults(array $defaults = array()) {
		foreach ($defaults as $key => $value) {
			$property = strtolower($key);
			$this->defaults[$property] = $value;
		}
		
		return $this->defaults;
	}
	
	/**
	 * Register a callback for filtering matched routes and their parameters.
	 * 
	 * Callbacks should return a bool determining whether the route matches.
	 * A route is passed by reference when matched by Router::match.
	 * 
	 * @param callable $callback
	 * @return Darya\Routing\Router
	 */
	public function filter($callback) {
		if (is_callable($callback)) {
			$this->filters[] = $callback;
		}
		
		return $this;
	}
	
	/**
	 * Append a named route to the router.
	 * 
	 * @param string $name     Name that identifies the route
	 * @param string $path     Path that matches the route
	 * @param mixed  $defaults Default route parameters
	 */
	public function route($name, $path, $defaults = array()) {
		$this->routes[$name] = new Route($path, $defaults);
	}
	
	/**
	 * Resolves a matched route's path parameters by finding existing
	 * controllers and actions.
	 * 
	 * Applies the router's defaults if a parameter is not set.
	 * 
	 * TODO: Also apply any other default parameters.
	 * 
	 * @param Route $route
	 * @return Route
	 */
	protected function resolve(Route $route) {
		// Set the router's default namespace if necessary
		if (!$route->namespace) {
			$route->namespace = $this->defaults['namespace'];
		}
		
		// Match an existing controller
		if (!empty($route->controller)) {
			$controller = static::prepareController($route->controller);
			
			if ($route->namespace) {
				$controller = $route->namespace . '\\' . $controller;
			}
			
			if (class_exists($controller)) {
				$route->controller = $controller;
			}
		} else if (!$route->controller) { // Apply the router's default controller when the route doesn't have one
			$namespace = !empty($route->namespace) ? $route->namespace . '\\' : '';
			$route->controller = $namespace . $this->defaults['controller'];
		}
		
		// Match an existing action
		if (!empty($route->action)) {
			$action = static::prepareAction($route->action);
			
			if (method_exists($route->controller, $action)) {
				$route->action = $action;
			} else if(method_exists($route->controller, $action . 'Action')) {
				$route->action = $action . 'Action';
			}
		} else if (!$route->action) { // Apply the router's default action when the route doesn't have one
			$route->action = $this->defaults['action'];
		}

		// Debug
		/*/echo Tools::dump(array(
			$route->parameters(),
			$route,
			$route->controller,
			$route->action,
			class_exists($route->controller),
			method_exists($route->controller, $route->action)
		));/**/
		
		return true;
	}
	
	/**
	 * Match a request to a route.
	 * 
	 * Accepts an optional callback for filtering matched routes and their
	 * parameters. This callback is executed after the router's filters.
	 * 
	 * @param Request|string $request A request URI or a Request object to match
	 * @param callable $callback [optional] Callback for filtering matched routes
	 * @return Route The matched route.
	 */
	public function match($request, $callback = null) {
		$request = static::prepareRequest($request);
		
		$url = $request->uri();
		
		// Remove base URL
		$url = substr($url, strlen($this->base));
		
		// Strip query string
		if (strpos($url, '?') > 0) {
			$url = strstr($url, '?', true);
		}
		
		// Find a matching route
		foreach ($this->routes as $route) {
			// Clone the route object to preserve instances belonging to the router
			$route = clone $route;
			
			// Prepare the route path as a regular expression
			$pattern = $this->preparePattern($route->path());
			
			// Test for a match
			if (preg_match($pattern, $url, $matches)) {
				$route->parameters(static::prepareMatches($matches));
				
				$matched = true;
				
				// Test the route against all registered filters
				foreach ($this->filters as $filter) {
					if (!call_user_func_array($filter, array(&$route))){
						$matched = false;
					}
				}
				
				// Perform the given callback if necessary
				if ($matched && $callback && is_callable($callback)) {
					$matched = call_user_func_array($callback, array(&$route));
				}
				
				if ($matched) {
					$route->router = $this;
					$request->router = $this;
					$request->route = $route;
					return $route;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Set an error handler for dispatched requests that don't match a route.
	 * 
	 * @param callable $handler
	 */
	public function errorHandler($handler) {
		if (is_callable($handler)) {
			$this->errorHandler = $handler;
		}
	}
	
	/**
	 * Match a request to a route and dispatch the resolved callable.
	 * 
	 * If only a controller is available with the matched route, the router's
	 * default action will be attempted.
	 * 
	 * An error handler can be set (@see Router::setErrorHandler) to handle the
	 * request in the case that a route could not be matched, or the matched
	 * route does not result in an action or controller-action combination that
	 * is callable. Returns null in these cases if an error handler is not set.
	 * 
	 * @param Request|string $request
	 * @param callable $callback [optional] Callback for filtering matched routes
	 * @return mixed The return value of the called action or null if the request could not be dispatched
	 */
	public function dispatch($request, $callback = null) {
		$request = static::prepareRequest($request);
		$route = $this->match($request, $callback);
		
		if ($route) {
			if ($route->action && is_callable($route->action)) {
				return call_user_func_array($route->action, $route->pathParameters());
			}
			
			if ($route->controller && $route->action && is_callable(array($route->controller, $route->action))) {
				return call_user_func_array(array($route->controller, $route->action), $route->pathParameters());
			}
			
			if ($route->controller && !$route->action && is_callable(array($route->controller, $this->defaults['action']))) {
				return call_user_func_array(array($route->controller, $this->defaults['action']), $route->pathParameters());
			}
		}
		
		if ($this->errorHandler) {
			$errorHandler = $this->errorHandler;
			return $errorHandler($request);
		}
		
		return null;
	}
	
	/**
	 * Dispatch a request, resolve a Response object from the result and send
	 * the response to the client.
	 * 
	 * @param Darya\Http\Request $request
	 */
	public function respond(Request $request = null) {
		$response = $this->dispatch($request);
		
		if (!$response instanceof Response) {
			$response = new Response($response);
		}
		
		$response->send();
	}
	
	/**
	 * Generate a URL using the given route path and parameters.
	 * 
	 * @param string $path
	 * @param array  $parameters [optional]
	 * @return string
	 */
	public function url($path, $parameters = array()) {
		
	}
	
}
