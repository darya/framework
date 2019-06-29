<?php

namespace Darya\Foundation\Providers;

use Darya\Database\Connection;
use Darya\Database\Factory;
use Darya\Events\Dispatchable;
use Darya\Foundation\Configuration;
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
		$container->register([
			Connection::class => function (Container $container) {
				/**
				 * @var Configuration $config
				 */
				$config = $container->get(Configuration::class);

				$factory = new Factory;

				$connection = $factory->create($config['database.type'], [
					'hostname' => $config->get('database.hostname'),
					'username' => $config->get('database.username'),
					'password' => $config->get('database.password'),
					'database' => $config->get('database.database'),
					'port'     => $config->get('database.port'),
					'options'  => $config->get('database.options')
				]);

				if (method_exists($connection, 'setEventDispatcher')) {
					$connection->setEventDispatcher($container->get(Dispatchable::class));
				}

				return $connection;
			}
		]);
	}
	/**
	 * Prepares database factory options with some default values.
	 */
}
