<?php
namespace Darya\Foundation\Providers;

use Darya\Foundation\Application;
use Darya\Foundation\Configuration\Php as Configuration;
use Darya\Service\Contracts\Container;
use Darya\Service\Contracts\Provider;

/**
 * A service provider that configures the application.
 * 
 * Also provides the default PHP session.
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
		$container->register(array(
			'Darya\Foundation\Configuration' => function (Application $application) {
				$basePath = $application->basePath();
				
				// Load the application's configuration
				$configuration = new Configuration(array(
					"$basePath/config/config.default.php",
					"$basePath/config/config.php"
				));
				
				return $configuration;
			}
		));
		
		$configuration = $container->resolve('Darya\Foundation\Configuration');
		
		// Register the configured aliases
		foreach ($configuration['aliases'] as $alias => $service) {
			$container->alias($alias, $service);
		}
		
		// Register the configured service providers
		if ($container instanceof Application) {
			foreach ($configuration['services'] as $service) {
				if (class_exists($service) && is_subclass_of($service, 'Darya\Service\Contracts\Provider')) {
					$container->provide($container->create($service));
				}
			}
		}
	}
}
