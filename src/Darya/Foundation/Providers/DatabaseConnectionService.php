<?php
namespace Darya\Foundation\Providers;

use Darya\Database\Factory;
use Darya\Service\Contracts\Container;
use Darya\Service\Contracts\Provider;

/**
 * A service provider that provides a MySQL connection using the configuration
 * registered with the service container.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class DatabaseConnectionService implements Provider
{
	/**
	 * Register an SQL database connection with the service container.
	 * 
	 * @param Container $container
	 */
	public function register(Container $container)
	{
		$container->register(array(
			'Darya\Database\Connection' => function ($container) {
				$config = $container->config;
				
				$factory = new Factory;
				
				$connection = $factory->create($config['database.type'], array(
					'hostname' => $config['database.hostname'],
					'username' => $config['database.username'],
					'password' => $config['database.password'],
					'database' => $config['database.database']
				));
				
				$connection->setEventDispatcher($container->event);
				
				return $connection;
			}
		));
	}
}
