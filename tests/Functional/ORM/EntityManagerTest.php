<?php

namespace Darya\Tests\Functional\ORM;

use Darya\ORM\EntityGraph;
use Darya\ORM\EntityManager;
use Darya\ORM\EntityMapFactory;
use Darya\ORM\Query;
use Darya\ORM\Relationship\Has;
use Darya\Storage;
use Darya\Tests\Unit\ORM\Fixtures\User;
use PHPUnit\Framework\TestCase;

class EntityManagerTest extends TestCase
{

	/**
	 * @var array[]
	 */
	protected $storageData;

	/**
	 * @var Storage\InMemory
	 */
	protected $storage;

	/**
	 * @var EntityMapFactory
	 */
	protected $factory;

	/**
	 * @var EntityGraph
	 */
	protected $graph;

	/**
	 * Prepare in-memory storage and an entity graph for each test.
	 */
	public function setUp()
	{
		$this->storageData = [
			'users' => [
				[
					'id'         => 1,
					'firstname'  => 'Qui-Gon',
					'surname'    => 'Jinn',
					'padawan_id' => 2
				],
				[
					'id'        => 2,
					'firstname' => 'Obi-Wan',
					'surname'   => 'Kenobi',
					'master_id' => 1
				],
				[
					'id'        => 3,
					'firstname' => 'Anakin',
					'surname'   => 'Skywalker',
					'master_id' => 2
				],
				[
					'id'        => 4,
					'firstname' => 'Ahsoka',
					'surname'   => 'Tano',
					'master_id' => 3
				]
			]
		];

		$this->storage = new Storage\InMemory($this->storageData);

		$this->factory = new EntityMapFactory();

		$userMap = $this->factory->createForClass(
			User::class,
			[
				'id'         => 'id',
				'firstname'  => 'firstname',
				'surname'    => 'surname',
				'padawan_id' => 'padawan_id',
				'master_id'  => 'master_id'
			],
			'users'
		);

		$this->graph = new EntityGraph(
			[
				$userMap
			],
			[
				// TODO: BelongsTo('master', $userMap, $userMap, 'padawan_id');
				new Has('master', $userMap, $userMap, 'padawan_id'),
				new Has('padawan', $userMap, $userMap, 'master_id')
				//new BelongsTo('padawan', $userMap, $userMap, 'padawan_id')
			]
		);
	}

	public function newEntityManager(): EntityManager
	{
		return new EntityManager($this->graph, ['in-memory' => $this->storage]);
	}

	/**
	 * Test that the manager accepts a simple storage query.
	 */
	public function testStorageQuery()
	{
		$orm = $this->newEntityManager();

		$query = (new Storage\Query(User::class))->where('id', 2);

		$users = $orm->run($query);

		$this->assertCount(1, $users);
		$user = $users[0];
		$this->assertInstanceOf(User::class, $user);
		$this->assertEquals($user->id, 2);
		$this->assertEquals($user->firstname, 'Obi-Wan');
		$this->assertEquals($user->surname, 'Kenobi');
		$this->assertEquals($user->master_id, 1);
	}

	/**
	 * Test that simple usage of the ORM query builder works as expected.
	 */
	public function testSimpleQueryBuilderRun()
	{
		$orm = $this->newEntityManager();

		$users = $orm->query(User::class)->where('id', 2)->run();

		$this->assertCount(1, $users);
		$user = $users[0];
		$this->assertInstanceOf(User::class, $user);
		$this->assertEquals(2, $user->id);
		$this->assertEquals('Obi-Wan', $user->firstname);
		$this->assertEquals('Kenobi', $user->surname);
		$this->assertEquals(1, $user->master_id, 1);
	}

	/**
	 * Test finding a single entity.
	 */
	public function testFind()
	{
		$orm = $this->newEntityManager();

		$user = $orm->find(User::class, 1);

		$this->assertInstanceOf(User::class, $user);
		$this->assertEquals(1, $user->id);
		$this->assertEquals('Qui-Gon', $user->firstname);
	}

	public function testQueryWith()
	{
		$orm = $this->newEntityManager();

		$users = $orm->query(User::class)->with('padawan')->run();

		$this->assertCount(count($this->storageData['users']), $users);

		// TODO: Unit test the matches
		//var_dump($users);
	}
}
