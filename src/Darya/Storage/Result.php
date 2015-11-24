<?php
namespace Darya\Storage;

use Darya\Storage\Query;

/**
 * Darya's storage query result.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Result {
	
	/**
	 * The storage query that produced this result.
	 * 
	 * @var Query
	 */
	protected $query;
	
	/**
	 * An associative array of the result data.
	 * 
	 * @var array
	 */
	protected $data;
	
	/**
	 * The error that occurred when executing the query, if any.
	 * 
	 * @var Error|null
	 */
	protected $error;
	
	/**
	 * Instantiate a new storage query result.
	 * 
	 * @param Query $query
	 * @param array $data  [optional]
	 * @param array $info  [optional]
	 * @param Error $error [optional]
	 */
	public function __construct(Query $query, array $data = array(), array $info = array(), Error $error = null) {
		$this->query = $query;
		$this->data = $data;
		$this->error = $error;
	}
	
}
