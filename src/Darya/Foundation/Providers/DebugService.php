<?php
namespace Darya\Foundation\Providers;

use ChromePhp;
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
	 * Register some debugging services if debugging is configured.
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
		
		$listener = function ($result) {
			Chrome::log(array($result->query->string, json_encode($result->query->parameters)));
			
			if ($result->error) {
				Chrome::error(array($result->error->number, $result->error->message));
			}
		};
		
		$container->event->listen('mysql.query', $listener);
		$container->event->listen('mssql.query', $listener);
	}
}
