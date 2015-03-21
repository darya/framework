<?php
namespace Darya\Mvc;

use \Exception;
use Darya\Mvc\Model;

/**
 * Darya's model relation representation.
 * 
 * TODO: Camel-to-delim.
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
	 * @var string Foreign key on the "belongs-to" model
	 */
	protected $foreignKey;
	
	/**
	 * @var string Local key on the "has" model
	 */
	protected $localKey;
	
	/**
	 * @var string Table name for many-to-many relations
	 */
	protected $table;
	
	/**
	 * Instantiate a new relation.
	 * 
	 * @param Model  $parent
	 * @param string $type
	 * @param string $related
	 * @param string $foreignKey
	 * @param string $localKey
	 * @param string $table
	 */
	public function __construct(Model $parent, $type, $related, $foreignKey = null, $localKey = null, $table = null) {
		if (!class_exists($related) && !is_subclass_of($related, 'Darya\Mvc\Model')) {
			throw new Exception("Related class $related does not exist or does not extend Darya\Mvc\Model");
		}
		
		$this->related = $related;
		$this->parent = $parent;
		$this->type = $type ?: static::HAS;
		
		$this->prepareKeys();
		
		$this->foreignKey = $foreignKey ?: $this->foreignKey;
		$this->localKey = $localKey ?: $this->localKey;
		$this->table = $table;
	}
	
	/**
	 * Read-only access for relation properties.
	 * 
	 * @param string $property
	 * @return mixed
	 */
	public function __get($property) {
		if (property_exists($this, $property)) {
			return $this->$property;
		}
	}
	
	/**
	 * Retrieve the key of the related model.
	 * 
	 * Returns null if the class does not extend Darya\Mvc\Model.
	 * 
	 * @return string|null
	 */
	protected function relatedKey() {
		if ($this->type === Relation::BELONGS_TO_MANY) {
			return $this->prepareForeignKey(get_class($this->parent));
		}
		
		if (is_subclass_of($this->related, 'Darya\Mvc\Model')) {
			$related = new $this->related;
			
			return $related->key();
		}
		
		return 'id';
	}
	
	/**
	 * Prepare a foreign key from the given class name.
	 * 
	 * @param string $class
	 * @return string
	 */
	protected function prepareForeignKey($class) {
		return preg_replace_callback('/([A-Z])/', function ($matches) {
			return '_' . strtolower($matches[1]);
		}, lcfirst($class)) . '_id';
	}
	
	/**
	 * Prepare the foreign key based on the direction of the relationship type.
	 * 
	 * @return string
	 */
	protected function prepareKeys() {
		switch ($this->type) {
			case Relation::HAS:
			case Relation::HAS_MANY:
				$this->foreignKey = $this->prepareForeignKey(get_class($this->parent));
				$this->localKey = $this->parent->key();
				break;
			case Relation::BELONGS_TO;
			case Relation::BELONGS_TO_MANY;
				$this->foreignKey = $this->prepareForeignKey($this->related);
				$this->localKey = $this->relatedKey();
				break;
		}
	}
	
	/**
	 * Retrieve the filter for this relation.
	 * 
	 * @return array
	 */
	public function filter() {
		switch ($this->type) {
			case Relation::HAS:
			case Relation::HAS_MANY:
				return array($this->foreignKey => $this->parent->get($this->localKey));
				break;
			case Relation::BELONGS_TO:
			case Relation::BELONGS_TO_MANY:
				return array($this->localKey => $this->parent->get($this->foreignKey));
				break;
		}
	}
	
}
