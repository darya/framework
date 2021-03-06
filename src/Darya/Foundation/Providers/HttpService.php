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
		$container->register([
			Request::class  => function (Container $container) {
				return Request::createFromGlobals($container->get(Session::class));
			},
			Response::class => new Response,
			Session::class  => new Session\Php
		]);
	}
}
