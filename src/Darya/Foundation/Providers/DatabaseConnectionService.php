<?php
namespace Darya\Foundation\Providers;

use Darya\Database\Connection;
use Darya\Database\Factory;
use Darya\Events\Dispatchable;
use Darya\Service\Contracts\Container;
use Darya\Service\Contracts\Provider;

/**
 * A service provider that provides a database connection as configured
 * with the service container.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class DatabaseConnectionService implements Provider
{
	/**
	 * Register a database connection with the service container.
	 *
	 * @param Container $container
	 */
	public function register(Container $container)
	{
		$container->register(array(
			Connection::class => function (Container $container) {
				$config = $container->config;

				$factory = new Factory;

				$connection = $factory->create($config['database.type'], array(
					'hostname' => $config['database.hostname'],
					'username' => $config['database.username'],
					'password' => $config['database.password'],
					'database' => $config['database.database'],
					'port'     => $config['database.port'],
					'options'  => $config['database.options']
				));

				if (method_exists($connection, 'setEventDispatcher')) {
					$connection->setEventDispatcher($container->resolve(Dispatchable::class));
				}

				return $connection;
			}
		));
	}

	/**
	 * Prepares database factory options with some default values.
	 */
}
