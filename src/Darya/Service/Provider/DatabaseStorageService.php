<?php
namespace Darya\Service\Provider;

use Darya\Database\Storage;
use Darya\Service\Contracts\Container;
use Darya\Service\Contracts\Provider;
use Darya\ORM\Record;

/**
 * A service provider that provides a database storage implementation using
 * whatever database connection is registered with the service container.
 * 
 * Registers the provided database storage with Darya's active record class.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class DatabaseStorageService implements Provider
{
	/**
	 * Register a database storage implementation with the service container.
	 * 
	 * @param Container $container
	 */
	public function register(Container $container)
	{
		$container->register(array(
			'Darya\Database\Storage' => function ($container) {
				return new Storage($container->resolve('Darya\Database\Connection'));
			},
			'Darya\Storage\Readable'   => 'Darya\Database\Storage',
			'Darya\Storage\Modifiable' => 'Darya\Database\Storage',
			'Darya\Storage\Searchable' => 'Darya\Database\Storage',
			'Darya\Storage\Queryable'  => 'Darya\Database\Storage',
			'Darya\Storage\Aggregational' => 'Darya\Database\Storage'
		));
	}
	
	/**
	 * Attach the registered storage to the ORM's active record class.
	 * 
	 * @param Storage $storage
	 */
	public function boot(Storage $storage)
	{
		Record::setSharedStorage($storage);
	}
}
