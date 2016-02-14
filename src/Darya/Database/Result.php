<?php
namespace Darya\Database;

use Darya\Storage\AbstractResult;
use Darya\Database\Error;
use Darya\Database\Query;

/**
 * Darya's database result representation.
 * 
 * @property array $data       Result data
 * @property Query $query      Query that produced this result
 * @property int   $count      Result count
 * @property Error $error      Result error
 * @property array $fields     Field names for each result data row
 * @property int   $insertId   Insert ID
 * @property int   $affected   Rows affected
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Result extends AbstractResult {
	
	/**
	 * The database query that produced this result.
	 * 
	 * @var Query
	 */
	protected $query;
	
	/**
	 * The error that occurred when executing the query, if any.
	 * 
	 * @var Error|null
	 */
	protected $error;
	
	/**
	 * Instantiate a new database result.
	 * 
	 * $info accepts the keys 'affected', 'count', 'insert_id' and 'fields'.
	 * 
	 * @param Query $query
	 * @param array $data  [optional]
	 * @param array $info  [optional]
	 * @param Error $error [optional]
	 */
	public function __construct(Query $query, array $data = array(), array $info = array(), Error $error = null) {
		$this->query = $query;
		$this->data  = $data;
		$this->error = $error;
		
		$this->setInfo($info);
	}
	
}
