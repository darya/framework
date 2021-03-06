<?php

namespace Darya\Foundation\Providers;

use Darya\Foundation\Configuration;
use Darya\Service\Contracts\Container;
use Darya\Service\Contracts\Provider;
use Darya\View;
use Darya\View\Php;

/**
 * A service provider that provides a PHP view resolver.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class PhpViewService implements Provider
{
	/**
	 * Register a Smarty view resolver with the service container.
	 *
	 * @param Container $container
	 */
	public function register(Container $container)
	{
		$container->register([
			View\Resolver::class => function (Container $container) {
				$basePath     = $container->get('path');
				$realBasePath = realpath("{$basePath}/views/php");

				$viewResolver = new View\Resolver(Php::class, $realBasePath, '.php');

				$viewResolver->shareConfig([
					'base' => $realBasePath
				]);

				$viewResolver->share([
					'config' => $container->get(Configuration::class)
				]);

				return $viewResolver;
			}
		]);
	}
}
