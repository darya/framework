<?php
namespace Darya\Storage\Query;

use Darya\Storage\Query;
use Darya\Storage\Queryable;

/**
 * Darya's storage query builder.
 * 
 * Forwards method calls to a storage query and reads from the given queryable
 * storage interface once the query building is complete.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Builder {
	
	/**
	 * @var Query
	 */
	protected $query;
	
	/**
	 * @var Queryable
	 */
	protected $storage;
	
	/**
	 * Instantiate a new query builder for the given storage resource.
	 * 
	 * @param Queryable $storage
	 * @param string    $resource
	 */
	public function __construct(Queryable $storage, $resource) {
		$this->query = new Query($resource);
		$this->storage = $storage;
	}
	
	/**
	 * Dynamically call query methods to build a query.
	 * 
	 * @param string $method
	 * @param array  $arguments
	 */
	public function __call($method, $arguments) {
		call_user_func_array(array($this->query, $method), $arguments);
	}
	
	/**
	 * Execute the query on the storage interface.
	 * 
	 * @return array
	 */
	public function execute() {
		return $this->storage->execute($this->query);
	}
	
	/**
	 * Alias for exectute() method.
	 * 
	 * @return array
	 */
	public function cheers() {
		return $this->execute();
	}
}
