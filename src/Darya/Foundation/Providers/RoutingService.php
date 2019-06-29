<?php
namespace Darya\Foundation\Providers;

use Darya\Events\Dispatchable;
use Darya\Foundation\Configuration;
use Darya\Routing\Router;
use Darya\Service\Contracts\Container;
use Darya\Service\Contracts\Provider;

/**
 * A service provider that provides a configured router.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class RoutingService implements Provider
{
	/**
	 * Register a router with the service container.
	 *
	 * @param Container $container
	 */
	public function register(Container $container)
	{
		$container->register(array(
			Router::class => function (Container $container) {
				/**
				 * @var Configuration $config
				 */
				$config = $container->get(Configuration::class);

				$routes = $config->get('routes', array(
					'/:controller/:action/:params' => null,
					'/:controller/:params' => null,
					'/:action/:params' => null,
					'/' => null
				));

				$projectNamespace = $config->get('project.namespace', 'Application');
				$defaultNamespace = "{$projectNamespace}\Controllers";

				$router = new Router($routes, array(
					'namespace' => $defaultNamespace
				));

				$router->base($config->get('base_url'));
				$router->setServiceContainer($container);
				$router->setEventDispatcher($container->get(Dispatchable::class));

				return $router;
			}
		));
	}
}
