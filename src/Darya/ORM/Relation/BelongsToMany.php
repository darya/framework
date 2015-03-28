<?php
namespace Darya\ORM\Relation;

use Darya\ORM\Record;
use Darya\ORM\Relation;

/**
 * Darya's many-to-many entity relation.
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
		
		$parent = $this->delimitClass(basename(get_class($this->parent)));
		$target = $this->delimitClass(basename(get_class($this->target)));
		
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
	
	public function retrieve() {
		return $this->all();
	}
	
}
