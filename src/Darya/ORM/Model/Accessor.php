<?php
namespace Darya\ORM\Model;

use Darya\ORM\Model\Transformer;
use Darya\ORM\Model\TransformerTrait;

/**
 * Darya's model attribute accessor.
 * 
 * Governs default output behaviour when getting model attributes.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Accessor implements Transformer
{
	use TransformerTrait;
	
	/**
	 * @var string
	 */
	protected $dateFormat;
	
	/**
	 * Instantiate a new model attribute accessor.
	 * 
	 * @param string $dateFormat
	 */
	public function __construct($dateFormat)
	{
		$this->dateFormat = $dateFormat;
	}
	
	/**
	 * Transform a value to an integer.
	 * 
	 * @param mixed $value
	 * @return int
	 */
	public function transformInt($value)
	{
		return (int) $value;
	}
	
	/**
	 * Transform a value to a date string.
	 * 
	 * Uses the currently set date format.
	 * 
	 * @param mixed $value
	 * @return string
	 */
	public function transformDate($value)
	{
		return date($this->dateFormat, $value);
	}
	
	/**
	 * Transform a value to a date time string.
	 * 
	 * Currently an alias for transformDate().
	 * 
	 * @param mixed $value
	 * @return string
	 */
	public function transformDatetime($value)
	{
		return $this->transformDate($value);
	}
	
	/**
	 * Transform a value to a time string.
	 * 
	 * Currently an alias for transformDate().
	 * 
	 * @param mixed $value
	 * @return string
	 */
	public function transformTime($value)
	{
		return $this->transformDate($value);
	}
	
	/**
	 * Transform a value to an array.
	 * 
	 * @param string $value
	 * @return array
	 */
	public function transformArray($value)
	{
		return json_decode($value, true);
	}
	
	/**
	 * Transform a JSON string to an array.
	 * 
	 * Alias for transformArray().
	 * 
	 * @param string $value
	 * @return array
	 */
	public function transformJson($value)
	{
		return $this->transformArray($value);
	}
}
