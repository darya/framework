<?php
namespace Darya\Database;

use Darya\Storage\AbstractResult;

/**
 * Darya's database result representation.
 *
 * @property-read array $data       Result data
 * @property-read Query $query      Query that produced this result
 * @property-read int   $count      Result count
 * @property-read Error $error      Result error
 * @property-read array $fields     Field names for each result data row
 * @property-read int   $insertId   Insert ID
 * @property-read int   $affected   Rows affected
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class Result extends AbstractResult
{
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
