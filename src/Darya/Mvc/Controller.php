<?php
namespace Darya\Mvc;

use Darya\Http\Request;
use Darya\Http\Response;
use Darya\Mvc\ViewInterface;
use Darya\Service\Container;

/**
 * Darya's MVC controller.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
abstract class Controller {
	
	/**
	 * @var Darya\Http\Request
	 */
	public $request;
	
	/**
	 * @var Darya\Http\Response
	 */
	public $response;
	
	/**
	 * @var Darya\Routing\Container;
	 */
	public $services;
	
	/**
	 * @var Darya\Mvc\ViewInterface
	 */
	public $template;
	
	/**
	 * Instantiate a controller.
	 * 
	 * @param Darya\Core\Models\Request  $request
	 * @param Darya\Core\Models\Response $response
	 * @param Darya\Mvc\ViewInterface    $template [optional]
	 * @param Darya\Container\Container  $services [optional]
	 */
	public function __construct(Request $request, Response $response, ViewInterface $template = null, Container $services = null) {
		$this->request = $request;
		$this->response = $response;
		$this->template = $template;
		$this->services = $services;
	}
	
	public function setServiceContainer(Container $services) {
		$this->services = $services;
	}
	
}
