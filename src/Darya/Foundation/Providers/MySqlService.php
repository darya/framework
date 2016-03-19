<?php
namespace Darya\Foundation\Providers;

use Darya\Database\Connection\MySql;
use Darya\Database\Factory;
use Darya\Service\Contracts\Container;
use Darya\Service\Contracts\Provider;

/**
 * A service provider that provides a MySQL connection using the configuration
 * registered with the service container.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class MySqlService implements Provider
{
    public function register(Container $container)
    {
        $container->register(array(
            'Darya\Database\Connection' => function ($container) {
                $config = $container->config;
                
                $connection = new MySql(
                    $config['database.hostname'],
                    $config['database.username'],
                    $config['database.password'],
                    $config['database.database']
                );
                
                $connection->setEventDispatcher($container->event);
                
                return $connection;
            }
        ));
    }
}
