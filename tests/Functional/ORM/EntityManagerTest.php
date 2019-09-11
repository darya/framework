<?php

namespace Darya\Tests\Functional\ORM;

use Darya\ORM\EntityGraph;
use Darya\ORM\EntityManager;
use Darya\ORM\EntityMap;
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

		$this->graph = new EntityGraph([
			new EntityMap(
				User::class,
				'users',
				[
					'id'         => 'id',
					'firstname'  => 'firstname',
					'surname'    => 'surname',
					'padawan_id' => 'padawan_id',
					'master_id'  => 'master_id'
				],
				new PropertyStrategy()
			)
		]);
	}

	public function newOrmManager(): EntityManager
	{
		return new EntityManager($this->graph, [$this->storage]);
	}

	public function testSimpleQueryRun()
	{
		$orm = $this->newOrmManager();

		$query = (new Query(
			new \Darya\Storage\Query('users'),
			User::class
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
		$orm = $this->newOrmManager();

		$users = $orm->query(User::class)->where('id', 2)->run();

		$this->assertCount(1, $users);
		$user = $users[0];
		$this->assertInstanceOf(User::class, $user);
		$this->assertEquals($user->id, 2);
		$this->assertEquals($user->firstname, 'Obi-Wan');
		$this->assertEquals($user->surname, 'Kenobi');
		$this->assertEquals($user->master_id, 1);
	}
}
