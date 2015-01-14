<?php
namespace Darya\Mvc;

use Darya\Http\Request;
use Darya\Http\Response;
use Darya\Mvc\ViewInterface;
use Darya\Service\Container;
use Darya\Service\ContainerAwareInterface;

/**
 * Darya's base functionality for MVC controllers.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
abstract class Controller implements ContainerAwareInterface {
	
	/**
	 * @var \Darya\Http\Request
	 */
	public $request;
	
	/**
	 * @var \Darya\Http\Response
	 */
	public $response;
	
	/**
	 * @var \Darya\Service\Container;
	 */
	public $services;
	
	/**
	 * @var \Darya\Mvc\ViewInterface
	 */
	public $template;
	
	/**
	 * Instantiate a controller.
	 * 
	 * @param \Darya\Http\Request      $request
	 * @param \Darya\Http\Response     $response
	 * @param \Darya\Mvc\ViewInterface $template [optional]
	 * @param \Darya\Service\Container $services [optional]
	 */
	public function __construct(Request $request, Response $response, ViewInterface $template = null, Container $services = null) {
		$this->request = $request;
		$this->response = $response;
		$this->template = $template;
		$this->services = $services;
	}
	
	/**
	 * Set the controller's service container.
	 * 
	 * @param \Darya\Service\Container $services
	 */
	public function setServiceContainer(Container $services) {
		$this->services = $services;
	}
	
}
