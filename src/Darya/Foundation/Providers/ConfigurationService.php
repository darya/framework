<?php

namespace Darya\Foundation\Providers;

use Darya\Foundation\Application;
use Darya\Foundation\Configuration;
use Darya\Service\Exceptions\ContainerException;
use Darya\Service\Contracts\Container;
use Darya\Service\Contracts\Provider;

/**
 * A service provider that configures the application.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class ConfigurationService implements Provider
{
	/**
	 * Register a configuration object, and any of its service aliases and
	 * providers, with the container.
	 *
	 * @param Container $container
	 * @throws ContainerException
	 */
	public function register(Container $container)
	{
		// Register the configuration service and its aliases
		$container->register([
			Configuration::class => function (Application $application) {
				$basePath = $application->basePath();

				// Load the application's configuration
				$configuration = new Configuration\Php([
					"$basePath/config/config.default.php",
					"$basePath/config/config.php"
				]);

				return $configuration;
			},
			'configuration'      => Configuration::class,
			'config'             => Configuration::class
		]);

		$configuration = $container->get(Configuration::class);

		// Register the configured service aliases
		foreach ($configuration['aliases'] as $alias => $service) {
			$container->alias($alias, $service);
		}

		// Register the configured service providers
		if ($container instanceof Application) {
			foreach ($configuration['services'] as $service) {
				if (class_exists($service) && is_subclass_of($service, Provider::class)) {
					/**
					 * @var Provider $provider
					 */
					$provider = $container->create($service);

					$container->provide($provider);
				}
			}
		}
	}
}
