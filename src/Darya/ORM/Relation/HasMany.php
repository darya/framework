<?php
namespace Darya\ORM\Relation;

use Darya\ORM\Record;
use Darya\ORM\Relation;

/**
 * Darya's has-many entity relation.
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class HasMany extends Has {
	
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
		$ids = static::attributeList($instances, 'id');
		
		$filter = array_merge($this->filter(), array(
			$this->foreignKey => array_unique($ids)
		));
		
		$data = $this->storage()->read($this->target->table(), $filter);
		
		$class = get_class($this->target);
		$generated = $class::generate($data);
		
		$related = array();
		
		foreach ($generated as $model) {
			$key = $model->get($this->foreignKey);
			
			if (!isset($related[$key])) {
				$related[$key] = array();
			}
			
			$related[$key][] = $model;
		}
		
		foreach ($instances as $instance) {
			$key = $instance->id();
			$value = isset($related[$key]) ? $related[$key] : array();
			$instance->relation($name)->set($value);
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
	 * TODO: Stop this from assuming an ID on the instances. Somehow.
	 * 
	 * @param Record[]|Record $instances
	 * @return int
	 */
	public function associate($instances) {
		$ids = array();
		
		foreach (static::arrayify($instances) as $instance) {
			$this->replace($instance);
			
			$ids[] = $instance->id();
		}
		
		return $this->save($ids);
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
		if (empty($instances)) {
			return 0;
		}
		
		$ids = array();
		
		$successful = 0;
		
		foreach (static::arrayify($instances) as $instance) {
			$this->verify($instance);
			$instance->set($this->foreignKey, 0);
			
			if ($instance->save()) {
				$ids[] = $instance->id();
				$successful++;
			}
		}
		
		$relatedIds = array();
		
		foreach ($this->related as $related) {
			$relatedIds[] = $related->id();
		}
		
		$this->reduce(array_diff($relatedIds, $ids));
		
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
		
		return (int) $this->storage()->update($this->target->table(), array(
			$this->foreignKey => 0
		), array(
			$this->foreignKey => $this->parent->get($this->localKey)
		));
	}
	
}
