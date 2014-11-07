<?php
namespace Darya\Routing;

use Darya\Http\Request;
use Darya\Http\Response;
use Darya\Routing\Router;
use Darya\Service\Container;

/**
 * Darya's dispatcher. Invokes the most suitable controller and action derived 
 * from a route matched by the given router. 
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Dispatcher {
	
	/**
	 * @var Darya\Routing\Router
	 */
	protected $router;
	
	/**
	 * @var Darya\Routing\Container
	 */
	protected $services;
	
	/**
	 * Instantiate a new dispatcher.
	 * 
	 * @param Darya\Routing\Router    $router
	 * @param Darya\Service\Container $services
	 */
	public function __construct(Router $router, Container $services = null) {
		$this->router = $router;
		$this->services = $services;
	}

	/**
	 * Determine whether a given matched route can be dispatched based on
	 * whether the resolved controller action is callable.
	 * 
	 * @param Darya\Routing\Route $route
	 * @return bool
	 */
	public function dispatchable(Route $route) {
		return (is_object($route->controller) || class_exists($route->controller))
			&& method_exists($route->controller, $route->action) 
			&& is_callable(array($route->controller, $route->action));
	}

	/**
	 * Helper function for invoking controller actions that is silent when
	 * the given controller and action are not callable.
	 * 
	 * @param string $controller
	 * @param string $action
	 * @param array  $params
	 * @return mixed
	 */
	protected function call($controller, $action, $params = array()) {
		if (is_callable(array($controller, $action))) {
			return call_user_func_array(array($controller, $action), $params);
		}
		
		return null;
	}
	
	/**
	 * Prepare a response object with the given variable.
	 * 
	 * @param mixed $response
	 * @return Darya\Http\Response
	 */
	protected function prepareResponse($response) {
		if (!$response instanceof Response) {
			$response = new Response($response);
		}
		
		return $response;
	}

	/**
	 * Dispatch the given request by matching it to a route and invoking said 
	 * route's controller action, as well as before and after hooks of the
	 * matched controller.
	 * 
	 * @param Darya\Http\Request|string $request
	 * @param Darya\Http\Response $response [optional]
	 * @return Darya\Http\Response
	 */
	public function dispatch($request, Response $response = null) {
		$route = $this->router->match($request, array($this, 'dispatchable'));
		
		if ($route) {
			$controller = is_object($route->controller) ? $route->controller : new $route->controller($request, $response ?: new Response);
			
			if ($this->services && method_exists($controller, 'setServiceContainer')) {
				$controller->setServiceContainer($this->services);
				
				if (!$controller->template && $this->services->view) {
					$controller->template = $this->services->view->create();
				}
			}
			
			$action = $route->action;
			$params = $route->getParams();
			
			$this->call($controller, 'before', $params);
			$response = $this->call($controller, $action, $params);
			$this->call($controller, 'after', $params);
			
			$response = $this->prepareResponse($response ?: $controller->response);
			
			if (!$response->redirected()) {
				$this->call($controller, 'last', $params);
				
				if (!$response->hasContent()) {
					$response = $this->prepareResponse($controller->template);
				}
			}
		} else {
			// TODO: Error controller?
			$response = new Response;
			$response->setStatus(404);
		}
		
		$response->addHeader('X-Location: ' . $this->router->getBaseUrl() . $request->server('PATH_INFO'));
		return $response;
	}
	
	/**
	 * Respond to the given request by dispatching it and sending the resulting
	 * response.
	 * 
	 * @param Darya\Http\Request|string $request
	 * @param Darya\Http\Response $response [optional]
	 */
	public function respond($request, Response $response = null) {
		$response = $this->dispatch($request, $response);
		$response->send();
	}
	
}
