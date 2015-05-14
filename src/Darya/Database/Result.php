<?php
namespace Darya\Database;

/**
 * Darya's database result representation.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Result {
	
	/**
	 * Associative array of result data.
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
	 * Error data captured from with the query that produced this result.
	 * 
	 * @var array
	 */
	protected $error;
	
	public function __construct() {
		
	}
	
}
