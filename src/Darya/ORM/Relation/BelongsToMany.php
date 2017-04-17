<?php
namespace Darya\ORM\Relation;

use Darya\ORM\Record;
use Darya\ORM\Relation;

/**
 * Darya's many-to-many entity relation.
 * 
 * @property-read array  $associationConstraint
 * @property-read string $table
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
class BelongsToMany extends Relation
{
	/**
	 * @var array
	 */
	protected $associationConstraint = array();
	
	/**
	 * @var string Table name for "many-to-many" relations
	 */
	protected $table;
	
	/**
	 * Instantiate a new many-to-many relation.
	 * 
	 * @param Record $parent
	 * @param string $target
	 * @param string $foreignKey            [optional]
	 * @param string $localKey              [optional]
	 * @param string $table                 [optional]
	 * @param array  $constraint            [optional]
	 * @param array  $associationConstraint [optional]
	 */
	public function __construct(
			Record $parent,
			$target,
			$foreignKey = null,
			$localKey = null,
			$table = null,
			array $constraint = array(),
			array $associationConstraint = array())
	{
		$this->localKey = $localKey;
		parent::__construct($parent, $target, $foreignKey);
		
		$this->table = $table;
		$this->setDefaultTable();
		$this->constrain($constraint);
		$this->constrainAssociation($associationConstraint);
	}
	
	/**
	 * Retrieve the IDs of models that should be inserted into the relation
	 * table, given models that are already related and models that should be
	 * associated.
	 * 
	 * Returns the difference of the IDs of each set of instances.
	 * 
	 * @param array $old
	 * @param array $new
	 * @return array
	 */
	protected static function insertIds($old, $new)
	{
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
	 * Returns an adjacency list of local keys to related foreign keys.
	 * 
	 * @param array $relations
	 * @return array
	 */
	protected function bundleRelations(array $relations)
	{
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
	 * @param Record[]|Record|array $instances
	 * @return Record[]
	 */
	protected static function listById($instances)
	{
		$list = array();
		
		foreach ((array) $instances as $instance) {
			$list[$instance->id()] = $instance;
		}
		
		return $list;
	}
	
	/**
	 * Set the default keys for the relation if they have not already been set.
	 */
	protected function setDefaultKeys()
	{
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
	 * Sorts parent and related class names alphabetically.
	 */
	protected function setDefaultTable()
	{
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
	 * Retrieve the default filter for the association table.
	 * 
	 * @return array
	 */
	protected function defaultAssociationConstraint()
	{
		return array($this->localKey => $this->parent->id());
	}
	
	/**
	 * Set a filter to constrain the association table.
	 * 
	 * @param array $filter
	 */
	public function constrainAssociation(array $filter)
	{
		$this->associationConstraint = $filter;
	}
	
	/**
	 * Retrieve the custom filter used to constrain the association table.
	 * 
	 * @return array
	 */
	public function associationConstraint()
	{
		return $this->associationConstraint;
	}
	
	/**
	 * Retrieve the filter for the association table.
	 * 
	 * @return array
	 */
	public function associationFilter()
	{
		return array_merge($this->defaultAssociationConstraint(), $this->associationConstraint());
	}
	
	/**
	 * Retrieve the filter for the related models.
	 * 
	 * Optionally accepts a list of related IDs to filter by.
	 * 
	 * @param array $related
	 * @return array
	 */
	public function filter(array $related = array())
	{
		$filter = array();

		// First filter by the currently related IDs if none are given
		$filter[$this->target->key()] = empty($related) ? $this->relatedIds() : $related;

		// Also filter by constraints
		$filter = array_merge($filter, $this->constraint());
		
		return $filter;
	}
	
	/**
	 * Retrieve and optionally set the table of the many-to-many relation.
	 * 
	 * @param string $table [optional]
	 * @return string
	 */
	public function table($table = null)
	{
		$this->table = (string) $table ?: $this->table;
		
		return $this->table;
	}
	
	/**
	 * Retrieve the related IDs from the association table.
	 * 
	 * Takes into consideration the regular relation filter, if it's not empty,
	 * and loads IDs from the target table accordingly.
	 * 
	 * @param int $limit
	 * @return int[]
	 */
	protected function relatedIds($limit = 0)
	{
		// Read the associations from the relation table
		$associations = $this->storage()->read($this->table, $this->associationFilter(), null, $limit);
		$associatedIds = static::attributeList($associations, $this->foreignKey);

		// If there's no constraint for the target table then we're done
		if (empty($this->constraint())) {
			return $associatedIds;
		}

		// Create the filter for the target table
		$filter = array();

		$filter[$this->target->key()] = $associatedIds;

		$filter = array_merge($filter, $this->constraint());

		// Load the matching related IDs from the target table
		$related = $this->storage()->listing($this->target->table(), $this->target->key(), $filter, null, $limit);
		
		return static::attributeList($related, $this->target->key());
	}
	
	/**
	 * Retrieve the data of the related models.
	 * 
	 * @param int $limit
	 * @return array
	 */
	public function read($limit = 0)
	{
		return $this->storage()->read(
			$this->target->table(),
			array(
				$this->target->key() => $this->relatedIds($limit)
			),
			$this->order()
		);
	}
	
	/**
	 * Eagerly load the related models for the given parent instances.
	 * 
	 * Returns the given instances with their related models loaded.
	 * 
	 * @param array $instances
	 * @return array
	 */
	public function eager(array $instances)
	{
		$this->verifyParents($instances);
		
		// Grab IDs of parent instances
		$ids = static::attributeList($instances, $this->parent->key());
		
		// Build the filter for the association table
		$filter = array_merge($this->associationFilter(), array(
			$this->localKey => $ids
		));
		
		// Read the relations from the table
		$relations = $this->storage()->read($this->table, $filter);
		
		// Unique list of target keys
		$relatedIds = static::attributeList($relations, $this->foreignKey);
		$relatedIds = array_unique($relatedIds);
		
		// Adjacency list of parent keys to target keys
		$relationBundle = $this->bundleRelations($relations);
		
		// Build the filter for the related models
		$filter = $this->filter($relatedIds);
		
		// Data of relations
		$data = $this->storage()->read($this->target->table(), $filter, $this->order());
		
		// Instances of relations from the data
		$class = get_class($this->target);
		$generated = $class::generate($data);
		
		// Set IDs as the keys of the relation instances
		$list = static::listById($generated);
		
		// Attach the related instances using the relation adjacency list
		foreach ($instances as $instance) {
			$instanceRelations = array();
			
			// TODO: Find a way to drop these issets
			if (isset($relationBundle[$instance->id()])) {
				foreach ($relationBundle[$instance->id()] as $relationId) {
					if (isset($list[$relationId])) {
						$instanceRelations[] = $list[$relationId];
					}
				}
			}
			
			$instance->relation($this->name)->set($instanceRelations);
		}
		
		return $instances;
	}
	
	/**
	 * Retrieve the related models.
	 * 
	 * @return Record[]
	 */
	public function retrieve()
	{
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
	public function associate($instances)
	{
		$this->verify($instances);
		
		$this->load();
		
		$this->attach($instances);
		
		$existing = $this->storage()->distinct($this->table, $this->foreignKey, array(
			$this->localKey => $this->parent->id()
		));
		
		$successful = 0;
		
		foreach ($this->related as $instance) {
			if ($instance->save()) {
				$successful++;
				$this->replace($instance);
				
				// Create the association in the relation table if it doesn't
				// yet exist
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
	 * Dissociate the given models from the parent model.
	 * 
	 * Returns the number of models successfully dissociated.
	 * 
	 * @param Record[]|Record $instances [optional]
	 * @return int
	 */
	public function dissociate($instances = array())
	{
		$instances = static::arrayify($instances);
		
		$ids = array();
		
		$this->verify($instances);
		
		foreach ($instances as $instance) {
			$ids[] = $instance->id();
		}
		
		$ids = array_intersect($ids, $this->relatedIds());
		
		$successful = $this->storage()->delete($this->table, array_merge(
			$this->associationFilter(),
			array($this->foreignKey => $ids)
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
	public function purge()
	{
		$this->clear(); // Force a reload because diffing would be a pain
		
		return (int) $this->storage()->delete($this->table, array(
			$this->foreignKey => $this->relatedIds()
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
	public function sync($instances)
	{
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
	public function count()
	{
		if (!$this->loaded() && empty($this->related)) {
			if (empty($this->filter())) {
				return $this->storage()->count($this->table, $this->associationFilter());
			}
			
			$filter = $this->filter($this->relatedIds());
			
			return $this->storage()->count($this->target->table(), $filter);
		}
		
		return parent::count();
	}
}
