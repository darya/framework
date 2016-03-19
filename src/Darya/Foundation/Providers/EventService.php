<?php
namespace Darya\Foundation\Providers;

use Darya\Events\Dispatcher;
use Darya\Service\Contracts\Container;
use Darya\Service\Contracts\Provider;

/**
 * A service provider that provides an event dispatcher.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class EventService implements Provider
{
	/**
	 * Register an event dispatcher with the container.
	 * 
	 * @param Container $container
	 */
	public function register(Container $container)
	{
		$container->register(array(
			'Darya\Events\Dispatcher'   => new Dispatcher,
			'Darya\Events\Dispatchable' => 'Darya\Events\Dispatcher'
		));
	}
}
