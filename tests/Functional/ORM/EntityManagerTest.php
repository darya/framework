<?php

namespace Darya\Tests\Functional\ORM;

use Darya\ORM\EntityGraph;
use Darya\ORM\EntityManager;
use Darya\ORM\EntityMapFactory;
use Darya\ORM\Relationship\BelongsTo;
use Darya\ORM\Relationship\BelongsToMany;
use Darya\ORM\Relationship\Has;
use Darya\ORM\Relationship\HasMany;
use Darya\Storage;
use Darya\Tests\Unit\ORM\Fixtures\PostModel as Post;
use Darya\Tests\Unit\ORM\Fixtures\RoleModel as Role;
use Darya\Tests\Unit\ORM\Fixtures\UserModel as User;
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
			],
			'roles' => [
				[
					'id' => 1,
					'name' => 'Jedi Master'
				],
				[
					'id' => 2,
					'name' => 'Jedi Knight'
				],
				[
					'id' => 3,
					'name' => 'Jedi Padawan'
				],
				[
					'id' => 4,
					'name' => 'Jedi Council'
				]
			],
			'users_roles' => [
				[
					'user_id' => 1,
					'role_id' => 1
				],
				[
					'user_id' => 2,
					'role_id' => 1
				],
				[
					'user_id' => 2,
					'role_id' => 4,
				],
				[
					'user_id' => 3,
					'role_id' => 2
				],
				[
					'user_id' => 4,
					'role_id' => 3
				]
			],
			'posts' => [
				[
					'id'        => 1,
					'title'     => 'Post one',
					'author_id' => 1
				],
				[
					'id'        => 2,
					'title'     => 'Post two',
					'author_id' => 1
				],
				[
					'id'        => 3,
					'title'     => 'Post three',
					'author_id' => 2
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

		$roleMap = $this->factory->createForClass(
			Role::class,
			[
				'id'   => 'id',
				'name' => 'name'
			],
			'roles'
		);

		$postMap = $this->factory->createForClass(
			Post::class,
			[
				'id'        => 'id',
				'title'     => 'title',
				'author_id' => 'author_id'
			],
			'posts'
		);

		$this->graph = new EntityGraph(
			[
				$userMap,
				$postMap,
				$roleMap
			],
			[
				new BelongsTo('master', $userMap, $userMap, 'master_id'),
				new Has('padawan', $userMap, $userMap, 'master_id'),
				new HasMany('posts', $userMap, $postMap, 'author_id'),
				new BelongsToMany('roles', $userMap, $roleMap, 'role_id', 'user_id', 'users_roles')
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
		$this->assertEquals(2, $user->id);
		$this->assertEquals('Obi-Wan', $user->firstname);
		$this->assertEquals('Kenobi', $user->surname);
		$this->assertEquals(1, $user->master_id);
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

		$users = $orm->query(User::class)->with(['master', 'padawan', 'posts', 'roles'])->run();

		$storageUsers = $this->storageData['users'];
		$this->assertCount(count($storageUsers), $users);

		$actualMasterCount   = 0;
		$expectedMasterCount = count($orm->query(User::class)->where('master_id >', 0)->run());

		$actualPadawanCount   = 0;
		$expectedPadawanCount = count($orm->query(User::class)->where('master_id >', 0)->run());

		$actualPostsCount   = 0;
		$expectedPostsCount = count($orm->query(Post::class)->where('author_id >', 0)->run());

		$actualRolesCount = 0;
		$expectedRolesCount = count($this->storageData['users_roles']);

		// Assert that the IDs of related entities match properly, and increment the counts of each
		foreach ($users as $user) {
			if ($user->master) {
				$this->assertSame($user->master_id, $user->master->id);
				$actualMasterCount++;
			}

			if ($user->padawan) {
				$this->assertSame($user->id, $user->padawan->master_id);
				$actualPadawanCount++;
			}

			if ($user->posts) {
				foreach ($user->posts as $post) {
					$this->assertSame($user->id, $post->author_id);
				}

				$actualPostsCount += count($user->posts);
			}

			if ($user->roles) {
				$actualRolesCount += count($user->roles);
			}
		}

		$this->assertSame($expectedMasterCount, $actualMasterCount, 'Eagerly loaded masters count did not match');
		$this->assertSame($expectedPadawanCount, $actualPadawanCount, 'Eagerly loaded padawans count did not match');
		$this->assertSame($expectedPostsCount, $actualPostsCount, 'Eagerly loaded posts count did not match');
		$this->assertSame($expectedRolesCount, $actualRolesCount, 'Eagerly loaded roles count did not match');
	}
}
