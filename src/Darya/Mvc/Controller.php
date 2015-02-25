<?php
namespace Darya\Mvc;

use Darya\Http\Request;
use Darya\Http\Response;
use Darya\Service\ContainerInterface;
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
	 * @var \Darya\Service\ContainerInterface
	 */
	public $services;
	
	/**
	 * @var \Darya\Mvc\ViewInterface
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
	 * @param \Darya\Service\ContainerInterface $services
	 */
	public function setServiceContainer(ContainerInterface $services) {
		$this->services = $services;
		
		if ($this->services->has('Darya\Mvc\ViewResolver')) {
			$this->template = $this->services->resolve('Darya\Mvc\ViewResolver')->create();
		}
	}
	
}
