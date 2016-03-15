<?php
namespace Darya\Storage\Query;

use Darya\Storage\Query;
use Darya\Storage\Queryable;

/**
 * Darya's storage query builder.
 * 
 * Forwards method calls to a storage query and executes it on the given
 * queryable storage interface once the query has been built.
 * 
 * @property-read Query     $query
 * @property-read Queryable $storage
 * @property-read callable  $callback
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Builder {
	
	/**
	 * @var Query The query to execute
	 */
	protected $query;
	
	/**
	 * @var Queryable The storage interface to query
	 */
	protected $storage;
	
	/**
	 * @var callback
	 */
	protected $callback;
	
	/**
	 * @var array Query methods that should trigger query execution
	 */
	protected static $executors = array('all', 'read', 'distinct', 'delete');
	
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
	 * Dynamically invoke methods to fluently build a query.
	 * 
	 * If the method is an execute method, the query is executed and the result
	 * returned.
	 * 
	 * @param string $method
	 * @param array  $arguments
	 * @return $this|mixed
	 */
	public function __call($method, $arguments) {
		call_user_func_array(array($this->query, $method), $arguments);
		
		if (in_array($method, static::$executors)) {
			return $this->execute();
		}
		
		return $this;
	}
	
	/**
	 * Dynamically retrieve a property.
	 * 
	 * @param string $property
	 * @return mixed
	 */
	public function __get($property) {
		return $this->$property;
	}
	
	/**
	 * Set a callback to run on results before returning them from execute
	 * methods.
	 * 
	 * Callbacks should accept one parameter: the query result.
	 * 
	 * @param callback $callback
	 */
	public function callback($callback) {
		$this->callback = $callback;
	}
	
	/**
	 * Execute the query on the storage interface.
	 * 
	 * @return mixed
	 */
	public function execute() {
		$result = $this->storage->execute($this->query);
		
		if (!is_callable($this->callback)) {
			return $result;
		}
		
		return call_user_func($this->callback, $result);
	}
	
	/**
	 * Alias for execute() method.
	 * 
	 * @return mixed
	 */
	public function cheers() {
		return $this->execute();
	}
}
