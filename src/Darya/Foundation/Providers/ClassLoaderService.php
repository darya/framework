<?php
namespace Darya\Foundation\Providers;

use Darya\Foundation\Autoloader;
use Darya\Foundation\Configuration;
use Darya\Service\Contracts\Container;
use Darya\Service\Contracts\Provider;

/**
 * A service provider that provides an autoloader for application classes.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class ClassLoaderService implements Provider
{
	/**
	 * Register an autoloader for application classes.
	 *
	 * @param Container $container
	 */
	public function register(Container $container)
	{
		$configuration = $container->get(Configuration::class);

		$basePath = $container->get('path');
		$namespace = $configuration->get('project.namespace', 'Application');

		// Map the configured namespace to the application directory
		$autoloader = new Autoloader($basePath, array(
			$namespace => 'application'
		));

		$autoloader->register();

		$container->register(array(
			Autoloader::class => $autoloader
		));
	}
}
