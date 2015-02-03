<?php
namespace Darya\Routing;

use ReflectionClass;
use Darya\Common\Tools;
use Darya\Events\DispatcherInterface;
use Darya\Events\SubscriberInterface;
use Darya\Http\Request;
use Darya\Http\Response;
use Darya\Routing\Route;
use Darya\Service\ContainerInterface;
use Darya\Service\ContainerAwareInterface;

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
	 * @var array Collection of routes to match requests against
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
	 * @var \Darya\Events\DispatcherInterface
	 */
	protected $eventDispatcher;
	
	/**
	 * @var \Darya\Service\ContainerInterface
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
	 * @param string $action URL action name
	 * @return string Action method name
	 */
	public static function prepareAction($action) {
		return lcfirst(Tools::delimToCamel($action));
	}
	
	/**
	 * Instantiates a new request if the given argument is a string.
	 *
	 * @param \Darya\Http\Request|string $request
	 * @return \Darya\Http\Request
	 */
	public static function prepareRequest($request) {
		if (!$request instanceof Request && is_string($request)) {
			$request = new Request($request);
		}
		
		return $request;
	}
	
	/**
	 * Prepare a response object using the given value.
	 * 
	 * @param mixed $response
	 * @return \Darya\Http\Response
	 */
	public static function prepareResponse($response) {
		if (!$response instanceof Response) {
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
		$this->filter(array($this, 'dispatchable'));
	}
	
	/**
	 * Set the optional event dispatcher for emitting routing events.
	 * 
	 * @param \Darya\Events\DispatcherInterface $dispatcher
	 */
	public function setEventDispatcher(DispatcherInterface $dispatcher) {
		$this->eventDispatcher = $dispatcher;
	}
	
	/**
	 * Set an optional service container for resolving the dependencies of
	 * controllers and actions.
	 * 
	 * @param \Darya\Service\ContainerInterface $container
	 */
	public function setServiceContainer(ContainerInterface $container) {
		$this->services = $container;
	}
	
	/**
	 * Helper method for invoking callables. Silent if the given argument is
	 * not callable.
	 * 
	 * Resolves parameters using the service container if available.
	 * 
	 * @param mixed $callable
	 * @param array $arguments [optional]
	 * @return mixed
	 */
	protected function call($callable, array $arguments = array()) {
		if (is_callable($callable)) {
			if ($this->services) {
				return $this->services->call($callable, $arguments);
			} else {
				return call_user_func_array($callable, $arguments);
			}
		}
		
		return null;
	}
	
	/**
	 * Helper method for instantiating classes.
	 * 
	 * Instantiates the given class if it isn't already an object.
	 * 
	 * @param mixed $class
	 * @param array $arguments [optional]
	 * @return object
	 */
	protected function create($class, $arguments) {
		if (!is_object($class) && class_exists($class)) {
			if ($this->services) {
				$class = $this->services->create($class, $arguments);
			} else {
				$reflection = new ReflectionClass($class);
				$class = $reflection->newInstanceArgs($arguments);
			}
		}
		
		return $class;
	}
	
	/**
	 * Helper method for dispatching events. Silent if an event dispatcher is
	 * not set.
	 * 
	 * @param string $name
	 * @param mixed  $arguments [optional]
	 * @return mixed
	 */
	protected function event($name, array $arguments = array()) {
		if ($this->eventDispatcher) {
			return $this->eventDispatcher->dispatch($name, $arguments);
		}
		
		return null;
	}
	
	/**
	 * Helper method for subscribing objects to the router's event dispatcher.
	 * 
	 * Silent if $subscriber does not implement `SubscriberInterface`.
	 * 
	 * @param mixed $subscriber
	 * @return bool
	 */
	protected function subscribe($subscriber) {
		if ($this->eventDispatcher && $subscriber instanceof SubscriberInterface) {
			$this->eventDispatcher->subscribe($subscriber);
			return true;
		}
		
		return false;
	}
	
	/**
	 * Helper method for unsubscribing objects from the router's event
	 * dispatcher.
	 * 
	 * Silent if $subscriber does not implement `SubscriberInterface`.
	 * 
	 * @param mixed $subscriber
	 * @return bool
	 */
	protected function unsubscribe($subscriber) {
		if ($this->eventDispatcher && $subscriber instanceof SubscriberInterface) {
			$this->eventDispatcher->unsubscribe($subscriber);
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
	 * @return array Router's default route parameters
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
	 * @return \Darya\Routing\Router
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
	 * @return \Darya\Routing\Router
	 */
	public function pattern($pattern, $replacement) {
		$this->patterns[$pattern] = $replacement;
		
		return $this;
	}
	
	/**
	 * Attempt to resolve a matched route's controller class.
	 * 
	 * Falls back to the router's default controller.
	 * 
	 * @param \Darya\Routing\Route $route
	 * @return \Darya\Routing\Route
	 */
	protected function resolveRouteController(Route $route) {
		if (!$route->namespace) {
			$route->namespace = $this->defaults['namespace'];
		}
		
		if ($route->controller) {
			$controller = static::prepareController($route->controller);
			
			if ($route->namespace) {
				$controller = $route->namespace . '\\' . $controller;
			}
			
			if (class_exists($controller)) {
				$route->controller = $controller;
			}
		} else {
			$namespace = $route->namespace ? $route->namespace . '\\' : '';
			$route->controller = $namespace . $this->defaults['controller'];
		}
		
		return $route;
	}
	
	/**
	 * Attempt to resolve a matched route's action method.
	 * 
	 * Falls back to the router's default action.
	 * 
	 * @param \Darya\Routing\Route $route
	 * @return \Darya\Routing\Route
	 */
	protected function resolveRouteAction(Route $route) {
		if ($route->action) {
			if (!is_string($route->action)) {
				return $route;
			}
			
			$action = static::prepareAction($route->action);
			
			if (method_exists($route->controller, $action)) {
				$route->action = $action;
			} else if(method_exists($route->controller, $action . 'Action')) {
				$route->action = $action . 'Action';
			}
		} else {
			$route->action = $this->defaults['action'];
		}
		
		return $route;
	}
	
	/**
	 * Resolve a matched route's controller and action.
	 * 
	 * Applies the router's defaults for these if they are not set.
	 * 
	 * This is a built in route filter that is registered by default.
	 * 
	 * TODO: Also apply any other default parameters.
	 * 
	 * @param \Darya\Routing\Route $route
	 * @return bool
	 */
	public function resolve(Route $route) {
		$this->resolveRouteController($route);
		$this->resolveRouteAction($route);
		return true;
	}
	
	/**
	 * Determine whether a given matched route can be dispatched based on
	 * whether the resolved controller action is callable.
	 * 
	 * This is a built in route filter that is registered by default. It expects
	 * the `resolve` filter to have already been applied to the given route.
	 * 
	 * @param \Darya\Routing\Route $route
	 * @return bool
	 */
	public function dispatchable(Route $route) {
		$dispatchableAction = is_callable($route->action);
		
		$dispatchableController =
			(is_object($route->controller) || class_exists($route->controller))
			&& method_exists($route->controller, $route->action)
			&& is_callable(array($route->controller, $route->action));
			
		return $dispatchableAction || $dispatchableController;
	}
	
	/**
	 * Test a given route against the router's filters.
	 * 
	 * Optionally test against the given callback after testing against filters.
	 * 
	 * @param \Darya\Routing\Route $route
	 * @param callable             $callback
	 * @return bool
	 */
	protected function testMatchFilters(Route $route, $callback = null) {
		$filters = is_callable($callback) ? array_merge($this->filters, $callback) : $this->filters;
		
		foreach ($filters as $filter) {
			if (!$this->call($filter, array(&$route))) {
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Test a request against a route.
	 * 
	 * Accepts an optional extra callback for filtering matched routes and their
	 * parameters. This callback is executed after testing the route against
	 * the router's filters.
	 * 
	 * Fires the 'router.prefilter' event before testing against filters.
	 * 
	 * @param \Darya\Http\Request  $request
	 * @param \Darya\Routing\Route $route
	 * @param callable             $callback [optional]
	 * @return bool
	 */
	protected function testMatch(Request $request, Route $route, $callback = null) {
		$path = $request->path();
		$path = substr($path, strlen($this->base));
		$pattern = $this->preparePattern($route->path());
		
		if (preg_match($pattern, $path, $matches)) {
			$route->matches($matches);
			
			$this->event('router.prefilter', array($route));
			
			$matched = $this->testMatchFilters($route, $callback);
			
			if ($matched) {
				$route->router = $this;
				$request->router = $this;
				$request->route = $route;
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Match a request to one of the router's routes.
	 * 
	 * @param \Darya\Http\Request|string $request A request URI or a Request object to match
	 * @param callable $callback [optional] Callback for filtering matched routes
	 * @return \Darya\Routing\Route|bool The matched route
	 */
	public function match($request, $callback = null) {
		$request = static::prepareRequest($request);
		
		foreach ($this->routes as $route) {
			$route = clone $route;
			
			if ($this->testMatch($request, $route, $callback)) {
				return $route;
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
	 * Dispatch the given controller/action/arguments.
	 * 
	 * TODO: Lots of refactoring!
	 * 
	 * @param \Darya\Http\Response $response
	 * @param object|string        $controller [optional]
	 * @param callable|string      $action
	 * @param array                $arguments [optional]
	 * @return \Darya\Http\Response
	 */
	protected function dispatchController(Request $request, Response $response, $controller = null, $action, array $arguments = array()) {
		if ($this->services && $controller instanceof ContainerAwareInterface) {
			$controller->setServiceContainer($this->services);
		}
		
		$this->subscribe($controller);
		
		$this->event('router.before', array($request));
		
		if ($controller && is_callable(array($controller, $action))) {
			$response = $this->call(array($controller, $action), $arguments);
		} else if (is_callable($action)) {
			$response = $this->call($action, $arguments);
		} else {
			$response->setStatus(404);
		}
		
		$this->event('router.after', array($request));
		
		$response = static::prepareResponse($response ?: $controller->response);
		
		if (!$response->redirected()) {
			$this->event('router.last', array($request));
			
			// TODO: Eradicate the need for this statement.
			if (!$response->hasContent()) {
				$response = static::prepareResponse($controller->template);
			}
		}
		
		$this->unsubscribe($controller);
		
		return $response;
	}
	
	/**
	 * Match a request to a route and dispatch the resolved callable.
	 * 
	 * An error handler can be set (@see Router::setErrorHandler) to handle the
	 * request in the case that a route could not be matched, or the matched
	 * route does not result in an action or controller-action combination that
	 * is callable. Returns null in these cases if an error handler is not set.
	 * 
	 * @param \Darya\Http\Request|string $request
	 * @param \Darya\Http\Response       $response [optional]
	 * @return \Darya\Http\Response
	 */
	public function dispatch($request, Response $response = null) {
		$request  = static::prepareRequest($request);
		$response = static::prepareResponse($response);
		
		$route = $this->match($request);
		
		if ($route) {
			$controllerArguments = array(
				'request'  => $request,
				'response' => $response
			);
			
			$controller = $this->create($route->controller, $controllerArguments);
			$action     = $route->action;
			$arguments  = $route->arguments();
			
			$response = $this->dispatchController($request, $response, $controller, $action, $arguments);
			
			$response->addHeader('X-Location: ' . $request->path());
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
	 * @param \Darya\Http\Request|string $request
	 * @param \Darya\Http\Response       $response [optional]
	 */
	public function respond($request, Response $response = null) {
		$response = $this->dispatch($request, $response);
		$response->send();
	}
	
	/**
	 * Generate a request path using the given route path and parameters.
	 * 
	 * TODO: Swap generate() & path() functionality?
	 * 
	 * @param string $path
	 * @param array $parameters [optional]
	 * @return string
	 */
	public function generate($path, array $parameters = array()) {
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
	 * Generate a request path using the given route name/path and parameters.
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
		
		return $this->generate($path, $parameters);
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
