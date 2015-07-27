<?php
namespace Darya\Database;

/**
 * Darya's database query class.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Query {
	
	/**
	 * @var string SQL query string
	 */
	protected $string;
	
	/**
	 * @var array Data bound to the query
	 */
	protected $parameters = array();
	
	/**
	 * Instantiate a new database query.
	 * 
	 * @param string $string
	 * @param array $parameters [optional]
	 */
	public function __construct($string, $parameters = array()) {
		$this->string = $string;
		$this->parameters = $parameters;
	}
	
	/**
	 * Retrieve the query string.
	 * 
	 * @return string
	 */
	public function string() {
		return $this->string;
	}
	
	/**
	 * Retrieve the query parameters.
	 * 
	 * @return array
	 */
	public function parameters() {
		return $this->parameters;
	}
	
}
