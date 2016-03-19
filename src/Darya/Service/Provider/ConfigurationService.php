<?php
namespace Darya\Service\Provider;

use Darya\Foundation\Configuration\Php as Configuration;
use Darya\Service\Contracts\Container;
use Darya\Service\Contracts\Provider;

/**
 * A service provider that provides HTTP request and response objects.
 * 
 * Also provides the default PHP session.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class ConfigurationService implements Provider
{
	/**
	 * Register a global HTTP request, response and session with the container.
	 * 
	 * @param Container $application
	 */
	public function register(Container $application)
	{
		$basePath = $application->get('path');
		
		$configuration = new Configuration(array(
			"$basePath/config/config.default.php",
			"$basePath/config/config.php"
		));
		
		$application->set('Darya\Foundation\Configuration', $configuration);
		
		// Register the configured aliases
		foreach ($configuration['aliases'] as $alias => $service) {
			$application->alias($alias, $service);
		}
		
		// Register the configured service providers
		foreach ($configuration['services'] as $service) {
			if (class_exists($service) && is_subclass_of($service, 'Darya\Service\Contracts\Provider')) {
				$application->provide($application->create($service));
			}
		}
	}
}
