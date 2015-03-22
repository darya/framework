<?php
namespace Darya\Routing;

use Darya\Http\Request;
use Darya\Http\Response;
use Darya\Service\Contracts\Container;
use Darya\Service\Contracts\ContainerAware;

/**
 * Darya's base controller.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
abstract class Controller implements ContainerAware {
	
	/**
	 * @var \Darya\Http\Request
	 */
	public $request;
	
	/**
	 * @var \Darya\Http\Response
	 */
	public $response;
	
	/**
	 * @var \Darya\Service\Contracts\Container
	 */
	public $services;
	
	/**
	 * @var \Darya\View\ViewInterface
	 */
	public $template;
	
	/**
	 * Instantiate a controller.
	 * 
	 * @param \Darya\Http\Request  $request
	 * @param \Darya\Http\Response $response
	 */
	public function __construct(Request $request, Response $response) {
		$this->request = $request;
		$this->response = $response;
	}
	
	/**
	 * Set the controller's service container and instantiate an empty view.
	 * 
	 * @param \Darya\Service\Contracts\Container $services
	 */
	public function setServiceContainer(Container $services) {
		$this->services = $services;
		
		if ($this->services->has('Darya\View\ViewResolver')) {
			$this->template = $this->services->resolve('Darya\View\ViewResolver')->create();
		}
	}
	
}
