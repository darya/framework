<?php
namespace Darya\Foundation\Providers;

use Darya\Foundation\Application;
use Darya\Foundation\Configuration;
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
	 */
	public function register(Container $container)
	{
		// Register the configuration service and its aliases
		$container->register(array(
			Configuration::class => function (Application $application) {
				$basePath = $application->basePath();

				// Load the application's configuration
				$configuration = new Configuration\Php(array(
					"$basePath/config/config.default.php",
					"$basePath/config/config.php"
				));

				return $configuration;
			},
			'configuration' => 'Darya\Foundation\Configuration',
			'config' => 'Darya\Foundation\Configuration'
		));

		$configuration = $container->resolve('Darya\Foundation\Configuration');

		// Register the configured service aliases
		foreach ($configuration['aliases'] as $alias => $service) {
			$container->alias($alias, $service);
		}

		// Register the configured service providers
		if ($container instanceof Application) {
			foreach ($configuration['services'] as $service) {
				if (class_exists($service) && is_subclass_of($service, 'Darya\Service\Contracts\Provider')) {
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
