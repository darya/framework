<?php
namespace Darya\Database;

/**
 * Darya's database error representation.
 * 
 * @property int    $number
 * @property string $message
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Error {
	
	/**
	 * @var int
	 */
	protected $number;
	
	/**
	 * @var string
	 */
	protected $message;
	
	/**
	 * Instantiate a new database error object.
	 * 
	 * @param int    $number
	 * @param string $message
	 */
	public function __construct($number, $message) {
		$this->number = (int) $number;
		$this->message = (string) $message;
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
