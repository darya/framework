<?php
namespace Darya\Storage\Query;

use Darya\Storage\Query;
use Darya\Storage\Queryable;

/**
 * Darya's storage query builder.
 * 
 * Forwards method calls to a storage query and executes it on the given
 * queryable storage interface once the query building is complete.
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
	 * Instantiate a new query builder for the given storage and query objects.
	 * 
	 * @param Query     $query
	 * @param Queryable $storage
	 */
	public function __construct(Query $query, Queryable $storage) {
		$this->query = $query;
		$this->storage = $storage;
	}
	
	/**
	 * Dynamically call query methods to build a query.
	 * 
	 * @param string $method
	 * @param array  $arguments
	 * @return $this
	 */
	public function __call($method, $arguments) {
		call_user_func_array(array($this->query, $method), $arguments);
		
		return $this;
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
	 * Alias for execute() method.
	 * 
	 * @return array
	 */
	public function cheers() {
		return $this->execute();
	}
}
