<?php
namespace Darya\ORM\Relation;

use Darya\ORM\Record;
use Darya\ORM\Relation;

/**
 * Darya's many-to-many entity relation.
 * 
 * TODO: Eager loading.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class BelongsToMany extends Relation {
	
	/**
	 * @var string Table name for "many-to-many" relations
	 */
	protected $table;
	
	/**
	 * Instantiate a new many-to-many relation.
	 * 
	 * @param Relation $parent
	 * @param string   $target
	 * @param string   $foreignKey
	 * @param string   $localKey
	 * @param string   $table
	 */
	public function __construct(Record $parent, $target, $foreignKey = null, $localKey = null, $table = null) {
		$this->localKey = $localKey;
		parent::__construct($parent, $target, $foreignKey);
		
		$this->table = $table;
		$this->setDefaultTable();
	}
	
	/**
	 * Retrieve the IDs of models that should be inserted into the relation
	 * table, given models that are already related and models that should be
	 * associated.
	 * 
	 * @param array $old
	 * @param array $new
	 * @return array
	 */
	protected static function insertIds($old, $new) {
		$oldIds = array();
		$newIds = array();
		
		foreach ($old as $instance) {
			$oldIds[] = $instance->id();
		}
		
		foreach ($new as $instance) {
			$newIds[] = $instance->id();
		}
		
		$insert = array_diff($newIds, $oldIds);
		
		return $insert;
	}
	
	/**
	 * Group foreign keys into arrays for each local key found.
	 * 
	 * Expects an array with at least local keys and foreign keys set.
	 * 
	 * Returns an adjacency list.
	 * 
	 * @param array $relations
	 * @return array
	 */
	protected function bundleRelations(array $relations) {
		$bundle = array();
		
		foreach ($relations as $relation) {
			if (!isset($bundle[$relation[$this->localKey]])) {
				$bundle[$relation[$this->localKey]] = array();
			}
			
			$bundle[$relation[$this->localKey]][] = $relation[$this->foreignKey];
		}
		
		return $bundle;
	}
	
	/**
	 * List the given instances with their IDs as keys.
	 * 
	 * @param Record[]|Record $instances
	 * @return Record[]
	 */
	protected static function listById($instances) {
		$list = array();
		
		foreach ((array) $instances as $instance) {
			$list[$instance->id()] = $instance;
		}
		
		return $list;
	}
	
	/**
	 * Set the default keys for the relation if they have not already been set.
	 */
	protected function setDefaultKeys() {
		if (!$this->foreignKey) {
			$this->foreignKey = $this->prepareForeignKey(get_class($this->target));
		}
		
		if (!$this->localKey) {
			$this->localKey = $this->prepareForeignKey(get_class($this->parent));
		}
	}
	
	/**
	 * Set the default many-to-many relation table name.
	 * 
	 * Sorts parent and related class alphabetically.
	 */
	protected function setDefaultTable() {
		if ($this->table) {
			return;
		}
		
		$parent = $this->delimitClass(get_class($this->parent));
		$target = $this->delimitClass(get_class($this->target));
		
		$names = array($parent, $target);
		sort($names);
		
		$this->table = implode('_', $names) . 's';
	}
	
	/**
	 * Retrieve the filter for the many-to-many table.
	 * 
	 * @return string
	 */
	public function filter() {
		return array($this->localKey => $this->parent->id());
	}
	
	/**
	 * Retrieve the table of the many-to-many relation.
	 * 
	 * @return string
	 */
	public function table() {
		return $this->table;
	}
	
	/**
	 * Retrieve the data of the related models.
	 * 
	 * @param int $limit
	 * @return array
	 */
	public function load($limit = null) {
		$relations = $this->storage()->read($this->table, $this->filter(), null, $limit);
		
		$related = array();
		
		foreach ($relations as $relation) {
			$related[] = $relation[$this->foreignKey];
		}
		
		return $this->storage()->read($this->target->table(), array(
			$this->target->key() => $related
		));
	}
	
	/**
	 * Eagerly load the related models for the given parent instances.
	 * 
	 * Returns the given instances with their related models loaded.
	 * 
	 * @param array $instances
	 * @param string $name TODO: Remove this and store as a property
	 * @return array
	 */
	public function eager(array $instances, $name) {
		$this->verifyParents($instances);
		
		// Grab IDs of parent instances
		$ids = static::attributeList($instances, $this->parent->key());
		
		// Build the filter for the relations
		$filter = array_merge($this->filter(), array($this->localKey => $ids));
		
		// Read their relations from the table
		$relations = $this->storage()->read($this->table, $filter);
		
		// Unique list of target keys
		$relatedIds = static::attributeList($relations, $this->foreignKey);
		$relatedIds = array_unique($relatedIds);
		
		// Adjacency list of parent keys to target keys
		$relationBundle = $this->bundleRelations($relations);
		
		// Data of relations
		$data = $this->storage()->read($this->target->table(), array(
			$this->target->key() => $relatedIds
		));
		
		// Instances of relations from the data
		$class = get_class($this->target);
		$generated = $class::generate($data);
		
		// Set IDs as the keys of the relation instances
		$list = static::listById($generated);
		
		// Attach the related instances using the relation adjacency list
		foreach ($instances as $instance) {
			$instanceRelations = array();
			
			foreach ($relationBundle[$instance->id()] as $relationId) {
				$instanceRelations[] = $list[$relationId];
			}
			
			$instance->relation($name)->set($instanceRelations);
		}
		
		return $instances;
	}
	
	/**
	 * Retrieve the related models.
	 * 
	 * @return Record[]
	 */
	public function retrieve() {
		return $this->all();
	}
	
	/**
	 * Associate the given models.
	 * 
	 * Returns the number of models successfully associated.
	 * 
	 * @param Record[]|Record $instances
	 * @return int
	 */
	public function associate($instances) {
		$instances = static::arrayify($instances);
		
		$existing = $this->storage()->read($this->table, array(
			$this->localKey => $this->parent->id()
		));
		
		$successful = 0;
		
		foreach ($instances as $instance) {
			$this->verify($instance);
			
			if ($instance->save()) {
				$successful++;
				$this->replace($instance);
				
				if (!in_array($instance->id(), $existing)) {
					$this->storage()->create($this->table, array(
						$this->localKey   => $this->parent->id(),
						$this->foreignKey => $instance->id()
					));
				}
			};
		}
		
		return $successful;
	}
	
	/**
	 * Dissociate the given models.
	 * 
	 * Returns the number of models successfully dissociated.
	 * 
	 * @param Record[]|Record $instances [optional]
	 * @return int
	 */
	public function dissociate($instances = null) {
		$instances = static::arrayify($instances);
		
		$ids = array();
		
		$this->verify($instances);
		
		foreach ($instances as $instance) {
			$ids[] = $instance->id();
		}
		
		$successful = $this->storage()->delete($this->table, array(
			$this->localKey => $this->parent->id(),
			$this->foreignKey => $ids
		));
		
		$this->reduce($ids);
		
		return (int) $successful;
	}
	
	/**
	 * Dissociate all currently associated models.
	 * 
	 * Returns the number of models successfully dissociated.
	 * 
	 * @return int
	 */
	public function purge() {
		$this->related = array();
		
		return (int) $this->storage()->delete($this->table, array(
			$this->localKey => $this->parent->id()
		));
	}
	
	/**
	 * Dissociate all models and associate the given models.
	 * 
	 * Returns the number of models successfully associated.
	 * 
	 * @param Record[]|Record $instances [optional]
	 * @return int
	 */
	public function sync($instances) {
		$this->purge();
		
		return $this->associate($instances);
	}
	
	/**
	 * Count the number of related model instances.
	 * 
	 * Counts loaded instances if they are present, queries storage otherwise.
	 * 
	 * @return int
	 */
	public function count() {
		if ($this->related) {
			return parent::count();
		}
		
		return $this->storage()->count($this->table, $this->filter());
	}
	
}
