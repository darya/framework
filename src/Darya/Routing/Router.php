<?php
namespace Darya\Routing;

use Darya\Common\Tools;
use Darya\Http\Request;
use Darya\Http\Response;
use Darya\Routing\Route;
use Darya\Service\Container;
use Darya\Service\ContainerAwareInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Darya's request router.
 * 
 * TODO: Implement route groups.
 * 
 * @author Chris Andrew <chris.andrew>
 */
class Router implements ContainerAwareInterface {
	
	/**
	 * @var array Regular expression replacements for matching route paths to request URIs
	 */
	protected $patterns = array(
		'#/:([A-Za-z0-9_-]+)#' => '(?:/(?<$1>[^/]+))',
		'#/:params#' => '(?:/(?<params>.*))?'
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
	 * @var Symfony\Component\EventDispatcher\EventDispatcherInterface
	 */
	protected $eventDispatcher;
	
	/**
	 * @var Darya\Service\Container
	 */
	protected $services;
	
	/**
	 * @var callable Callable for handling dispatched requests that don't match a route
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
		foreach (array_reverse($this->patterns) as $pattern => $replacement) {
			$path = preg_replace($pattern, $replacement, $path);
		}
		
		return '#/?^'.$path.'/?$#';
	}
	
	/**
	 * Prepares a controller name by CamelCasing the given value and appending
	 * 'Controller', if the provided name does not already end as such. The
	 * resulting string will start with an uppercase letter.
	 * 
	 * For example, 'super-swag' would become 'SuperSwagController'
	 * 
	 * @param string $controller Route path parameter controller string
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
	 * @param string $controller URL controller name
	 * @return string Controller class name
	 */
	public static function prepareAction($action) {
		return lcfirst(Tools::delimToCamel($action));
	}
	
	/**
	 * Instantiates a new request if the given argument is a string.
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
	 * Prepare a response object using the given value.
	 * 
	 * @param mixed $response
	 * @return Darya\Http\Response
	 */
	public static function prepareResponse($response) {
		if (!($response instanceof Response)) {
			$response = new Response($response);
		}
		
		return $response;
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
	 * Set the optional event dispatcher for emitting routing events.
	 * 
	 * @param Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
	 */
	public function setEventDispatcher(EventDispatcherInterface $dispatcher) {
		$this->eventDispatcher = $dispatcher;
	}
	
	/**
	 * Set an optional service container for resolving the dependencies of
	 * controllers and actions.
	 * 
	 * @param Darya\Service\Container $container
	 */
	public function setServiceContainer(Container $container) {
		$this->services = $container;
	}
	
	/**
	 * Helper method for invoking callables. Silent if the given argument is
	 * not callable.
	 * 
	 * Resolves parameters using the service container if one is set.
	 * 
	 * @param string $callable
	 * @param array  $params
	 * @return mixed
	 */
	protected function call($callable, $parameters = array()) {
		if (is_callable($callable)) {
			if ($this->services) {
				return $this->services->call($callable, $parameters);
			} else {
				return call_user_func_array($callable, $parameters);
			}
		}
		
		return null;
	}
	
	/**
	 * Helper method for dispatching events. Silent if an event dispatcher is
	 * not set.
	 * 
	 * @param string $name
	 * @param mixed  $event [optional]
	 * @return mixed
	 */
	protected function event($name, $event = null) {
		if ($this->eventDispatcher) {
			return $this->eventDispatcher->dispatch($name, $event);
		}
		
		return null;
	}
	
	/**
	 * Helper method for subscribing objects (controllers) to the router's event
	 * dispatcher.
	 * 
	 * @param Symfony\Component\EventDispatcher\EventSubscriberInterface $subscriber
	 * @return bool
	 */
	protected function subscribe(EventSubscriberInterface $subscriber) {
		if ($this->eventDispatcher && $subscriber instanceof EventSubscriberInterface) {
			$this->eventDispatcher->addSubscriber($subscriber);
			return true;
		}
		
		return false;
	}
	
	/**
	 * Helper method for unsubscribing objects (controllers) from the router's 
	 * event dispatcher.
	 * 
	 * @param Symfony\Component\EventDispatcher\EventSubscriberInterface $subscriber
	 * @return bool
	 */
	protected function unsubscribe(EventSubscriberInterface $subscriber) {
		if ($this->eventDispatcher && $subscriber instanceof EventSubscriberInterface) {
			$this->eventDispatcher->removeSubscriber($subscriber);
			return true;
		}
		
		return false;
	}
	
	/**
	 * Add routes to the router.
	 * 
	 * When passed as an array, $routes elements can consist of either:
	 *   - Route path as the key, callable as the value
	 *   - Route name as the key, Route instance as the value
	 * 
	 * An example using both:
	 *     $router->add(array(
	 *         '/route-path' => 'Namespace\Controller',
	 *         'route-name'  => new Route('/route-path', 'Namespace\Controller')
	 *     ));
	 * 
	 * @param string|array          $routes   Route definitions or a route path
	 * @param callable|array|string $defaults Default parameters for the route if $routes is a route path
	 */
	public function add($routes, $defaults = null) {
		if (is_array($routes)) {
			foreach ($routes as $path => $defaults) {
				if ($defaults instanceof Route) {
					$this->routes[$path] = $defaults;
				} else {
					$this->routes[] = new Route($path, $defaults);
				}
			}
		} else if ($defaults) {
			$path = $routes;
			$this->routes[] = new Route($path, $defaults);
		}
	}
	
	/**
	 * Add a single named route to the router.
	 * 
	 * @param string $name     Name that identifies the route
	 * @param string $path     Path that matches the route
	 * @param mixed  $defaults Default route parameters
	 */
	public function set($name, $path, $defaults = array()) {
		$this->routes[$name] = new Route($path, $defaults);
	}
	
	/**
	 * Get or set the router's base URI.
	 * 
	 * @param string $uri [optional]
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
	 * @return array Router default parameters
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
	 * Register a replacement pattern.
	 * 
	 * @param string $pattern
	 * @param string $replacement
	 * @return Darya\Routing\Router
	 */
	public function pattern($pattern, $replacement) {
		$this->patterns[$pattern] = $replacement;
		
		return $this;
	}
	
	/**
	 * Resolves a matched route's path parameters by finding existing
	 * controllers and actions.
	 * 
	 * Applies the router's defaults for these if one is not set.
	 * 
	 * This is a built in route filter that is registered by default.
	 * 
	 * TODO: Also apply any other default parameters.
	 * 
	 * @param Darya\Routing\Route $route
	 * @return bool
	 */
	public function resolve(Route $route) {
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
		
		return true;
	}
	
	/**
	 * Match a request to a route.
	 * 
	 * Accepts an optional extra callback for filtering matched routes and their
	 * parameters. This callback is executed after the router's filters.
	 * 
	 * @param Darya\Http\Request|string $request A request URI or a Request object to match
	 * @param callable $callback [optional] Callback for filtering matched routes
	 * @return Darya\Routing\Route The matched route
	 */
	public function match($request, $callback = null) {
		$request = static::prepareRequest($request);
		
		$uri = $request->uri();
		
		// Remove base URL
		$uri = substr($uri, strlen($this->base));
		
		// Strip query string
		if (strpos($uri, '?') > 0) {
			$uri = strstr($uri, '?', true);
		}
		
		// Find a matching route
		foreach ($this->routes as $route) {
			// Clone the route object to preserve the router's instances
			$route = clone $route;
			
			// Prepare the route path as a regular expression
			$pattern = $this->preparePattern($route->path());
			
			// Test for a match
			if (preg_match($pattern, $uri, $matches)) {
				$route->matches($matches);
				
				$matched = true;
				
				// Test the route against all registered filters
				foreach ($this->filters as $filter) {
					if (!$this->call($filter, array(&$route))) {
						$matched = false;
					}
				}
				
				// Test the route against the given callback filter if necessary
				if ($matched && $callback && is_callable($callback)) {
					$matched = $this->call($callback, array(&$route));
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
	public function error($handler) {
		if (is_callable($handler)) {
			$this->errorHandler = $handler;
		}
	}
	
	/**
	 * Match a request to a route and dispatch the resolved callable.
	 * 
	 * Attempts to resolve a callable in this order:
	 *   - Action
	 *   - Controller::action
	 *   - Controller:defaultAction
	 *   - DefaultController::defaultAction
	 * 
	 * An error handler can be set (@see Router::setErrorHandler) to handle the
	 * request in the case that a route could not be matched, or the matched
	 * route does not result in an action or controller-action combination that
	 * is callable. Returns null in these cases if an error handler is not set.
	 * 
	 * @param Darya\Http\Request|string $request
	 * @param Darya\Http\Response       $response [optional]
	 * @return Darya\Http\Response|null
	 */
	public function dispatch($request, Response $response = null) {
		$request  = static::prepareRequest($request);
		$response = static::prepareResponse($response);
		
		$route = $this->match($request);
		
		if ($route) {
			$controller = $route->controller;
			$action     = $route->action;
			$arguments  = $route->arguments();
			
			// Instantiate the controller
			if (!is_object($controller) && class_exists($controller)) {
				if ($this->services) {
					$controller = $this->services->create($controller, array(
						'request'  => $request,
						'response' => $response
					));
				} else {
					$controller = new $controller($request, $response);
				}
			}
			
			if ($this->services && $controller instanceof ContainerAwareInterface) {
				$controller->setServiceContainer($this->services);
			}
			
			$this->subscribe($controller);
			
			$this->event('router.before');
			
			if ($route->action && is_callable($route->action)) {
				$response = $this->call($action, $arguments);
			}
			
			if ($controller && $action && is_callable(array($controller, $action))) {
				$response = $this->call(array($controller, $action), $arguments);
			}
			
			if ($controller && !$action && is_callable(array($controller, $this->defaults['action']))) {
				$response = $this->call(array($controller, $this->defaults['action']), $arguments);
			}

			if (!$controller && !$action && is_callable(array($this->defaults['controller'], $this->defaults['action']))) {
				$response = $this->call(array($this->defaults['controller'], $this->defaults['action']), $arguments);
			}
			
			$this->event('router.after');
			
			$response = static::prepareResponse($response);
			
			if (!$response->redirected()) {
				$this->event('router.last');
			}
			
			$this->unsubscribe($controller);
			
			$response->addHeader('X-Location: ' . $this->base() . $request->server('PATH_INFO'));
			return $response;
		} else {
			$response->setStatus(404);
		}
		
		if ($this->errorHandler) {
			$errorHandler = $this->errorHandler;
			return static::prepareResponse($this->call($errorHandler, array($request, $response)));
		}
		
		return $response;
	}
	
	/**
	 * Dispatch a request, resolving a response and send it to the client.
	 * 
	 * Optionally pass through an existing response object.
	 * 
	 * @param Darya\Http\Request|string $request
	 * @param Darya\Http\Response       $response [optional]
	 */
	public function respond($request, Response $response = null) {
		$response = $this->dispatch($request, $response);
		$response->send();
	}
	
	/**
	 * Generate a URL path using the given route name and parameters.
	 * 
	 * Any required parameters that are not satisfied by the given parameters
	 * or the route's defaults will be set to the string 'null'.
	 * 
	 * @param string $name       Route name or path
	 * @param array  $parameters [optional]
	 * @return string
	 */
	public function path($name, array $parameters = array()) {
		$path = $name;
		
		if (isset($this->routes[$name])) {
			$route = $this->routes[$name];
			$path = $route->path();
			$parameters = array_merge($route->defaults(), $parameters);
		}
		
		if (isset($parameters['params']) && is_array($parameters['params'])) {
			$parameters['params'] = implode('/', $parameters['params']);
		}
		
		return preg_replace_callback('#/(:[A-Za-z0-9_-]+(\??))#', function ($match) use ($parameters) {
			$parameter = trim($match[1], '?:');
			
			if ($parameter && isset($parameters[$parameter])) {
				return '/' . $parameters[$parameter];
			}
			
			if ($parameter !== 'params' && $match[2] !== '?') {
				return '/null';
			}
			
			return null;
		}, $path);
	}
	
	/**
	 * Generate an absolute URL using the given route name and parameters.
	 * 
	 * @param string $name
	 * @param array  $parameters [optional]
	 * @return string
	 */
	public function url($name, array $parameters = array()) {
		return $this->base . $this->path($name, $parameters);
	}
	
}
