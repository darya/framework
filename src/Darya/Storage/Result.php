<?php
namespace Darya\Storage;

use Darya\Storage\AbstractResult;
use Darya\Storage\Error;
use Darya\Storage\Query;

/**
 * Darya's storage query result.
 * 
 * @property array $data     Result data
 * @property Query $query    Query that produced this result
 * @property int   $count    Result count
 * @property Error $error    Result error
 * @property array $fields   Field names for each result data row
 * @property int   $insertId Insert ID
 * @property int   $affected Rows affected
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Result extends AbstractResult {
	
	/**
	 * The storage query that produced this result.
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
		
		$this->setInfo($info);
	}
	
}
