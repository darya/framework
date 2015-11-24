<?php
namespace Darya\Database;

use Darya\Database\Error;
use Darya\Database\Query;

/**
 * Darya's database result representation.
 * 
 * TODO: Make this iterable for result data. Maybe.
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
class Result {
	
	/**
	 * The database query that produced this result.
	 * 
	 * @var Query
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
	 * Convert a string from snake_case to camelCase.
	 * 
	 * @param string $string
	 * @return string
	 */
	protected static function snakeToCamel($string) {
		return preg_replace_callback('/_(.)/', function($matches) {
			return strtoupper($matches[1]);
		}, $string);
	}
	
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
		$this->data  = $data;
		$this->error = $error;
		$this->query = $query;
		
		$this->setInfo($info);
	}
	
	/**
	 * Set the result info.
	 * 
	 * Accepts the keys 'affected', 'count', 'insert_id' and 'fields'.
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
			$property = static::snakeToCamel($key);
			$this->$property = isset($info[$key]) ? $info[$key] : $default;
		}
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
