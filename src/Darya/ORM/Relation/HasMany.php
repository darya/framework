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
	 * Eagerly load the related models of the given parent instances.
	 * 
	 * Retrieves the related models without matching them to their parents.
	 * 
	 * @param array $instances
	 * @return array
	 */
	public function eagerLoad(array $instances)
	{
		$this->verifyParents($instances);
		$ids = static::attributeList($instances, 'id');
		
		$filter = array_merge($this->filter(), array(
			$this->foreignKey => array_unique($ids)
		));
		
		$data = $this->storage()->read($this->target->table(), $filter, $this->order());
		
		return $this->target->generate($data);
	}
	
	/**
	 * Eagerly load and match the related models for the given parent instances.
	 * 
	 * Returns the given instances with their related models loaded.
	 * 
	 * @param array $instances
	 * @return array
	 */
	public function eager(array $instances) {
		if ($this->parent instanceof $this->target) {
			return $this->eagerSelf($instances);
		}
		
		$related = $this->eagerLoad($instances);
		
		$instances = $this->match($instances, $related);
		
		return $instances;
	}
	
	/**
	 * Eagerly load the related models from the same table.
	 * 
	 * This continues to load the same relation recursively.
	 * 
	 * @param array $instances
	 * @return array
	 */
	protected function eagerSelf(array $instances)
	{
		$parents = $instances;
		
		while ($related = $this->eagerLoad($parents)) {
			$this->match($parents, $related);
			
			$parents = $related;
		}
		
		$this->match($parents, array());
		
		return $instances;
	}
	
	/**
	 * Match the given related models to their parent instances.
	 * 
	 * @param Record[] $instances
	 * @param Record[] $related
	 * @return Record[]
	 */
	protected function match(array $instances, array $related) {
		$list = $this->adjacencyList($related);
		
		foreach ($instances as $instance) {
			$key = $instance->id();
			$value = isset($list[$key]) ? $list[$key] : array();
			$instance->relation($this->name)->set($value);
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
	 * TODO: Stop this from assuming an ID on the instances. Somehow. Maybe save
	 *       if it doesn't have one yet, or don't use IDs at the risk of saving
	 *       more relations than necessary (all of them...).
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
	 * TODO: Consider constraints
	 * 
	 * @param Record[]|Record $instances [optional]
	 * @return int
	 */
	public function dissociate($instances = array()) {
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
	 * TODO: Consider constraints
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
