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
class Accessor implements Transformer {
	
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
	public function __construct($dateFormat) {
		$this->dateFormat = $dateFormat;
	}
	
	public function transformInt($value) {
		return (int) $value;
	}
	
	public function transformDate($value) {
		return date($this->dateFormat, $value);
	}
	
	public function transformDatetime($value) {
		return $this->transformDate($value);
	}
	
	public function transformTime($value) {
		return $this->transformDate($value);
	}
	
	public function transformArray($value) {
		return json_decode($value, true);
	}
	
	public function transformJson($value) {
		return $this->transformArray($value);
	}
	
}
