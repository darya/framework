<?php
namespace Darya\Routing;

use Darya\Http\Request;
use Darya\Http\Response;
use Darya\Service\Contracts\Container;
use Darya\Service\Contracts\ContainerAware;
use Darya\View\Resolver;

/**
 * Darya's base controller.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
abstract class Controller implements ContainerAware
{
	/**
	 * The current request.
	 *
	 * TODO: This should be per-action, not per-controller.
	 *
	 * @var Request
	 */
	public $request;

	/**
	 * The response object.
	 *
	 * @var Response
	 */
	public $response;

	/**
	 * The service container.
	 *
	 * @var Container
	 */
	public $services;

	/**
	 * The view to respond with.
	 *
	 * @var \Darya\View\View
	 */
	public $template;

	/**
	 * Instantiate a controller.
	 *
	 * @param Request  $request
	 * @param Response $response
	 */
	public function __construct(Request $request, Response $response)
	{
		$this->request = $request;
		$this->response = $response;
	}

	/**
	 * Get the URL of the controller's current request.
	 *
	 * Optionally accepts an array of route parameters to override.
	 *
	 * @param array $parameters [optional]
	 * @return string
	 */
	public function url(array $parameters = array())
	{
		return $this->request->route->url($parameters);
	}

	/**
	 * Set the controller's service container.
	 *
	 * Instantiates an empty view if the container has a view resolver.
	 *
	 * @param Container $services
	 */
	public function setServiceContainer(Container $services)
	{
		$this->services = $services;

		if ($this->services->has(Resolver::class)) {
			$this->template = $this->services->get(Resolver::class)->create();
		}
	}
}
