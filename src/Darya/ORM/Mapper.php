<?php
namespace Darya\ORM;

use Darya\Storage\Query;
use Darya\Storage\Queryable;
use Darya\Storage\Result;
use ReflectionClass;

/**
 * Darya's entity mapper.
 *
 * Maps an entity to a queryable storage.
 *
 * TODO: newInstanceWithInjection() method and a $container property with setter method
 * TODO: Entity factory for instantiation; could allow dynamically defined entities
 *
 * @author Chris Andrew <chris@hexus.io>
 */
class Mapper
{
	/**
	 * The EntityMap to map to storage.
	 *
	 * @var EntityMap
	 */
	protected $entityMap;

	/**
	 * The storage to map to.
	 *
	 * @var Queryable
	 */
	protected $storage;

	/**
	 * Create a new mapper.
	 *
	 * @param EntityMap $entityMap The entity map to use.
	 * @param Queryable $storage   The storage to map to.
	 */
	public function __construct(EntityMap $entityMap, Queryable $storage)
	{
		$this->entityMap = $entityMap;
		$this->storage = $storage;
	}

	/**
	 * Find a single entity with the given ID.
	 *
	 * @param mixed $id The ID of the entity to find.
	 * @return Mappable
	 */
	public function find($id): ?Mappable
	{
		$entities = $this->query()
			->where($this->entityMap->getStorageKey(), $id)
			->cheers();

		if (!count($entites)) {
			return null;
		}

		return $entities[0];
	}

	/**
	 * Find all entities.
	 *
	 * @return Mappable[]
	 */
	public function all(): array
	{
		return $this->query()->cheers();
	}

	/**
	 * Open a query to the storage that the entity is mapped to.
	 *
	 * @return Query\Builder
	 */
	public function query(): Query\Builder
	{
		$query = $this->storage->query($this->entityMap->getResource());

		$query->callback(function (Result $result) {
			$entities = [];

			foreach ($result as $row) {
				// TODO: Implement attribute to field mapping
				$entities[] = $this->newInstance($row);
			}

			return $entities;
		});

		return $query;
	}

	/**
	 * Create a new instance of the mapper's entity.
	 *
	 * @param array $data The attribute data to set on the created entity instance.
	 * @return Mappable
	 */
	public function newInstance(array $data = []): Mappable
	{
		$reflection = new ReflectionClass($this->entityMap->getClass());

		/**
		 * @var Mappable $instance
		 */
		$instance = $reflection->newInstance();
		$instance->setAttributeData($data);

		return $instance;
	}
}
