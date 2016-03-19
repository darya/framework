<?php
namespace Darya\Foundation\Providers;

use Darya\Http\Request;
use Darya\Http\Session\Php as PhpSession;
use Darya\Service\Contracts\Container;
use Darya\Service\Contracts\Provider;

/**
 * A service provider that provides HTTP request and response objects.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class HttpService implements Provider
{
    public function register(Container $container)
    {
		$container->register(array(
            'Darya\Http\Request' => function ($container) {
                return Request::createFromGlobals($container->resolve('Darya\Http\Session'));
            },
            'Darya\Http\Session' => new PhpSession
        ));
    }
}
