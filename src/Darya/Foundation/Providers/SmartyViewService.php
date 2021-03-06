<?php
namespace Darya\Foundation\Providers;

use Darya\Service\Contracts\Container;
use Darya\Service\Contracts\Provider;
use Darya\Smarty\ViewResolver;
use Darya\View;

/**
 * A service provider that provides a Smarty view resolver.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class SmartyViewService implements Provider
{
	/**
	 * Register a Smarty view resolver with the service container.
	 *
	 * @param Container $container
	 */
	public function register(Container $container)
	{
		$container->register(array(
			'Darya\Smarty\ViewResolver' => function ($container) {
				$basePath = $container->get('path');
				$realBasePath = realpath("{$basePath}/views/smarty");

				$viewResolver = new ViewResolver('Darya\Smarty\View', $realBasePath);

				$viewResolver->shareConfig(array(
					'base'	  => $realBasePath,
					'cache'   => '../../storage/cache',
					'compile' => '../../storage/views'
				));

				$viewResolver->share(array(
					'config' => $container->config
				));

				return $viewResolver;
			},
			View\Resolver::class => 'Darya\Smarty\ViewResolver'
		));
	}
}
