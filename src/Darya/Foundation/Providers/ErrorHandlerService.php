<?php
namespace Darya\Foundation\Providers;

use Darya\Http\Request;
use Darya\Http\Response;
use Darya\Routing\Router;
use Darya\Service\Contracts\Container;
use Darya\Service\Contracts\Provider;
use Darya\Smarty\ViewResolver;

/**
 * A service provider that provides its own method as a routing error handler.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class ErrorHandlerService implements Provider
{
    /**
     * @var ViewResolver
     */
    protected $view;
    
    public function __construct(ViewResolver $view)
    {
        $this->view = $view;
    }
    
    public function handle(Request $request, Response $response)
    {
        $status = $response->status();
        
        if ($this->view->exists("error/$status")) {
			$response->content($this->view->create("error/$status", array(
				'http_host' => $request->host(),
				'request_uri' => $request->path(),
				'signature' => $request->server('server_signature')
			)));
        } else {
        	$response->content("$status error.");
        }
        
        return $response;
    }
    
    public function register(Container $container)
    {
    }
    
    public function boot(Router $router)
    {
        $router->setErrorHandler(array($this, 'handle'));
    }
}
