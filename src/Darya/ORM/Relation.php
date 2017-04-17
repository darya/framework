<?php
namespace Darya\ORM;

use Darya\ORM\Record;
use Darya\Storage\Readable;
use Darya\Storage\Modifiable;
use Darya\Storage\Queryable;
use Darya\Storage\Query\Builder;
use Exception;
use InvalidArgumentException;
use ReflectionClass;

/**
 * Darya's abstract entity relation.
 * 
 * TODO: errors() method.
 * TODO: Filter, order, limit, offset for load() and retrieve().
 * TODO: Shouldn't delimitClass() and prepareForeignKey() be static?
 * TODO: Separate filters for reading and updating/deleting (readFilter(), modifyFilter()) mainly for BelongsToMany
 * 
 * @property-read string    $name
 * @property-read Record    $parent
 * @property-read Record    $target
 * @property-read string    $foreignKey
 * @property-read string    $localKey
 * @property-read array     $constraint
 * @property-read Record[]  $related
 * @property-read bool      $loaded
 * @property-read Queryable $storage
 * 
 * @author Chris Andrew <chris@hexus.io>
 */
abstract class Relation
{
	const HAS             = 'has';
	const HAS_MANY        = 'has_many';
	const BELONGS_TO      = 'belongs_to';
	const BELONGS_TO_MANY = 'belongs_to_many';
	
	/**
	 * A map of relation type constants to their respective implementations.
	 * 
	 * @var array
	 */
	protected static $classMap = array(
		self::HAS             => 'Darya\ORM\Relation\Has',
		self::HAS_MANY        => 'Darya\ORM\Relation\HasMany',
		self::BELONGS_TO      => 'Darya\ORM\Relation\BelongsTo',
		self::BELONGS_TO_MANY => 'Darya\ORM\Relation\BelongsToMany',
	);
	
	/**
	 * The name of the relation in the context of the parent model.
	 * 
	 * @var string
	 */
	protected $name = '';
	
	/**
	 * The parent model.
	 * 
	 * @var Record
	 */
	protected $parent;
	
	/**
	 * The target model.
	 * 
	 * @var Record
	 */
	protected $target;
	
	/**
	 * Foreign key on the "belongs-to" model.
	 * 
	 * @var string
	 */
	protected $foreignKey;
	
	/**
	 * Local key on the "has" model.
	 * 
	 * @var string
	 */
	protected $localKey;
	
	/**
	 * Filter for constraining related models loaded from storage.
	 * 
	 * @var array
	 */
	protected $constraint = array();
	
	/**
	 * Sort order for related models.
	 * 
	 * @var array
	 */
	protected $sort = array();
	
	/**
	 * The related instances.
	 * 
	 * @var Record[]
	 */
	protected $related = array();
	
	/**
	 * Detached instances that need dissociating on save.
	 * 
	 * @var Record[]
	 */
	protected $detached = array();
	
	/**
	 * Determines whether related instances have been loaded.
	 * 
	 * @var bool
	 */
	protected $loaded = false;
	
	/**
	 * The storage interface.
	 * 
	 * @var Queryable
	 */
	protected $storage;
	
	/**
	 * Helper method for methods that accept single or multiple values, or for
	 * just casting to an array without losing a plain object.
	 * 
	 * Returns an array with the given value as its sole element, if it is not
	 * an array already.
	 *
	 * This exists because casting an object to an array results in its public
	 * properties being set as the values.
	 * 
	 * @param mixed $value
	 * @return array
	 */
	protected static function arrayify($value)
	{
		return !is_array($value) ? array($value) : $value;
	}
	
	/**
	 * Separate array elements with numeric keys from those with string keys.
	 * 
	 * @param array $array
	 * @return array array($numeric, $strings)
	 */
	protected static function separateKeys(array $array)
	{
		$numeric = array();
		$strings = array();
		
		foreach ($array as $key => $value) {
			if (is_numeric($key)) {
				$numeric[$key] = $value;
			} else {
				$strings[$key] = $value;
			}
		}
		
		return array($numeric, $strings);
	}
	
	/**
	 * Resolve a relation class name from the given relation type constant.
	 * 
	 * @param string $type
	 * @return string
	 */
	protected static function resolveClass($type)
	{
		if (isset(static::$classMap[$type])) {
			return static::$classMap[$type];
		}
		
		return static::$classMap[static::HAS];
	}
	
	/**
	 * Create a new relation of the given type using the given arguments.
	 * 
	 * Applies numerically-keyed arguments to the constructor and string-keyed
	 * arguments to methods with the same name.
	 * 
	 * @param string $type
	 * @param array  $arguments
	 * @return Relation
	 */
	public static function factory($type = self::HAS, array $arguments)
	{
		$class = static::resolveClass($type);
		
		$reflection = new ReflectionClass($class);
		
		list($arguments, $named) = static::separateKeys($arguments);

		/**
		 * @var Relation $instance
		 */
		$instance = $reflection->newInstanceArgs($arguments);
		
		foreach ($named as $method => $argument) {
			if (method_exists($instance, $method)) {
				$argument = static::arrayify($argument);
				call_user_func_array(array($instance, $method), $argument);
			}
		}
		
		return $instance;
	}
	
	/**
	 * Instantiate a new relation.
	 * 
	 * @param Record $parent     Parent class
	 * @param string $target     Related class that extends \Darya\ORM\Record
	 * @param string $foreignKey [optional] Custom foreign key
	 * @param array  $constraint [optional] Constraint filter for related models
	 * @throws InvalidArgumentException
	 */
	public function __construct(Record $parent, $target, $foreignKey = null, array $constraint = array())
	{
		if (!is_subclass_of($target, 'Darya\ORM\Record')) {
			throw new InvalidArgumentException('Target class not does not extend Darya\ORM\Record');
		}
		
		$this->parent = $parent;
		$this->target = !is_object($target) ? new $target : $target;
		
		$this->foreignKey = $foreignKey;
		$this->setDefaultKeys();
		$this->constrain($constraint);
	}
	
	/**
	 * Lowercase and delimit the given PascalCase class name.
	 * 
	 * @param string $class
	 * @return string
	 */
	protected function delimitClass($class)
	{
		$split = explode('\\', $class);
		$class = end($split);
		
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
	protected function prepareForeignKey($class)
	{
		return $this->delimitClass($class) . '_id';
	}
	
	/**
	 * Retrieve the default filter for the related models.
	 * 
	 * @return array
	 */
	protected function defaultConstraint()
	{
		return array(
			$this->foreignKey => $this->parent->id()
		);
	}
	
	/**
	 * Set the default keys for the relation if they haven't already been set.
	 */
	abstract protected function setDefaultKeys();
	
	/**
	 * Retrieve the values of the given attribute of the given instances.
	 * 
	 * Works similarly to array_column(), but doesn't return data from any rows
	 * without the given attribute set.
	 * 
	 * Optionally accepts a second attribute to index by.
	 * 
	 * @param Record[]|Record|array $instances
	 * @param string                $attribute
	 * @param string                $index     [optional]
	 * @return array
	 */
	protected static function attributeList($instances, $attribute, $index = null)
	{
		$values = array();
		
		foreach (static::arrayify($instances) as $instance) {
			if (isset($instance[$attribute])) {
				if ($index !== null) {
					$values[$instance[$index]] = $instance[$attribute];
				} else {
					$values[] = $instance[$attribute];
				}
			}
		}
		
		return $values;
	}
	
	/**
	 * Build an adjacency list of related models, indexed by their foreign keys.
	 * 
	 * Optionally accepts a different attribute to index the models by.
	 * 
	 * @param Record[] $instances
	 * @param string   $index     [optional]
	 * @return array
	 */
	protected function adjacencyList(array $instances, $index = null)
	{
		$index = $index ?: $this->foreignKey;
		
		$related = array();
		
		foreach ($instances as $instance) {
			$related[$instance->get($index)][] = $instance;
		}
		
		return $related;
	}
	
	/**
	 * Reduce the cached related models to those with the given IDs.
	 * 
	 * If no IDs are given then all of the in-memory models will be removed.
	 * 
	 * @param int[] $ids
	 */
	protected function reduce(array $ids = array())
	{
		if (empty($this->related)) {
			return;
		}
		
		$keys = array();
		
		foreach ($this->related as $key => $instance) {
			if (!in_array($instance->id(), $ids)) {
				$keys[$key] = null;
			}
		}
		
		$this->related = array_values(array_diff_key($this->related, $keys));
	}
	
	/**
	 * Replace a cached related model.
	 * 
	 * If the related model does not have an ID or it is not found, it is simply
	 * appended.
	 * 
	 * TODO: Remove from $this->detached if found?
	 * 
	 * @param Record $instance
	 */
	protected function replace(Record $instance)
	{
		$this->verify($instance);
		
		if (!$instance->id()) {
			$this->related[] = $instance;
			
			return;
		}
		
		foreach ($this->related as $key => $related) {
			if ($related->id() === $instance->id() || $related === $instance) {
				$this->related[$key] = $instance;
				
				return;
			}
		}
		
		$this->related[] = $instance;
	}
	
	/**
	 * Save the given record to storage if it hasn't got an ID.
	 * 
	 * @param Record $instance
	 */
	protected function persist(Record $instance)
	{
		if (!$instance->id()) {
			$instance->save();
		}
	}
	
	/**
	 * Verify that the given models are instances of the relation's target
	 * class.
	 * 
	 * Throws an exception if any of them aren't.
	 * 
	 * @param Record[]|Record $instances
	 * @throws Exception
	 */
	protected function verify($instances)
	{
		static::verifyModels($instances, get_class($this->target));
	}
	
	/**
	 * Verify that the given objects are instances of the given class.
	 * 
	 * @param object[]|object $instances
	 * @param string          $class
	 * @throws Exception
	 */
	protected static function verifyModels($instances, $class)
	{
		if (!class_exists($class)) {
			return;
		}
		
		foreach (static::arrayify($instances) as $instance) {
			if (!$instance instanceof $class) {
				throw new Exception('Related models must be an instance of ' . $class);
			}
		}
	}
	
	/**
	 * Verify that the given models are instances of the relation's parent
	 * class.
	 * 
	 * Throws an exception if any of them aren't.
	 * 
	 * @param Record[]|Record $instances
	 * @throws Exception
	 */
	protected function verifyParents($instances)
	{
		static::verifyModels($instances, get_class($this->parent));
	}
	
	/**
	 * Retrieve and optionally set the storage used for the target model.
	 * 
	 * Falls back to target model storage, then parent model storage.
	 * 
	 * @param Queryable $storage
	 * @return Queryable
	 */
	public function storage(Queryable $storage = null)
	{
		$this->storage = $storage ?: $this->storage;
		
		return $this->storage ?: $this->target->storage() ?: $this->parent->storage();
	}
	
	/**
	 * Retrieve and optionally set the name of the relation on the parent model.
	 * 
	 * @param string $name [optional]
	 * @return string
	 */
	public function name($name = '')
	{
		$this->name = (string) $name ?: $this->name;
		
		return $this->name;
	}
	
	/**
	 * Retrieve and optionally set the foreign key for the "belongs-to" model.
	 * 
	 * @param string $foreignKey [optional]
	 * @return string
	 */
	public function foreignKey($foreignKey = '')
	{
		$this->foreignKey = (string) $foreignKey ?: $this->foreignKey;
		
		return $this->foreignKey;
	}
	
	/**
	 * Retrieve and optionally set the local key for the "has" model.
	 * 
	 * @param string $localKey [optional]
	 * @return string
	 */
	public function localKey($localKey = '')
	{
		$this->localKey = (string) $localKey ?: $this->localKey;
		
		return $this->localKey;
	}
	
	/**
	 * Set a filter to constrain which models are considered related.
	 * 
	 * @param array $filter
	 */
	public function constrain(array $filter)
	{
		$this->constraint = $filter;
	}
	
	/**
	 * Retrieve the custom filter used to constrain related models.
	 * 
	 * @return array
	 */
	public function constraint()
	{
		return $this->constraint;
	}
	
	/**
	 * Retrieve the filter for this relation.
	 * 
	 * @return array
	 */
	public function filter()
	{
		return array_merge($this->defaultConstraint(), $this->constraint());
	}
	
	/**
	 * Set the sorting order for this relation.
	 * 
	 * @param array|string $order
	 * @return array|string
	 */
	public function sort($order)
	{
		return $this->sort = $order;
	}
	
	/**
	 * Retrieve the order for this relation.
	 * 
	 * @return array|string
	 */
	public function order()
	{
		return $this->sort;
	}
	
	/**
	 * Read related model data from storage.
	 * 
	 * TODO: $filter, $order, $offset
	 * 
	 * @param int $limit [optional]
	 * @return array
	 */
	public function read($limit = 0)
	{
		return $this->storage()->read($this->target->table(), $this->filter(), $this->order(), $limit);
	}

	/**
	 * Query related model data from storage.
	 *
	 * @return Builder
	 */
	public function query()
	{
		$class = get_class($this->target);

		$builder = $this->storage()->query($this->target->table())
			->filters($this->filter())
			->orders($this->order());

		$builder->callback(function ($result) use ($class) {
			return $class::hydrate($result->data);
		});

		return $builder;
	}
	
	/**
	 * Read, generate and set cached related models from storage.
	 * 
	 * This will completely replace any cached related models.
	 * 
	 * @param int $limit [optional]
	 * @return Record[]
	 */
	public function load($limit = 0)
	{
		$data = $this->read($limit);
		$class = get_class($this->target);
		$this->related = $class::generate($data);
		$this->loaded = true;
		
		return $this->related;
	}
	
	/**
	 * Determine whether cached related models have been loaded from storage.
	 * 
	 * @return bool
	 */
	public function loaded()
	{
		return $this->loaded;
	}
	
	/**
	 * Eagerly load the related models for the given parent instances.
	 * 
	 * Returns the given instances with their related models loaded.
	 * 
	 * @param array $instances
	 * @return array
	 */
	abstract public function eager(array $instances);
	
	/**
	 * Retrieve one or many related model instances, depending on the relation.
	 * 
	 * @return Record[]|Record|null
	 */
	abstract public function retrieve();
	
	/**
	 * Retrieve one related model instance.
	 * 
	 * @return Record|null
	 */
	public function one()
	{
		if (!$this->loaded() && empty($this->related)) {
			$this->load(1);
		}
		
		// TODO: Load and merge with cached?
		
		return !empty($this->related) ? $this->related[0] : null;
	}
	
	/**
	 * Retrieve all related model instances.
	 * 
	 * @return Record[]|null
	 */
	public function all()
	{
		if (!$this->loaded() && empty($this->related)) {
			$this->load();
		}
		
		// TODO: Load and merge with cached?
		
		return $this->related;
	}
	
	/**
	 * Count the number of related model instances.
	 * 
	 * Counts loaded or attached instances if they are present, queries storage
	 * otherwise.
	 * 
	 * @return int
	 */
	public function count()
	{
		if (!$this->loaded() && empty($this->related)) {
			return $this->storage()->count($this->target->table(), $this->filter());
		}
		
		return count($this->related);
	}
	
	/**
	 * Set the related models.
	 * 
	 * Overwrites any currently set related models.
	 * 
	 * @param Record[] $instances
	 */
	public function set($instances)
	{
		$this->verify($instances);
		$this->related = static::arrayify($instances);
		$this->loaded = true;
	}
	
	/**
	 * Clear the related models.
	 */
	public function clear()
	{
		$this->related = array();
		$this->loaded = false;
	}
	
	/**
	 * Attach the given models.
	 * 
	 * @param Record[]|Record $instances
	 */
	public function attach($instances)
	{
		$this->verify($instances);
		
		foreach (static::arrayify($instances) as $instance) {
			$this->replace($instance);
		}
	}
	
	/**
	 * Detach the given models.
	 * 
	 * Detaches all attached models if none are given.
	 * 
	 * @param Record[]|Record $instances [optional]
	 */
	public function detach($instances = array())
	{
		$this->verify($instances);
		
		$instances = static::arrayify($instances) ?: $this->related;
		
		$relatedIds = static::attributeList($this->related, 'id');
		$detached = array();
		$ids = array();
		
		// Collect the IDs and instances of the models to be detached
		foreach ($instances as $instance) {
			if (in_array($instance->id(), $relatedIds)) {
				$ids[] = $instance->id();
				$detached[] = $instance;
			}
		}
		
		// Reduce related models to those that haven't been detached
		$this->reduce(array_diff($relatedIds, $ids));
		
		// Merge the newly detached models in with the existing ones
		$this->detached = array_merge($this->detached, $detached);
	}
	
	/**
	 * Associate the given models.
	 * 
	 * Returns the number of models successfully associated.
	 * 
	 * @param Record[]|Record $instances
	 * @return int
	 */
	abstract public function associate($instances);
	
	/**
	 * Dissociate the given models.
	 * 
	 * Returns the number of models successfully dissociated.
	 * 
	 * @param Record[]|Record $instances [optional]
	 * @return int
	 */
	abstract public function dissociate($instances = array());
	
	/**
	 * Save the relationship.
	 * 
	 * Associates related models and dissociates detached models.
	 * 
	 * Optionally accepts a set of IDs to save by. Saves all related models
	 * otherwise.
	 * 
	 * Returns the number of associated models.
	 * 
	 * @param int[] $ids
	 * @return int
	 */
	public function save(array $ids = array())
	{
		$related = $this->related;
		$detached = $this->detached;
		
		// Filter the IDs to associate and dissociate if any have been given
		if (!empty($ids)) {
			$filter = function ($instance) use ($ids) {
				return in_array($instance->id(), $ids);
			};
			
			$related = array_filter($related, $filter);
			$detached = array_filter($detached, $filter);
		}
		
		// Bail if we have nothing to associate or dissociate
		if (empty($related) && empty($detached)) {
			return 0;
		}
		
		// Dissociate, then associate
		if  (!empty($detached)) {
			$this->dissociate($detached);
		}

		$associated = $this->associate($related);
		
		// Update detached models to be persisted
		$this->detached = array();
		
		// Persist relationships on all related models
		foreach ($related as $instance) {
			$instance->saveRelations();
		}
		
		return $associated;
	}
	
	/**
	 * Dynamic read-only access for relation properties.
	 * 
	 * @param string $property
	 * @return mixed
	 */
	public function __get($property)
	{
		if (property_exists($this, $property)) {
			return $this->$property;
		}
	}
}
