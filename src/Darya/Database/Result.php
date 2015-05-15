<?php
namespace Darya\Database;

use Darya\Database\Error;

/**
 * Darya's database result representation.
 * 
 * @property array  $data     Result data
 * @property string $query    Query that produced this result
 * @property int    $count    Result count
 * @property Error  $error    Result error
 * @property array  $fields   Field names for each result data row
 * @property int    $insert   Insert ID
 * @property int    $affected Rows affected
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Result {
	
	/**
	 * The query that produced this result.
	 * 
	 * @var string
	 */
	protected $query;
	
	/**
	 * Associative array of the result's data.
	 * 
	 * @var array
	 */
	protected $data;
	
	/**
	 * Number of rows affected by the query. Only applies to queries that modify
	 * the database.
	 * 
	 * @var int
	 */
	protected $affected;
	
	/**
	 * Number of rows in the result data.
	 * 
	 * @var int
	 */
	protected $count;
	
	/**
	 * Set of fields available for each row in the result.
	 * 
	 * @var array
	 */
	protected $fields;
	
	/**
	 * Auto incremented primary key of an inserted row.
	 * 
	 * @var int
	 */
	protected $insertId;
	
	/**
	 * Error captured from the query that produced this result.
	 * 
	 * @var Error
	 */
	protected $error;
	
	/**
	 * Instantiate a new database result.
	 * 
	 * $error expects the keys 'number' and 'message'.
	 * 
	 * $info expects the keys 'affected', 'count', 'insert_id' and 'fields'.
	 * 
	 * @param string $query
	 * @param array  $data
	 * @param array  $info
	 * @param array  $error
	 */
	public function __construct($query, array $data = array(), array $info = array(), array $error = array()) {
		$this->data = $data;
		$this->query = $query;
		$this->setInfo($info);
		$this->setError($error);
	}
	
	/**
	 * Set the result info.
	 * 
	 * @param array $info
	 */
	protected function setInfo(array $info) {
		$defaults = array(
			'count' => 0,
			'fields' => array(),
			'affected' => 0,
			'insert_id' => 0
		);
		
		foreach ($defaults as $key => $default) {
			$this->$key = isset($info[$key]) ? $info[$key] : $default;
		}
	}
	
	/**
	 * Set the result error.
	 * 
	 * @param array $error
	 */
	protected function setError(array $error) {
		$number = isset($error['number']) ? $error['number'] : 0;
		$message = isset($error['message']) ? $error['message'] : '';
		$this->error = new Error($number, $message);
	}
	
	/**
	 * Dynamically retrieve the given property.
	 * 
	 * @param string $property
	 * @return mixed
	 */
	public function __get($property) {
		return $this->$property;
	}
	
}
