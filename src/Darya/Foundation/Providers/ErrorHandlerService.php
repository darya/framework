<?php
namespace Darya\Foundation\Providers;

use Darya\Http\Request;
use Darya\Http\Response;
use Darya\Routing\Router;
use Darya\Service\Contracts\Container;
use Darya\Service\Contracts\Provider;
use Darya\View;

/**
 * A service provider that provides its own method as a routing error handler.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class ErrorHandlerService implements Provider
{
	/**
	 * @var View\Resolver
	 */
	protected $view;
	
	/**
	 * Instantiate a new error handler service.
	 * 
	 * @param View\Resolver $view
	 */
	public function __construct(View\Resolver $view)
	{
		$this->view = $view;
	}
	
	/**
	 * Handle the given request and response in the case of a routing error.
	 * 
	 * @param Request  $request
	 * @param Response $response
	 * @return mixed
	 */
	public function handle(Request $request, Response $response)
	{
		$status = $response->status();
		
		$response->content("$status error.");
		
		if ($this->view->exists("errors/$status")) {
			$response->content($this->view->create("errors/$status", array(
				'http_host'   => $request->host(),
				'request_uri' => $request->path(),
				'signature'   => $request->server('server_signature')
			)));
		}
		
		return $response;
	}
	
	/**
	 * Register services with the container.
	 * 
	 * @param Container $container
	 */
	public function register(Container $container)
	{
		// No implementation needed.
	}
	
	/**
	 * Associate the error handler method with the router.
	 * 
	 * @param Router $router
	 */
	public function boot(Router $router)
	{
		$router->setErrorHandler(array($this, 'handle'));
	}
}
