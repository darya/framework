<?php
namespace Darya\ORM\Relation;

use Darya\ORM\Record;
use Darya\ORM\Relation;

/**
 * Darya's belongs-to entity relation.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class BelongsTo extends Relation {
	
	/**
	 * Set the default keys for the relation if they have not yet been set.
	 */
	protected function setDefaultKeys() {
		if (!$this->foreignKey) {
			$this->foreignKey = $this->prepareForeignKey(get_class($this->target));
		}
		
		$this->localKey = $this->target->key();
	}
	
	/**
	 * Replace the in-memory related model with the given instance.
	 * 
	 * @param \Darya\ORM\Record $instance
	 */
	protected function replace(Record $instance) {
		$this->related = array($instance);
	}
	
	/**
	 * Retrieve the filter for this relation.
	 * 
	 * @return array
	 */
	public function filter() {
		return array($this->target->key() => $this->parent->get($this->foreignKey));
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
		$ids = array();
		$this->verifyParents($instances);
		
		foreach ($instances as $instance) {
			$ids[] = $instance->get($this->foreignKey);
		}
		
		$filter = array($this->localKey => array_unique($ids));
		$data = $this->storage()->read($this->target->table(), $filter);
		$class = get_class($this->target);
		$generated = $class::generate($data);
		$related = array();
		
		foreach ($generated as $model) {
			$related[$model->id()] = $model;
		}
		
		foreach ($instances as $instance) {
			$key = $instance->get($this->foreignKey);
			
			if (isset($related[$key])) {
				$instance->set($name, $related[$key]);
			}
		}
		
		return $instances;
	}
	
	/**
	 * Retrieve the related model.
	 * 
	 * @return \Darya\ORM\Record
	 */
	public function retrieve() {
		if ($this->parent->get($this->foreignKey)) {
			return $this->one();
		}
	}
	
	/**
	 * Associate the given model.
	 * 
	 * @param \Darya\ORM\Record $instance
	 * @return bool
	 */
	public function associate(Record $instance) {
		$this->verify($instance);
		$this->parent->set($this->foreignKey, $instance->id());
		
		return $this->parent->save();
	}
	
	/**
	 * Dissociate the related model.
	 * 
	 * @return bool
	 */
	public function dissociate() {
		$this->parent->set($this->foreignKey, 0);
		
		return $this->parent->save();
	}
	
}
