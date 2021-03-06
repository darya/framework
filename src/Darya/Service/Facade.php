<?php
namespace Darya\Service;

use RuntimeException;
use Darya\Service\Contracts\Container as ContainerInterface;

/**
 * Darya's service facade implementation. Very similar to Laravel's approach.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
abstract class Facade
{
	/**
	 * The service container to use for service facades.
	 *
	 * @var ContainerInterface
	 */
	protected static $serviceContainer;

	/**
	 * Set the service container to use for all facades.
	 *
	 * @param ContainerInterface $container
	 */
	public static function setServiceContainer(ContainerInterface $container)
	{
		static::$serviceContainer = $container;
	}

	/**
	 * Return the service interface or alias to resolve from the container.
	 *
	 * All facades must override this method.
	 *
	 * @return string
	 */
	public static function getServiceName()
	{
		return 'Darya\Service\Contracts\Container';
	}

	/**
	 * Magic method that redirects static calls to the facade's related service.
	 *
	 * @param string $method
	 * @param array  $parameters
	 * @return mixed
	 */
	public static function __callStatic($method, $parameters)
	{
		$service = static::getServiceName();

		if (!static::$serviceContainer) {
			throw new RuntimeException('Tried to use a facade without setting a service container');
		}

		$instance = static::$serviceContainer->get($service);

		if (!is_object($instance)) {
			throw new RuntimeException('Facade resolved non-object from the service container');
		}

		if (!method_exists($instance, $method)) {
			throw new RuntimeException('Call to non-existent method "' . $method . '" on facade instance');
		}

		return static::$serviceContainer->call(array($instance, $method), $parameters);
	}
}
