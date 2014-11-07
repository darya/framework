<?php
namespace Darya\Routing;

use Darya\Common\Tools;
use Darya\Http\Request;
use Darya\Http\Response;
use Darya\Routing\Route;

/**
 * Darya's router.
 * 
 * @author Chris Andrew <chris.andrew>
 */
class Router {
	
	/**
	 * @var array Regex/replacement patterns for converting route definitions into regular expressions 
	 */
	protected static $replacements = array(
		'#/\:params#' => '(?:/(?<params>.*))?',
		'#/\:([A-Za-z0-9\_\-]+)#' => '(?:/(?<$1>[^/]+))'
	);
	
	/**
	 * @var string Base URL to ignore when matching routes
	 */
	protected $baseUrl = '/';
	
	/**
	 * @var array Collection of routes to match
	 */
	protected $routes = array();
	
	/**
	 * @var string Default namespace for the router to apply if a matched route doesn't have one
	 */
	protected $defaultNamespace = '';
	
	/**
	 * @var string Default controller for the router to apply if a matched route doesn't have one
	 */
	protected $defaultController = 'IndexController';
	
	/**
	 * @var string Default action for the router to apply if a matched route doesn't have one
	 */
	protected $defaultAction = 'index';
	
	/**
	 * @var callable Callable for handling dispatch errors
	 */
	protected $errorHandler;
	
	/**
	 * Convert a route pattern into a regular expression
	 * 
	 * @param string $pattern Route pattern to process 
	 * @return string Regular expression for route matching
	 */
	public static function processPattern($pattern) {
		foreach (static::$replacements as $replacementPattern => $replacement) {
			$pattern = preg_replace($replacementPattern, $replacement, $pattern);
		}
		
		return '#/?^'.$pattern.'/?$#';
	}
	
	/**
	 * Remove all non-numeric properties of a route's matched parameters.
	 * Additionally split the matched "params" property by forward slashes.
	 * 
	 * @param array $matches Set of matches to process
	 * @return array Set of parameters to pass to a matched controller action
	 */
	public static function processMatches($matches) {
		$params = array();
		
		foreach ($matches as $key => $value) {
			if (!is_numeric($key)) {
				if ($key == 'params') {
					$paramParams = explode('/', $value);
					foreach ($paramParams as $paramParam) {
						$params[] = $paramParam;
					}
				} else {
					$params[$key] = $value;
				}
			}
		}
		
		return $params;
	}
	
	/**
	 * Prepares a controller name by camel-casing the given value and appending 
	 * 'Controller', if the provided name does not already end as such. The
	 * resulting string will start with an uppercase letter.
	 * 
	 * For example, 'super-swag' would become 'SuperSwagController'
	 * 
	 * @param $controller URL controller name
	 * @return string Controller class name
	 */
	public static function processController($controller) {
		return Tools::endsWith($controller, 'Controller') ? $controller : Tools::delimToCamel($controller).'Controller';
	}
	
	/**
	 * Prepares an action name by camel-casing the given value. The resulting 
	 * string will start with a lowercase letter.
	 * 
	 * For example, 'super-swag' would become 'superSwag'
	 * 
	 * @param $controller URL controller name
	 * @return string Controller class name
	 */
	public static function processAction($action) {
		return lcfirst(Tools::delimToCamel($action));
	}
	
	/**
	 * Instantiates a new Request if the given argument is a string.
	 *
	 * @param Darya\Core\Models\Request|string $request
	 * @return Darya\Core\Models\Request
	 */
	public static function processRequest($request) {
		if (!($request instanceof Request) && is_string($request)) {
			$request = new Request($request);
		}
		
		return $request;
	}
	
	
	/**
	 * Initialise router with given array of routes where keys are patterns and 
	 * values are either default controllers or a set of default values
	 * 
	 * @param array $routes Array of routes to match
	 */
	public function __construct(array $routes = array()) {
		$this->add($routes);
	}
	
	/**
	 * Appends routes to the router's collection.
	 * Passing $defaults causes the function to expect $routes as a single route
	 * pattern instead of an array.
	 * 
	 * @param string|array $routes Array of pattern => default-value route definitions or a single route pattern string
	 * @param Callable|array $defaults String or array of default parameters for the route
	 */
	public function add($routes, $defaults = null) {
		if (is_array($routes)) {
			foreach ($routes as $pattern => $defaults) {
				$this->routes[] = new Route($pattern, $defaults);
			}
		} else if ($defaults) {
			$pattern = $routes;
			$this->routes[] = new Route($pattern, $defaults);
		}
	}
	
	/**
	 * Set the router's base URL
	 * 
	 * @param string $url
	 */
	public function setBaseUrl($url) {
		$this->baseUrl = $url;
	}
	
	/**
	 * Get the router's base URL
	 * 
	 * @return string
	 */
	public function getBaseUrl() {
		return $this->baseUrl;
	}
	
	/**
	 * Set the router's default values for namespace, controller and action.
	 * 
	 * These are used when a route hasn't provided these values and the matched
	 * route's parameters do not fill these values.
	 * 
	 * @param array $defaults Expects any of 'namespace', 'controller' or 'action' as keys
	 */
	public function setDefaults($defaults = array()) {
		foreach ($defaults as $key => $default) {
			$property = 'default' . ucfirst(strtolower($key));
			
			if (property_exists($this, $property)) {
				$this->$property = $default;
			}
		}
	}
	
	/**
	 * Set an optional error handler for when a dispatched request doesn't 
	 * match to a route.
	 * 
	 * @param callable $handler
	 */
	public function setErrorHandler($handler) {
		if (is_callable($handler)) {
			$this->errorHandler = $handler;
		}
	}
		
	/**
	 * Resolves a matched route's parameters by finding existing controllers and
	 * actions.
	 * 
	 * TODO: It may make sense to move this into Dispatcher and be used as part 
	 * of a Router::match() callback instead of being hardcoded into said method.
	 * 
	 * @param Route $route
	 * @return Route
	 */
	protected function resolve(Route $route) {
		// Store the namespace
		if (!empty($route->params['namespace'])) {
			$route->namespace = $route->params['namespace'];
		}
		
		// Match an existing controller
		if (!empty($route->params['controller'])) {
			$controller = static::processController($route->params['controller']);
			
			if ($route->namespace) {
				$controller = $route->namespace . '\\' . $controller;
			}
			
			if (class_exists($controller)) {
				$route->controller = $controller;
			}
		} else if (!$route->controller) { // Apply router's default controller seeing as the route doesn't have one
			$route->controller = !empty($route->namespace) ? $route->namespace : '';
			$route->controller .= '\\' . $this->defaultController;
		}
		
		// Match an existing action
		if (!empty($route->params['action'])) {
			$action = static::processAction($route->params['action']);
			
			if (method_exists($route->controller, $action)) {
				$route->action = $action;
			} else if(method_exists($route->controller, $action.'Action')) {
				$route->action = $action.'Action';
			}
		} else if (!$route->action) { // Apply router's default action seeing as the route doesn't have one
			$route->action = $this->defaultAction;
		}

		// Debug
		/*echo '<pre>';
		print_r(array(
			$route,
			$route->controller,
			$route->action,
			class_exists($route->controller),
			method_exists($route->controller, $route->action)
		));
		echo '</pre>';*/
		
		return $route;
	}
	
	/**
	 * Match a request to a route.
	 * 
	 * Accepts an optional callback for filtering matched routes, which is
	 * useful for determining whether the matched route's parameters result in
	 * something callable, for example.
	 * 
	 * @param Request|string $request A request URI or a Request object to match
	 * @param Callable $callback [optional] Callback for filtering matched routes
	 * @return Route The matched route.
	 */
	public function match($request, $callback = null) {
		$request = static::processRequest($request);
		
		$url = $request->uri();
		
		// Remove base URL
		$url = substr($url, strlen($this->baseUrl));
		
		// Strip query string
		if (strpos($url, '?') > 0) {
			$url = strstr($url, '?', true);
		}
		
		// Find a matching route
		foreach ($this->routes as $route) {
			// Clone the route object so as not to modify the instances belonging to the router 
			$route = clone $route;
			
			// Process the route pattern into a regular expression
			$pattern = static::processPattern($route->pattern);
			
			// Test for a match
			if (preg_match($pattern, $url, $matches)) {
				$route->addParams(static::processMatches($matches));
				
				$route = $this->resolve($route);
				
				$matched = true;
				
				// Perform the given callback if necessary
				if ($callback && is_callable($callback)) {
					$matched = call_user_func($callback, $route);
				}
				
				if ($matched) {
					$request->router = $this;
					$request->route = $route;
					return $route;
				}
			}
		}
		
		return false;
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
	 * @param Callable $callback [optional] Callback for filtering matched routes
	 * @return mixed The return value of the called action or null if the request could not be dispatched
	 */
	public function dispatch($request, $callback = null) {
		$request = static::processRequest($request);
		$route = $this->match($request, $callback);
		
		if ($route) {
			if ($route->action && is_callable($route->action)) {
				return call_user_func_array($route->action, $route->getParams());
			}
			
			if ($route->controller && $route->action && is_callable(array($route->controller, $route->action))) {
				return call_user_func_array(array($route->controller, $route->action), $route->getParams());
			}
			
			if ($route->controller && !$route->action && is_callable(array($route->controller, $this->defaultAction))) {
				return call_user_func_array(array($route->controller, $route->defaultAction), $route->getParams());
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
	
}
