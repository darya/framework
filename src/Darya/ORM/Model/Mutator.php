<?php
namespace Darya\ORM\Model;

use DateTime;

/**
 * Darya's model attribute mutator.
 *
 * Governs default output behaviour when setting model attributes.
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class Mutator implements Transformer
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
	 * Transform a value to a timestamp integer.
	 *
	 * TODO: Attempt to use the currently set date format. Fall back otherwise.
	 *
	 * @param mixed $value
	 * @return int
	 */
	public function transformDate($value)
	{
		if (is_string($value)) {
			$value = strtotime(str_replace('/', '-', $value));
		}

		if ($value instanceof DateTime) {
			$value = $value->getTimestamp();
		}

		return $value;
	}

	/**
	 * Transform a value to a timestamp integer.
	 *
	 * Currently an alias for transformDate().
	 *
	 * @param mixed $value
	 * @return int
	 */
	public function transformDatetime($value)
	{
		return $this->transformDate($value);
	}

	/**
	 * Transform a value to a timestamp integer.
	 *
	 * Currently an alias for transformDate().
	 *
	 * @param mixed $value
	 * @return int
	 */
	public function transformTime($value)
	{
		return $this->transformDate($value);
	}

	/**
	 * Transform a value to a JSON string.
	 *
	 * @param mixed $value
	 * @return string
	 */
	public function transformArray($value)
	{
		if (is_array($value)) {
			$value = json_encode($value);
		}

		return $value;
	}

	/**
	 * Transform a value to a JSON string.
	 *
	 * Alias for transformArray().
	 *
	 * @param mixed $value
	 * @return string
	 */
	public function transformJson($value)
	{
		return $this->transformArray($value);
	}
}
