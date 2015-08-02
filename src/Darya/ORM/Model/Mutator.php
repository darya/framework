<?php
namespace Darya\ORM\Model;

use Darya\ORM\Model\Transformer;
use Darya\ORM\Model\TransformerTrait;

/**
 * Darya's model attribute mutator.
 * 
 * Governs default output behaviour when setting model attributes.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Mutator implements Transformer {
	
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
		if (is_string($value)) {
			$value = strtotime(str_replace('/', '-', $value));
		}
		
		if ($value instanceof DateTime) {
			$value = $value->getTimestamp();
		}
		
		return $value;
	}
	
	public function transformDatetime($value) {
		return $this->transformDate($value);
	}
	
	public function transformTime($value) {
		return $this->transformDate($value);
	}
	
	public function transformArray($value) {
		if (is_array($value)) {
			$value = json_encode($value);
		}
		
		return $value;
	}
	
	public function transformJson($value) {
		return $this->transformArray($value);
	}
	
}
