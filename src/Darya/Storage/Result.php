<?php
namespace Darya\Storage;

/**
 * Darya's storage query result.
 *
 * @property array $data     Result data
 * @property Query $query    Query that produced this result
 * @property int   $count    Result data count
 * @property Error $error    Result error
 * @property array $fields   Field names for each result data row
 * @property int   $insertId Resulting insert ID from the query
 * @property int   $affected Rows affected
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class Result extends AbstractResult
{
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
	 * @param Query $query Query that produced this result
	 * @param array $data  [optional] Result data
	 * @param array $info  [optional] Result info (Keys: 'affected', 'count', 'insert_id' and 'fields')
	 * @param Error $error [optional] Result error
	 */
	public function __construct(Query $query, array $data = [], array $info = [], Error $error = null)
	{
		$this->query = $query;
		$this->data  = $data;
		$this->error = $error;

		$this->setInfo($info);
	}
}
