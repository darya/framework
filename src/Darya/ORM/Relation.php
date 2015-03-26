<?php
namespace Darya\ORM;

use Exception;
use Darya\ORM\Record;
use Darya\Storage\Readable;

/**
 * Darya's entity relationship representation.
 * 
 * TODO: Give each relation type their own classes.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class Relation {
	
	const HAS             = 'has';
	const HAS_MANY        = 'has_many';
	const BELONGS_TO      = 'belongs_to';
	const BELONGS_TO_MANY = 'belongs_to_many';
	
	/**
	 * @var \Darya\ORM\Record Parent model
	 */
	protected $parent;
	
	/**
	 * @var \Darya\ORM\Record Related model
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
	 * @var string Table name for "many-to-many" relations
	 */
	protected $table;
	
	/**
	 * Instantiate a new relation.
	 * 
	 * @param Record $parent
	 * @param string $type
	 * @param string $related
	 * @param string $foreignKey
	 * @param string $localKey
	 * @param string $table
	 */
	public function __construct(Record $parent, $type, $related, $foreignKey = null, $localKey = null, $table = null) {
		if (!is_subclass_of($related, 'Darya\ORM\Record')) {
			throw new Exception("Related model not does not extend Darya\ORM\Record");
		}
		
		$this->related = new $related;
		$this->parent = $parent;
		$this->type = $type ?: static::HAS;
		
		$this->setDefaultKeys();
		$this->setDefaultTable();
		
		$this->foreignKey = $foreignKey ?: $this->foreignKey;
		$this->localKey = $localKey ?: $this->localKey;
		$this->table = $table ?: $this->table;
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
	 * @return string|null
	 */
	protected function relatedKey() {
		if ($this->type === static::BELONGS_TO_MANY) {
			return $this->prepareForeignKey(get_class($this->parent));
		}
		
		return $this->related->key();
	}
	
	/**
	 * Retrieve the table of a many-to-many relation.
	 */
	public function table() {
		return $this->table;
	}
	
	/**
	 * Lowercase and delimit the given PascalCase class name.
	 * 
	 * @param string $class
	 * @return string
	 */
	protected function delimitClass($class) {
		return preg_replace_callback('/([A-Z])/', function ($matches) {
			return '_' . strtolower($matches[1]);
		}, lcfirst($class));
	}
	
	/**
	 * Prepare a foreign key from the given class name.
	 * 
	 * @param string $class
	 * @return string
	 */
	protected function prepareForeignKey($class) {
		return $this->delimitClass($class) . '_id';
	}
	
	/**
	 * Set the default foreign and local keys based on the direction of the
	 * relationship type.
	 */
	protected function setDefaultKeys() {
		if (!$this->inverse()) {
			$this->foreignKey = $this->prepareForeignKey(get_class($this->parent));
			$this->localKey = $this->parent->key();
		} else {
			$this->foreignKey = $this->prepareForeignKey(get_class($this->related));
			$this->localKey = $this->relatedKey();
		}
	}
	
	/**
	 * Set the default many-to-many relation table name.
	 * 
	 * Sorts parent and related class alphabetically.
	 */
	protected function setDefaultTable() {
		$parent = $this->delimitClass(get_class($this->parent));
		$related = $this->delimitClass(get_class($this->related));
		
		$names = array($parent, $related);
		sort($names);
		
		$this->table = implode('_', $names) . 's';
	}
	
	/**
	 * Determine whether this is an inverse (belongs-to) relation.
	 * 
	 * @return bool
	 */
	protected function inverse() {
		return $this->type === static::BELONGS_TO || $this->type === static::BELONGS_TO_MANY;
	}
	
	/**
	 * Determine whether this is a singular (has or belongs-to one) relation.
	 * 
	 * @return bool
	 */
	protected function singular() {
		return $this->type === static::HAS || $this->type === static::BELONGS_TO;
	}
	
	/**
	 * Retrieve and optionally set the storage used for the related model.
	 * 
	 * Falls back to related storage, then parent storage.
	 * 
	 * @param \Darya\Storage\Readable $storage
	 */
	public function storage(Readable $storage = null) {
		$this->storage = $storage ?: $this->storage;
		
		return $this->storage ?: $this->related->storage() ?: $this->parent->storage();
	}
	
	/**
	 * Retrieve the filter for this relation.
	 * 
	 * @return array
	 */
	public function filter() {
		if (!$this->inverse()) {
			return array($this->foreignKey => $this->parent->get($this->localKey));
		}
		
		if ($this->type === static::BELONGS_TO_MANY) {
			return array($this->localKey => $this->parent->id());
		}
		
		return array($this->localKey => $this->parent->get($this->foreignKey));
	}
	
	/**
	 * Retrieve one or many related model instances depending on the relation.
	 * 
	 * @return Record|Record[]
	 */
	public function retrieve() {
		if ($this->singular()) {
			return $this->one();
		}
		
		return $this->all();
	}
	
	/**
	 * Retrieve one related model instance.
	 * 
	 * @return Record
	 */
	public function one() {
		if (!$this->singular()) {
			return null;
		}
		
		$data = $this->storage()->read($this->related->table(), $this->filter(), null, 1);
		$class = get_class($this->related);
		
		return new $class(count($data) ? $data[0] : null);
	}
	
	/**
	 * Retrieve all related model instances.
	 * 
	 * @return array
	 */
	public function all() {
		if ($this->type === static::BELONGS_TO_MANY) {
			$relations = $this->storage()->read($this->table, $this->filter());
			
			$related = array();
			
			foreach ($relations as $relation) {
				$related[] = $relation[$this->foreignKey];
			}
			
			$data = $related ? $this->storage()->read($this->related->table(), array(
				$this->related->key() => $related
			)) : array();
		} else {
			$data = $this->storage()->read($this->related->table(), $this->filter());
		}
		
		
		$class = get_class($this->related);
		
		return $class::generate($data);
	}
	
}
