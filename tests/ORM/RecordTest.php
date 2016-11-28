<?php
namespace Darya\Tests\ORM;

use PHPUnit_Framework_TestCase;

use Darya\ORM\Record;
use Darya\Storage\InMemory;

use Darya\Tests\ORM\Fixtures\Certificate;
use Darya\Tests\ORM\Fixtures\Post;
use Darya\Tests\ORM\Fixtures\Role;
use Darya\Tests\ORM\Fixtures\User;

/**
 * Tests Darya's active record ORM using in-memory storage.
 * 
 * Please refer to ./data/cms.json for the test data used for this test case.
 * 
 * TODO: Set up integration tests that extend this using different storage.
 */
class RecordTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Data to use for the in-memory storage.
	 * 
	 * @var array
	 */
	protected static $data;
	
	/**
	 * The storage interface used for testing Darya's active record ORM.
	 * 
	 * @var InMemory
	 */
	protected $storage;
	
	public static function setUpBeforeClass() {
		$file = __DIR__  . '/data/cms.json';
		$data = json_decode(file_get_contents($file), true);
		
		static::$data = $data;
	}
	
	protected function storageClass() {
		return 'Darya\Storage\InMemory';
	}
	
	protected function setUpStorage() {
		$class = $this->storageClass();
		$this->storage = new $class(static::$data);
	}
	
	protected function setUp() {
		$this->setUpStorage();
		
		Record::setSharedStorage($this->storage);
	}
	
	/**
	 * Sets the shared storage to a mock that will cause a test to fail if its
	 * read() method is called.
	 * 
	 * This can be used to test that model relations were eagerly loaded
	 * correctly - the relation objects shouldn't need to query the storage.
	 */
	protected function mockEagerStorage() {
		$mock = $this->getMockBuilder($this->storageClass())
		             ->setMethods(array('read'))
		             ->getMock();
		
		$mock->expects($this->never())->method('read');
		
		Record::setSharedStorage($mock);
	}
	
	public function testTable() {
		$user = new User;
		$this->assertEquals('users', $user->table());
		
		$role = new Role;
		$this->assertEquals('roles', $role->table());
		
		$post = new Post;
		$this->assertEquals('posts', $post->table());
		
		$certificate = new Certificate;
		$this->assertEquals('certs', $certificate->table());
	}
	
	public function testFind() {
		$user = User::find(2);
		
		$this->assertEquals('Bethany', $user->firstname);
	}
	
	public function testAll() {
		$users = User::all();
		
		$this->assertEquals(3, count($users));
		
		$this->assertEquals('Chris', $users[0]->firstname);
		$this->assertEquals('Bethany', $users[1]->firstname);
		
		// Test filtering
		$users = User::all(['firstname !=' => 'john']);
		
		$this->assertEquals(2, count($users));
		
		// Test sorting
		$users = User::all(null, 'firstname');
		
		$this->assertEquals('Bethany', $users[0]->firstname);
		$this->assertEquals('Chris', $users[1]->firstname);
		
		// Test limiting
		$users = User::all(null, null, 1);
		
		$this->assertEquals(1, count($users));
		$this->assertEquals('Chris', $users[0]->firstname);
		
		// Test limiting with offset
		$users = User::all(null, null, 1, 1);
		
		$this->assertEquals(1, count($users));
		$this->assertEquals('Bethany', $users[0]->firstname);
		
		// Test offset without limit
		$roles = Role::all(null, null, null, 1);
		$this->assertEquals(3, count($roles));
		$this->assertEquals('Moderator', $roles[0]->name);
	}
	
	public function testListing() {
		$firstnames = User::listing('firstname');
		
		$this->assertEquals(array(1 => 'Chris', 2 => 'Bethany', 3 => 'John'), $firstnames);
	}
	
	public function testSave() {
		$data = array(
			'id'        => 4,
			'firstname' => 'New',
			'surname'   => 'User'
		);
		
		$user = new User($data);
		
		// Also test changed attributes
		$user->firstname = 'Some';
		$data['firstname'] = 'Some';
		
		$user->save();
		
		// Test loading back from storage
		$rows = $this->storage->read('users', array('id' => 4));
		
		$this->assertNotEmpty($rows);
		$this->assertEquals($data, $rows[0]);
		
		// And loading back through the record
		$user = User::find(4);
		
		$this->assertEquals(4, $user->id());
		$this->assertEquals('Some', $user->firstname);
		$this->assertEquals('User', $user->surname);
	}
	
	public function testDistinct() {
		$firstnames = User::distinct('firstname');
		
		$this->assertEquals(array('Chris', 'Bethany', 'John'), $firstnames);
	}
	
	public function testHasMethod() {
		$user = User::find(1);
		
		$this->assertTrue($user->has('roles'));
		
		$user = User::find(2);
		
		$this->assertFalse($user->has('manager'));
		
		$user = User::find(3);
		
		$this->assertTrue($user->has('manager'));
	}
	
	public function testHas() {
		$user = User::find(1);
		
		$this->assertEquals('John', $user->padawan->firstname);
	}
	
	public function testHasEager() {
		$users = User::eager('padawan');
		
		$this->mockEagerStorage();
		
		$this->assertEquals(3, count($users));
		$this->assertEquals('John', $users[0]->padawan->firstname);
	}
	
	public function testHasAssociation() {
		$user = User::find(1);
		
		$user->padawan = User::find(2);
		
		$this->assertEquals('Bethany', $user->padawan->firstname);
		
		$padawan = User::find(2);
		$this->assertEquals(1, $padawan->master_id);
		
		$old = User::find(3);
		$this->assertEquals(0, $old->master_id);
		
		// Also test on the relation object
		$user->padawan()->associate(User::find(3));
		
		$this->assertEquals('John', $user->padawan->firstname);

		$padawan = User::find(2);
		$this->assertEquals(0, $padawan->master_id);
		
		$old = User::find(3);
		$this->assertEquals(1, $old->master_id);
	}
	
	public function testHasDissociation() {
		$user = User::find(1);
		
		$this->assertNotNull($user->padawan);
		
		$user->padawan()->dissociate();
		
		$this->assertNull($user->padawan);
		
		$padawan = User::find(3);
		
		$this->assertEquals(0, $padawan->master_id);
	}
	
	public function testHasAttachment()
	{
		$user = User::find(1);
		
		$padawan = new User([
			'firstname' => 'Obi-Wan',
			'surname'   => 'Kenobi'
		]);
		
		$user->padawan()->attach($padawan);
		
		$this->assertEquals('Obi-Wan', $user->padawan->firstname);
		
		// TODO: Test direct set ($user->padawan = $padawan)
	}
	
	public function testHasDetachment()
	{
		$user = User::find(1);
		
		$user->padawan()->detach($user->padawan);
		
		$this->assertEquals(null, $user->padawan);
		
		// TODO: Test unset()
	}
	
	public function testHasSave()
	{
		$user = User::find(1);
		
		$padawan = new User([
			'firstname' => 'Obi-Wan',
			'surname'   => 'Kenobi'
		]);
		
		$user->padawan()->attach($padawan);
		
		$user->save();
		
		// TODO: Assert that Obi-Wan is in storage
	}
	
	public function testHasDotNotation() {
		$user = User::find(1);
		
		$this->assertEquals('John', $user->get('padawan.firstname'));
	}
	
	public function testBelongsTo() {
		$post = Post::find(3);
		
		$author = $post->author;
		
		$this->assertEquals('Bethany', $author->firstname);
		
		$user = User::find(3);
		
		$manager = $user->manager;
		
		$this->assertEquals('Bethany', $manager->firstname);
	}
	
	public function testBelongsToEager() {
		$posts = Post::eager('author');
		
		$this->mockEagerStorage();
		
		$this->assertEquals(3, count($posts));
		$this->assertEquals('Chris', $posts[0]->author->firstname);
		$this->assertEquals('Chris', $posts[1]->author->firstname);
		$this->assertEquals('Bethany', $posts[2]->author->firstname);
	}
	
	public function testBelongsToAssociation() {
		$user = User::find(1);
		
		$user->manager()->associate(User::find(2));
		
		$this->assertEquals(1, $user->manager()->count());
		
		$manager = new User(array(
			'id' => 4,
			'firstname' => 'Test',
			'surname' => 'Manager'
		));
		
		$user->manager()->associate($manager);
		
		$this->assertEquals(1, $user->manager()->count());
		$this->assertEquals(4, count(User::all()));
		
		$manager = User::find(4);
		$this->assertNotEmpty($manager);
		$this->assertEquals('Test', $manager->firstname);
		$this->assertEquals('Manager', $manager->surname);
	}
	
	public function testBelongsToDissociation() {
		$user = User::find(3);
		
		$user->manager()->dissociate();
		
		$this->assertNull($user->manager);
		$this->assertSame(0, $user->manager_id);
		
		$rows = $this->storage->read('users', array('id' => 3));
		$this->assertEquals(0, $rows[0]['manager_id']);
	}
	
	public function testBelongsToDotNotation() {
		$user = User::find(3);
		
		$this->assertEquals('Bethany', $user->get('manager.firstname'));
		$this->assertEquals('Chris', $user->get('master.firstname'));
	}
	
	public function testHasMany() {
		$user = User::find(1);
		
		$posts = $user->posts;
		
		$this->assertEquals(2, count($posts));
		
		$this->assertEquals("First post", $posts[0]->title);
	}
	
	public function testHasManyEager() {
		$users = User::eager('posts');
		
		$this->mockEagerStorage();
		
		$this->assertEquals(3, count($users));
		$this->assertEquals(2, count($users[0]->posts));
		$this->assertEquals(1, count($users[1]->posts));
		$this->assertEquals(0, count($users[2]->posts));
	}
	
	public function testHasManyAssociation() {
		$user = User::find(1);
		
		$post = new Post(array(
			'id'      => 4,
			'title'   => 'Swagger',
			'content' => 'Dis one got swagger'
		));
		
		$user->posts()->associate($post);
		
		$this->assertEquals(3, $user->posts()->count());
		
		$this->assertEquals(4, count(Post::all()));
		
		$post = Post::find(4);
		$this->assertNotEmpty($post);
		$this->assertEquals('Swagger', $post->title);
		$this->assertEquals('Dis one got swagger', $post->content);
	}
	
	public function testHasManyDissociation() {
		$user = User::find(1);
		
		$this->assertEquals(2, $user->posts()->count());
		
		$post = $user->posts[0];
		
		$this->assertEquals(1, $post->id());
		
		$dissociated = $user->posts()->dissociate($post);
		
		$this->assertEquals(1, $dissociated);
		
		$this->assertEquals(1, $user->posts()->count());
		
		$this->assertEquals(2, $user->posts[0]->id());
	}
	
	public function testHasManyPurge() {
		$user = User::find(1);
		
		$this->assertEquals(2, $user->posts()->count());
		
		$dissociated = $user->posts()->purge();
		
		$this->assertEquals(2, $dissociated);
		
		$this->assertEquals(0, $user->posts()->count());
	}
	
	public function testHasManyConstraint() {
		$user = User::find(1);
		
		$user->posts()->constrain(array(
			'title !=' => 'First post'
		));
		
		$this->assertEquals(1, $user->posts()->count());
		$this->assertEquals('Second post', $user->posts[0]->title);
	}
	
	public function testBelongsToMany() {
		$user = User::find(1);
		
		$roles = $user->roles;
		
		$this->assertEquals(2, count($roles));
		
		$role = Role::find(2);
		
		$users = $role->users;
		
		$this->assertEquals(1, count($users));
		$this->assertEquals('Bethany', $users[0]->firstname);
	}
	
	public function testBelongsToManyEager() {
		$users = User::eager('roles');
		
		$this->mockEagerStorage();
		
		$this->assertEquals(3, count($users));
		
		$this->assertEquals('Administrator', $users[0]->roles[0]->name);
		$this->assertEquals('b0ss', $users[0]->roles[1]->name);
		$this->assertEquals(2, count($users[0]->roles));
		
		$this->assertEquals('Moderator', $users[1]->roles[0]->name);
		$this->assertEquals(1, count($users[1]->roles));
		
		$this->assertEquals('User', $users[2]->roles[0]->name);
		$this->assertEquals(1, count($users[2]->roles));
	}
	
	public function testBelongsToManyAssociation() {
		$user = User::find(1);
		
		$user->roles()->associate(Role::find(1));
		$this->assertEquals(3, $user->roles()->count());
		
		$expected = array(1, 3, 4);
		$actual = $this->storage->distinct('user_roles', 'role_id', array('user_id' => 1));
		
		$this->assertEmpty(array_diff($expected, $actual));
		
		// Test associating a new role
		$role = new Role(array(
			'id' => 5,
			'name' => 'New role'
		));
		
		$user->roles()->associate($role);
		
		// Test that the role was attached correctly
		$this->assertEquals(4, $user->roles()->count());
		
		$expected = array(1, 3, 4, 5);
		$actual = $this->storage->distinct('user_roles', 'role_id', array('user_id' => 1));
		
		$this->assertEmpty(array_diff($expected, $actual));
		
		// Test that the role was saved correctly
		$role = Role::find(5);
		$this->assertEquals('New role', $role->name);
	}
	
	public function testBelongsToManyDissociation() {
		$user = User::find(1);
		
		$user->roles()->dissociate(Role::find(3));
		$this->assertEquals(1, $user->roles()->count());
		$this->assertEquals(4, $user->roles[0]->id());
		
		$expected = array(4);
		$actual = $this->storage->distinct('user_roles', 'role_id', array('user_id' => 1));
		
		$this->assertEmpty(array_diff($expected, $actual));
	}
	
	public function testBelongsToManyPurge() {
		$user = User::find(1);
		
		$user->roles()->purge();
		$this->assertEmpty($user->roles);
		
		$this->assertEmpty($this->storage->distinct('user_roles', 'role_id', array('user_id' => 1)));
	}
	
	public function testBelongsToManyConstraint() {
		$user = User::find(1);
		
		$user->roles()->constrain(array(
			'name not like' => '%b0s%'
		));
		
		$this->assertEquals(1, $user->roles()->count());
		$this->assertEquals('Administrator', $user->roles[0]->name);
	}
	
	public function testBelongsToManyDissociationWithConstraint() {
		$user = User::find(1);
		
		$roles = $user->roles;
		
		$user->roles()->constrain(array(
			'name' => 'b0ss'
		));
		
		$user->roles()->dissociate($roles);
		
		$expected = array(3);
		$actual = $this->storage->distinct('user_roles', 'role_id', array('user_id' => 1));
		
		$this->assertEmpty(array_diff($expected, $actual));
	}
	
	public function testBelongsToManyPurgeWithConstraint() {
		$user = User::find(1);
		
		$user->roles()->constrain(array(
			'name' => 'b0ss'
		));
		
		$user->roles()->purge();
		
		$expected = array(3);
		$actual = $this->storage->distinct('user_roles', 'role_id', array('user_id' => 1));
		
		$this->assertEmpty(array_diff($expected, $actual));
	}
	
	public function testBelongsToManyAssociationConstraint() {
		$user = User::find(1);
		
		$user->roles()->constrainAssociation(array(
			'sort <' => 2
		));
		
		$this->assertEquals(1, $user->roles()->count());
		$this->assertEquals('Administrator', $user->roles[0]->name);
	}
	
	public function testRelationAttributes() {
		$user = new User;
		
		$this->assertEquals(array('padawan', 'manager', 'master', 'posts', 'roles'), $user->relationAttributes());
	}
	
	public function testRelations() {
		$user = new User;
		
		// Test single relation access and property
		$relation = $user->relation('padawan');
		$this->assertInstanceOf('Darya\ORM\Relation', $relation);
		$this->assertEquals('master_id', $relation->foreignKey);
		
		// Test all relations
		$relations = $user->relations();
		
		foreach ($relations as $relation) {
			$this->assertInstanceOf('Darya\ORM\Relation', $relation);
		}
	}
	
	public function testDefaultSearchAttributes() {
		$users = User::search('chris');
		
		$this->assertEquals(1, count($users));
		$this->assertEquals('Andrew', $users[0]->surname);
		
		$users = User::search('KING');
		
		$this->assertEquals(1, count($users));
		$this->assertEquals('Bethany', $users[0]->firstname);
	}
	
}

