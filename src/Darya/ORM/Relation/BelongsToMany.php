<?php
namespace Darya\ORM\Relation;

use Darya\ORM\Record;
use Darya\ORM\Relation;

/**
 * Darya's many-to-many entity relation.
 * 
 * TODO: Association, dissociation, syncing.
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
	 * @param Record[] $old
	 * @param Record[] $new
	 * @return array
	 */
	protected static function insertIds($old, $new) {
		$oldIds = array();
		
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
		$insert = static::insertIds($this->retrieve(), $instances);
		
		$successful = 0;
		
		foreach (static::arrayify($instances) as $instance) {
			$this->verify($instance);
			
			if (in_array($instance->id(), $insert)) {
				$this->storage()->create($this->table, array(
					$this->localKey   => $this->parent->id(),
					$this->foreignKey => $instance->id()
				));
			}
			
			if ($instance->save()) {
				$successful++;
				$this->replace($instance);
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
		
		$keys = array();
		
		foreach ($this->related as $key => $instance) {
			if (!in_array($instance->id(), $ids)) {
				$keys[] = $key;
			}
		}
		
		$this->related = array_intersect_key($this->related, array_flip($keys));
		
		return $successful;
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
		
		return (int) $this->storage()->delete(array(
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
	
}
