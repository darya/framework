<?php
namespace Darya\Foundation\Providers;

use Chrome;
use Darya\Events\Dispatcher;
use Darya\Foundation\Configuration;
use Darya\Service\Contracts\Container;
use Darya\Service\Contracts\Provider;

/**
 * A service provider that provides application debugging services, if debugging
 * is configured.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class DebugService implements Provider
{
	/**
	 * Alias ChromePhp to Chrome and, if debugging is configured, enable
	 * display_errors.
	 * 
	 * @param Container $container
	 */
	public function register(Container $container)
	{
		if (class_exists('ChromePhp') && !class_exists('Chrome')) {
			class_alias('ChromePhp', 'Chrome');
		}
		
		$configuration = $container->resolve('Darya\Foundation\Configuration');
		
		if (!$configuration->get('debug')) {
			return;
		}
		
		ini_set('display_errors', 1);
	}
	
	/**
	 * If debugging is enabled when the application boots, attach an event
	 * listener that outputs database queries to ChromePhp.
	 * 
	 * @param Configuration $configuration
	 * @param Dispatcher    $events
	 */
	public function boot(Configuration $configuration, Dispatcher $events) {
		if (!$configuration->get('debug') || !class_exists('Chrome')) {
			return;
		}
		
		$listener = function ($result) {
			Chrome::log(array($result->query->string, json_encode($result->query->parameters)));
			
			if ($result->error) {
				Chrome::error(array($result->error->number, $result->error->message));
			}
		};
		
		$events->listen('mysql.query', $listener);
		$events->listen('mssql.query', $listener);
	}
}
