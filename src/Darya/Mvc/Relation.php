<?php
namespace Darya\Mvc;

use \Exception;
use Darya\Mvc\Model;

/**
 * Darya's model relation representation.
 * 
 * TODO: Relation::getFilter()? if we store the parent model (instance?)...
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Relation {
	
	const HAS             = 'has';
	const HAS_MANY        = 'has_many';
	const BELONGS_TO      = 'belongs_to';
	const BELONGS_TO_MANY = 'belongs_to_many';
	
	/**
	 * @var string Parent model class
	 */
	protected $parent;
	
	/**
	 * @var string Related model class
	 */
	protected $related;
	
	/**
	 * @var string Relation type
	 */
	protected $type;
	
	/**
	 * @var string Foreign key on the related model
	 */
	protected $foreignKey;
	
	/**
	 * @var string Local key on the parent model
	 */
	protected $localKey;
	
	/**
	 * @var string Table name for many-to-many relations
	 */
	protected $table;
	
	/**
	 * Instantiate a new relation.
	 * 
	 * @param string $parent     Parent model class
	 * @param string $type       Relation type
	 * @param string $related    Related model class
	 * @param string $foreignKey
	 * @param string $localKey
	 * @param string $table
	 */
	public function __construct($parent, $type, $related, $foreignKey = null, $localKey = null, $table = null) {
		$this->related = $related;
		$this->parent = $parent;
		$this->type = $type ?: static::HAS;
		
		$this->foreignKey = $foreignKey ?: strtolower(basename($parent)) . '_id';
		$this->localKey = $localKey ?: 'id';
		
		$this->table = $table;
	}
	
	/**
	 * Read-only access for relation properties.
	 * 
	 * @param string $property
	 * @return string
	 */
	public function __get($property) {
		if (property_exists($this, $property)) {
			return $this->$property;
		}
	}
	
	public function filter() {
		// TODO: Implement.
	}
	
}
