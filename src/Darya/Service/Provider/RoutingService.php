<?php
namespace Darya\Service\Provider;

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
    public function register(Container $container)
    {
        $container->register(array(
            'Darya\Routing\Router' => function ($container) {
            	$config = $container->config;
            	
                $routes = $config['routes'] ?: array(
                    '/:controller/:action/:params' => null,
                    '/:controller/:params' => null,
                    '/:action/:params' => null,
                    '/' => null
                );
                
                $projectNamespace = $config['project.namespace'] ?: 'Application';
                
                $defaultNamespace = "{$projectNamespace}\Controllers";
                
                $router = new Router($routes, array(
                    'namespace' => $defaultNamespace
                ));
                
                $router->base($config['base_url']);
                
                $router->setServiceContainer($container);
                
                $router->setEventDispatcher($container->resolve('Darya\Events\Dispatchable'));
                
                return $router;
            }
        ));
    }
}
