<?php
namespace Darya\Storage;

/**
 * Darya's abstract storage query result.
 * 
 * TODO: Make this iterable for result data.
 * 
 * @property array  $data     Result data
 * @property object $query    Query that produced this result
 * @property int    $count    Result count
 * @property object $error    Result error
 * @property array  $fields   Field names for each result data row
 * @property int    $insertId Insert ID
 * @property int    $affected Rows affected
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
abstract class AbstractResult {
	
	/**
	 * The storage query that produced this result.
	 * 
	 * @var mixed
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
	 * @var mixed|null
	 */
	protected $error;
	
	/**
	 * The number of rows in the result data.
	 * 
	 * @var int
	 */
	protected $count;
	
	/**
	 * The set of fields available for each row in the result.
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
