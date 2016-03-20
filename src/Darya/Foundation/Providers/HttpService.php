<?php
namespace Darya\Foundation\Providers;

use Darya\Http\Request;
use Darya\Http\Response;
use Darya\Http\Session;
use Darya\Service\Contracts\Container;
use Darya\Service\Contracts\Provider;

/**
 * A service provider that provides HTTP request and response objects.
 * 
 * Also provides the default PHP session.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class HttpService implements Provider
{
	/**
	 * Register a global HTTP request, response and session with the container.
	 * 
	 * @param Container $container
	 */
	public function register(Container $container)
	{
		$container->register(array(
			'Darya\Http\Request' => function ($container) {
				return Request::createFromGlobals($container->resolve('Darya\Http\Session'));
			},
			'Darya\Http\Response' => new Response,
			'Darya\Http\Session' => new Session\Php
		));
	}
}
