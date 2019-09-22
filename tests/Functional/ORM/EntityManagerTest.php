<?php

namespace Darya\Tests\Functional\ORM;

use Darya\ORM\EntityGraph;
use Darya\ORM\EntityManager;
use Darya\ORM\EntityMap;
use Darya\ORM\EntityMapFactory;
use Darya\ORM\Mapper;
use Darya\ORM\Query;
use Darya\ORM\EntityMap\Strategy\PropertyStrategy;
use Darya\Storage\InMemory;
use Darya\Tests\Unit\ORM\Fixtures\User;
use PHPUnit\Framework\TestCase;

class EntityManagerTest extends TestCase
{
	/**
	 * @var InMemory
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
		$this->storage = new InMemory([
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
				]
			]
		]);

		$this->factory = new EntityMapFactory();

		$this->graph = new EntityGraph([
			$this->factory->createForClass(
				User::class,
				[
					'id'         => 'id',
					'firstname'  => 'firstname',
					'surname'    => 'surname',
					'padawan_id' => 'padawan_id',
					'master_id'  => 'master_id'
				],
				'users'
			)
		]);
	}

	public function newEntityManager(): EntityManager
	{
		return new EntityManager($this->graph, [$this->storage]);
	}

	public function testSimpleQueryRun()
	{
		$orm = $this->newEntityManager();

		$query = (new Query(
			$orm->mapper(User::class)
		))->where('id', 2);

		$users = $orm->run($query);

		$this->assertCount(1, $users);
		$user = $users[0];
		$this->assertInstanceOf(User::class, $user);
		$this->assertEquals($user->id, 2);
		$this->assertEquals($user->firstname, 'Obi-Wan');
		$this->assertEquals($user->surname, 'Kenobi');
		$this->assertEquals($user->master_id, 1);
	}

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

	public function testFind()
	{
		$orm = $this->newEntityManager();

		$user = $orm->find(User::class, 1);

		$this->assertInstanceOf(User::class, $user);
		$this->assertEquals(1, $user->id);
		$this->assertEquals('Qui-Gon', $user->firstname);
	}
}
