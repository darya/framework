<?php
namespace Darya\Tests\Unit\ORM;

use PHPUnit_Framework_TestCase;

use Darya\ORM\Record;
use Darya\Storage\InMemory;

use Darya\Tests\Unit\ORM\Fixtures\Certificate;
use Darya\Tests\Unit\ORM\Fixtures\Post;
use Darya\Tests\Unit\ORM\Fixtures\Role;
use Darya\Tests\Unit\ORM\Fixtures\User;

/**
 * Tests Darya's active record ORM using in-memory storage.
 * 
 * Please refer to ./data/cms.json for the test data used for this test case.
 * 
 * TODO: This is testing more than just Records. Extract to per-relation tests.
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

	/**
	 * Assert that two arrays contain the same values.
	 *
	 * @param array $expected
	 * @param array $actual
	 */
	protected function assertEqualValues(array $expected, array $actual)
	{
		sort($expected);
		sort($actual);
		$this->assertEquals($expected, $actual);
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
		
		$this->assertEquals(array(), $user->changed());
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
		
		// Also test changing an attribute
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
		
		// Test saving with no changed attributes, ensure no storage call
		$mock = $this->getMockBuilder($this->storageClass())
			->setMethods(array('create', 'update'))
			->getMock();
		
		$mock->expects($this->never())->method('create');
		$mock->expects($this->never())->method('update');
		
		$user->storage($mock);
		
		$user->save();
		
		$user->storage($this->storage);
		
		// TODO: Test that only changed attributes are saved
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
		
		$user->padawan()->associate(User::find(2));
		
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
		// Test attach method
		$user = User::find(1);
		
		$padawan = new User([
			'firstname' => 'Obi-Wan',
			'surname'   => 'Kenobi'
		]);
		
		$user->padawan()->attach($padawan);
		
		$this->assertEquals('Obi-Wan', $user->padawan->firstname);
		
		// Test dynamic property
		$user = User::find(2);
		
		$user->padawan = User::find(1);
		
		$this->assertEquals('Chris', $user->padawan->firstname);
	}
	
	public function testHasDetachment()
	{
		// Test detaching an existing model
		$user = User::find(1);
		
		$user->padawan;
		
		$user->padawan()->detach();
		
		$this->assertNull($user->padawan);
		
		// Test detaching a passed existing model
		$user = User::find(1);
		
		$padawan = $user->padawan;
		
		$this->assertNotNull($padawan);
		
		$user->padawan()->detach($padawan);
		
		$this->assertNull($user->padawan);
		
		// Test unset()
		$user = User::find(1);
		
		$padawan = $user->padawan;
		
		$this->assertNotNull($padawan);
		
		unset($user->padawan);
		
		$this->assertNull($user->padawan);
		
		// Test nulling
		$user = User::find(1);
		
		$padawan = $user->padawan;
		
		$this->assertNotNull($padawan);
		
		$user->padawan = null;
		
		$this->assertNull($user->padawan);
	}
	
	public function testHasSave()
	{
		// Test attachment save
		$user = User::find(1);
		
		$padawan = new User([
			'id'        => 4,
			'firstname' => 'Obi-Wan',
			'surname'   => 'Kenobi'
		]);
		
		$user->padawan()->attach($padawan);
		
		$this->assertEquals('John', User::find(1)->padawan->firstname);
		
		$user->save();
		
		$this->assertEquals('Obi-Wan', User::find(1)->padawan->firstname);
		$this->assertEquals('Obi-Wan', User::find(4)->firstname);
		
		// TODO: Test detachment save
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
		// Test instant dissociation
		$user = User::find(3);
		
		$user->manager()->dissociate();
		
		$this->assertNull($user->manager);
		$this->assertSame(0, $user->manager_id);
		
		$rows = $this->storage->read('users', array('id' => 3));
		$this->assertEquals(0, $rows[0]['manager_id']);

		// Test associating and dissociating
		$user = User::find(3);
		$user->manager()->associate(User::find(1));
		$user->manager()->save();

		$user = User::find(3);

		$user->manager()->dissociate();

		$this->assertFalse($user->manager()->loaded());
	}
	
	public function testBelongsToAttachment()
	{
		// Test attach()
		$user = User::find(3);
		
		$user->master()->attach(User::find(2));
		
		$this->assertEquals('Bethany', $user->master->firstname);
		$this->assertEquals('Chris', User::find(3)->master->firstname);
		
		// Test dynamic property
		$user = User::find(3);
		
		$user->master = User::find(3);
		
		$this->assertEquals('John', $user->master->firstname);
		$this->assertEquals('Chris', User::find(3)->master->firstname);
		
		// Test attaching a new model
		$user = User::find(3);
		
		$master = new User([
			'id'        => 4,
			'firstname' => 'Obi-Wan',
			'surname'   => 'Kenobi'
		]);
		
		$user->master = $master;
		
		$this->assertEquals('Obi-Wan', $user->master->firstname);
	}
	
	public function testBelongsToDetachment()
	{
		// Test detaching an existing model
		$user = User::find(3);
		
		$user->master;
		
		$user->master()->detach();
		
		$this->assertNull($user->master);
		
		// Test detaching a passed existing model
		$user = User::find(3);
		
		$master = $user->master;
		
		$this->assertNotNull($master);
		
		$user->master()->detach($master);
		
		$this->assertNull($user->master);
		
		// Test unset()
		$user = User::find(3);
		
		$master = $user->master;
		
		$this->assertNotNull($master);
		
		unset($user->master);
		
		$this->assertNull($user->master);
		
		// Test nulling
		$user = User::find(3);
		
		$master = $user->master;
		
		$this->assertNotNull($master);
		
		$user->master = null;
		
		$this->assertNull($user->master);
	}
	
	public function testBelongsToSave()
	{
		// Test attachment save
		$user = User::find(3);
		
		$master = new User([
			'id'        => 4,
			'firstname' => 'Obi-Wan',
			'surname'   => 'Kenobi'
		]);
		
		$user->master()->attach($master);
		
		$this->assertEquals('Chris', User::find(3)->master->firstname);
		
		$user->save();
		
		$this->assertEquals('Obi-Wan', User::find(3)->master->firstname);
		$this->assertEquals('Obi-Wan', User::find(4)->firstname);
		
		// Test detachment save
		$user = User::find(3);
		
		$user->master;
		$user->master()->detach();
		
		$this->assertNull($user->master);
		$this->assertNotNull(User::find(3)->master);
		
		$user->save();
		
		$this->assertNull($user->master);
		$this->assertNull(User::find(3)->master);
		
		// Test retrieving a new attachment after dissociation
		$user = User::find(3);
		
		$user->master = User::find(4);
		
		$this->assertEquals('Obi-Wan', $user->master->firstname);
		
		// Test setting the foreign key and saving with a different model attached
		$user = User::find(3);
		
		$user->master_id = 1;
		
		$user->master = User::find(4);
		
		$user->save();
		
		$this->assertEquals(4, $user->master->id);
		$this->assertEquals(4, $user->master_id);

		// Test dissociating the model and updating the foreign key
		$user = User::find(3);

		$this->assertEquals(4, $user->master->id);

		$user->master()->dissociate();
		$user->master_id = 1;

		$user->save();

		$this->assertNotEmpty($user->master);
		$this->assertEquals(1, $user->master->id);

		// Test detaching the model and updating the foreign key
		$user = User::find(3);
		$user->master = User::find(4);
		$user->save();
		
		$this->assertEquals(4, $user->master->id);
		
		$user->master = null;
		$user->master_id = 1;
		
		$user->save();
		
		// TODO: Fix this. Or change what to expect?
		//$this->assertNotEmpty($user->master);
		//$this->assertEquals(1, $user->master->id);
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
		// Test associating one
		$user = User::find(1);
		
		$post = new Post(array(
			'id'      => 4,
			'title'   => 'Swagger',
			'content' => 'Dis one got swagger'
		));
		
		$associated = $user->posts()->associate($post);
		
		$this->assertEquals(1, $associated);
		
		$this->assertEquals(3, $user->posts()->count());
		
		$this->assertEquals(4, count(Post::all()));
		
		$post = Post::find(4);
		$this->assertNotEmpty($post);
		$this->assertEquals('Swagger', $post->title);
		$this->assertEquals('Dis one got swagger', $post->content);
		
		// Test associating many
		$posts = array(
			new Post(array(
				'id'      => 5,
				'title'   => 'Swagger',
				'content' => 'Dis one also got swagger'
			)),
			new Post(array(
				'id'      => 6,
				'title'   => 'Swagger',
				'content' => 'Dis one definitely got swagger'
			))
		);
		
		$associated = $user->posts()->associate($posts);
		
		$this->assertEquals(2, $associated);
		$this->assertEquals(5, $user->posts()->count());
		$this->assertEquals(6, count(Post::all()));
		
		$this->assertEquals('Dis one also got swagger', Post::find(5)->content);
		$this->assertEquals('Dis one definitely got swagger', Post::find(6)->content);
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
	
	public function testHasManyAttachment()
	{
		$user = User::find(1);
		
		$user->posts()->load();
		
		$this->assertEquals(2, $user->posts()->count());
		
		$post = new Post(array(
			'id'      => 4,
			'title'   => 'CLICKBAIT!!1',
			'content' => 'omg lol 5 things you will never believe are clickbait'
		));
		
		$user->posts()->attach($post);
		
		$this->assertEquals(3, $user->posts()->count());
		$this->assertEquals(2, User::find(1)->posts()->count());
		
		$posts = array(
			new Post(array(
				'id'      => 5,
				'title'   => 'Test 5',
				'content' => 'Test 5'
			)),
			new Post(array(
				'id'      => 6,
				'title'   => 'Rey is totally the granddaughter of Obi-Wan',
				'content' => 'Search your feelings. YOU KNOW IT TO BE TRUE.'
			))
		);
		
		$user->posts()->attach($posts);
		
		$this->assertEquals(5, $user->posts()->count());
		$this->assertEquals(2, User::find(1)->posts()->count());
		
		// Test dynamic property
		$user->posts = array(
			new Post(array(
				'id'      => 7,
				'title'   => 'Test 7',
				'content' => 'Test 7'
			)),
			new Post(array(
				'id'      => 8,
				'title'   => 'Test 8',
				'content' => 'Test 8'
			)),
			new Post(array(
				'id'      => 9,
				'title'   => 'Test 9',
				'content' => 'Test 9'
			))
		);

		// It should replace any existing attached models in memory
		$this->assertEquals(3, $user->posts()->count());
		$this->assertEquals(2, User::find(1)->posts()->count());

		$user->save();

		// When saved, only these new attachments should be associated
		$this->assertEquals(3, $user->posts()->count());
		$this->assertEquals(3, User::find(1)->posts()->count());

		$expected = array(7, 8, 9);
		$actual = $this->storage->distinct('posts', 'id', array('author_id' => 1));
		$this->assertEqualValues($expected, $actual);
	}
	
	public function testHasManyDetachment()
	{
		// Test detaching existing models
		$user = User::find(1);
		
		$posts = $user->posts;
		
		$this->assertNotEmpty($posts);
		
		$user->posts()->detach();
		
		$this->assertEquals(array(), $user->posts);
		
		// Test detaching a passed existing model
		$user = User::find(1);
		
		$posts = $user->posts;
		
		$this->assertNotEmpty($posts);
		
		$user->posts()->detach($posts);
		
		$this->assertEquals(array(), $user->posts);
		
		// Test unset()
		$user = User::find(1);
		
		$posts = $user->posts;
		
		$this->assertNotEmpty($posts);
		
		unset($user->posts);
		
		$this->assertEquals(array(), $user->posts);
		
		// Test nulling
		$user = User::find(1);
		
		$posts = $user->posts;
		
		$this->assertNotEmpty($posts);
		
		$user->posts = null;
		
		$this->assertEquals(array(), $user->posts);
	}
	
	public function testHasManySave()
	{
		// Test attachment save
		$user = User::find(1);
		
		$user->posts()->load();
		
		$user->posts()->attach(new Post(array(
			'id'      => 4,
			'title'   => 'Test',
			'content' => 'Test'
		)));
		
		$this->assertEquals(3, $user->posts()->count());
		$this->assertEquals(2, User::find(1)->posts()->count());
		
		$user->save();
		
		$this->assertEquals(3, $user->posts()->count());
		$this->assertEquals(3, User::find(1)->posts()->count());
		$this->assertEquals('Test', Post::find(4)->title);
		
		// Test many attachment save
		$user->posts()->attach(array(
			new Post(array(
				'id'      => 5,
				'title'   => 'Test 5',
				'content' => 'Test 5'
			)),
			new Post(array(
				'id'      => 6,
				'title'   => 'Test 6',
				'content' => 'Test 6'
			))
		));
		
		$this->assertEquals(5, $user->posts()->count());
		$this->assertEquals(3, User::find(1)->posts()->count());
		
		$user->save();
		
		$this->assertEquals(5, $user->posts()->count());
		$this->assertEquals(5, User::find(1)->posts()->count());
		$this->assertEquals('Test 5', Post::find(5)->title);
		$this->assertEquals('Test 6', Post::find(6)->title);
		
		// Test detachment save
		$user->posts()->detach($user->posts[4]); // Post ID 6
		
		$this->assertEquals(4, $user->posts()->count());
		$this->assertEquals(5, User::find(1)->posts()->count());
		
		$user->save();
		
		$this->assertEquals(4, $user->posts()->count());
		$this->assertEquals(4, User::find(1)->posts()->count());
		$this->assertNull(Post::find(6)->author);
		
		$user->posts()->detach(array(
			$user->posts[0], // Post ID 1
			$user->posts[1]  // Post ID 2
		));
		
		$this->assertEquals(2, $user->posts()->count());
		$this->assertEquals(4, User::find(1)->posts()->count());
		
		$user->save();
		
		$this->assertEquals(2, $user->posts()->count());
		$this->assertEquals(2, User::find(1)->posts()->count());
		$this->assertNull(Post::find(1)->author);
		$this->assertNull(Post::find(2)->author);
		
		$user->posts()->detach(); // Detach remaining posts with IDs 4 and 5
		
		$this->assertEquals(0, $user->posts()->count());
		$this->assertEquals(2, User::find(1)->posts()->count());
		
		$user->save();
		
		$this->assertEquals(0, $user->posts()->count());
		$this->assertEquals(0, User::find(1)->posts()->count());
		$this->assertNull(Post::find(4)->author);
		$this->assertNull(Post::find(5)->author);
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
	
	public function testBelongsToMany()
	{
		$user = User::find(1);
		
		$roles = $user->roles;
		
		$this->assertEquals(2, count($roles));
		
		$role = Role::find(2);
		
		$users = $role->users;
		
		$this->assertEquals(1, count($users));
		$this->assertEquals('Bethany', $users[0]->firstname);
	}
	
	public function testBelongsToManyEager()
	{
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
	
	public function testBelongsToManyAssociation()
	{
		// Test associating an existing role
		$user = User::find(1);
		
		$user->roles()->associate(Role::find(1));
		$this->assertEquals(3, $user->roles()->count());
		
		$expected = array(1, 3, 4);
		$actual = $this->storage->distinct('user_roles', 'role_id', array('user_id' => 1));
		$this->assertEqualValues($expected, $actual);
		
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
		$this->assertEqualValues($expected, $actual);
		
		// Test that the role was saved correctly
		$role = Role::find(5);
		$this->assertEquals('New role', $role->name);

		// Test associating many roles
		$user->roles()->associate(array_merge(
			Role::all(),
			array(
				new Role(
					array(
						'id' => 6,
						'name' => 'Swag'
					)
				)
			)
		));

		$this->assertEquals(6, $user->roles()->count());

		$expected = array(1, 2, 3, 4, 5, 6);
		$actual = $this->storage->distinct('user_roles', 'role_id', array('user_id' => 1));
		$this->assertEqualValues($expected, $actual);
	}
	
	public function testBelongsToManyDissociation()
	{
		// Test dissociating a single role
		$user = User::find(1);

		$user->roles()->dissociate(Role::find(3));
		$this->assertEquals(1, $user->roles()->count());
		$this->assertEquals(4, $user->roles[0]->id());

		$expected = array(4);
		$actual = $this->storage->distinct('user_roles', 'role_id', array('user_id' => 1));
		$this->assertEqualValues($expected, $actual);

		// Test dissociating multiple roles
		$user->roles()->associate(Role::all());

		$this->assertEquals(4, $user->roles()->count());

		$expected = array(1, 2, 3, 4);
		$actual = $this->storage->distinct('user_roles', 'role_id', array('user_id' => 1));
		$this->assertEqualValues($expected, $actual);

		$user->roles()->dissociate(array($user->roles[0], $user->roles[1])); // We expect this to dissociate 1 and 4

		$this->assertEquals(2, $user->roles()->count());

		$expected = array(2, 3); // We expect these to remain because they were associated last
		$actual = $this->storage->distinct('user_roles', 'role_id', array('user_id' => 1));
		$this->assertEqualValues($expected, $actual);
	}
	
	public function testBelongsToManyAttachment()
	{
		// Test attaching an existing role
		$user = User::find(1);
		
		$user->roles()->load();
		
		$user->roles()->attach(Role::find(1));
		
		$this->assertEquals(3, $user->roles()->count());
		$this->assertEquals(2, User::find(1)->roles()->count());
		
		$expected = array(3, 4);
		$actual = $this->storage->distinct('user_roles', 'role_id', array('user_id' => 1));
		$this->assertEquals($expected, $actual);
		
		// Test saving the new attachment
		$user->save();
		
		$this->assertEquals(3, $user->roles()->count());
		$this->assertEquals(3, User::find(1)->roles()->count());

		$expected = array(1, 3, 4);
		$actual = $this->storage->distinct('user_roles', 'role_id', array('user_id' => 1));
		$this->assertEqualValues($expected, $actual);
		
		// Test attaching two new roles
		$roles = array(
			new Role(array(
				'id' => 5,
				'name' => 'Test Role 5'
			)),
			new Role(array(
				'id' => 6,
				'name' => 'Test Role 6'
			))
		);
		
		$user->roles()->attach($roles);
		
		$this->assertEquals(5, $user->roles()->count());
		$this->assertEquals(3, User::find(1)->roles()->count());
		
		$expected = array(1, 3, 4);
		$actual = $this->storage->distinct('user_roles', 'role_id', array('user_id' => 1));
		$this->assertEqualValues($expected, $actual);
		
		$user->save();
		
		$this->assertEquals(5, $user->roles()->count());
		$this->assertEquals(5, User::find(1)->roles()->count());
		
		$expected = array(1, 3, 4, 5, 6);
		$actual = $this->storage->distinct('user_roles', 'role_id', array('user_id' => 1));
		$this->assertEqualValues($expected, $actual);
		
		// Test dynamic property
		$user->roles = Role::in([1, 6]);
		
		$this->assertEquals(2, $user->roles()->count());
		$this->assertEquals(5, User::find(1)->roles()->count());
		
		$expected = array(1, 3, 4, 5, 6);
		$actual = $this->storage->distinct('user_roles', 'role_id', array('user_id' => 1));
		$this->assertEqualValues($expected, $actual);
		
		$user->save();
		
		$this->assertEquals(2, $user->roles()->count());
		$this->assertEquals(2, User::find(1)->roles()->count());
		
		$expected = array(1, 6);
		$actual = $this->storage->distinct('user_roles', 'role_id', array('user_id' => 1));
		$this->assertEqualValues($expected, $actual);
	}
	
	public function testBelongsToManyDetachment()
	{
		// Test detaching current roles
		$user = User::find(1);

		$user->roles()->load();

		$user->roles()->detach();

		// Ensure that relations have only changed in memory, not in storage
		$this->assertEquals(0, count($user->roles));
		$this->assertEquals(0, $user->roles()->count());
		$this->assertEquals(2, User::find(1)->roles()->count());

		$expected = array(3, 4);
		$actual = $this->storage->distinct('user_roles', 'role_id', array('user_id' => 1));
		$this->assertEqualValues($expected, $actual);

		// Save the changes to storage
		$user->save();

		// Ensure that the changes have propagated to storage
		$this->assertEquals(0, count($user->roles));
		$this->assertEquals(0, $user->roles()->count());
		$this->assertEquals(0, User::find(1)->roles()->count());

		$expected = array();
		$actual = $this->storage->distinct('user_roles', 'role_id', array('user_id' => 1));
		$this->assertEqualValues($expected, $actual);

		// Associate all roles for testing multiple detachments
		$user->roles()->associate(Role::all());

		$this->assertEquals(4, $user->roles()->count());
		$this->assertEquals(4, User::find(1)->roles()->count());

		// Test detaching an existing role
		$user = User::find(1);

		$user->roles()->load();

		$user->roles()->detach($user->roles[0]);

		// Ensure that relations have only changed in memory, not in storage
		$this->assertEquals(3, count($user->roles));
		$this->assertEquals(3, $user->roles()->count());
		$this->assertEquals(4, User::find(1)->roles()->count());

		$expected = array(1, 2, 3, 4);
		$actual = $this->storage->distinct('user_roles', 'role_id', array('user_id' => 1));
		$this->assertEqualValues($expected, $actual);

		// Save the changes to storage
		$user->save();

		// Ensure that the changes have propagated to storage
		$this->assertEquals(3, count($user->roles));
		$this->assertEquals(3, $user->roles()->count());
		$this->assertEquals(3, User::find(1)->roles()->count());

		$expected = array(2, 3, 4);
		$actual = $this->storage->distinct('user_roles', 'role_id', array('user_id' => 1));
		$this->assertEqualValues($expected, $actual);

		// Test detaching many existing roles
		$user->roles()->detach(array(
			$user->roles[0], $user->roles[1]
		));

		// Ensure that relations have only changed in memory, not in storage
		$this->assertEquals(1, count($user->roles));
		$this->assertEquals(1, $user->roles()->count());
		$this->assertEquals(3, User::find(1)->roles()->count());

		$expected = array(2, 3, 4);
		$actual = $this->storage->distinct('user_roles', 'role_id', array('user_id' => 1));
		$this->assertEqualValues($expected, $actual);

		// Save the changes to storage
		$user->save();

		// Ensure that the changes have propagated to storage
		$this->assertEquals(1, count($user->roles));
		$this->assertEquals(1, $user->roles()->count());
		$this->assertEquals(1, User::find(1)->roles()->count());

		$expected = array(4);
		$actual = $this->storage->distinct('user_roles', 'role_id', array('user_id' => 1));
		$this->assertEqualValues($expected, $actual);

		// Test unset
		unset($user->roles);

		$this->assertEquals(0, count($user->roles));
		$this->assertEquals(0, $user->roles()->count());
		$this->assertEquals(1, User::find(1)->roles()->count());

		$user->save();

		$this->assertEquals(0, User::find(1)->roles()->count());

		// Test nulling
		$user->roles()->associate(Role::all());

		$user->roles = null;

		$this->assertEquals(0, count($user->roles));
		$this->assertEquals(0, $user->roles()->count());
		$this->assertEquals(4, User::find(1)->roles()->count());

		$user->save();

		$this->assertEquals(0, User::find(1)->roles()->count());
	}
	
	public function testBelongsToManyPurge()
	{
		$user = User::find(1);
		
		$user->roles()->purge();
		$this->assertEmpty($user->roles);
		
		$this->assertEmpty($this->storage->distinct('user_roles', 'role_id', array('user_id' => 1)));
	}
	
	public function testBelongsToManyConstraint()
	{
		$user = User::find(1);
		
		$user->roles()->constrain(array(
			'name not like' => '%b0s%'
		));
		
		$this->assertEquals(1, $user->roles()->count());
		$this->assertEquals('Administrator', $user->roles[0]->name);
	}
	
	public function testBelongsToManyDissociationWithConstraint()
	{
		$user = User::find(1);
		
		$roles = $user->roles;
		
		$user->roles()->constrain(array(
			'name' => 'b0ss'
		));
		
		$user->roles()->dissociate($roles);
		
		$expected = array(3);
		$actual = $this->storage->distinct('user_roles', 'role_id', array('user_id' => 1));
		
		$this->assertEqualValues($expected, $actual);
	}
	
	public function testBelongsToManyPurgeWithConstraint()
	{
		$user = User::find(1);
		
		$user->roles()->constrain(array(
			'name' => 'b0ss'
		));
		
		$user->roles()->purge();
		
		$expected = array(3);
		$actual = $this->storage->distinct('user_roles', 'role_id', array('user_id' => 1));
		
		$this->assertEqualValues($expected, $actual);
	}
	
	public function testBelongsToManyAssociationConstraint()
	{
		$user = User::find(1);
		
		$user->roles()->constrainAssociation(array(
			'sort <' => 2
		));
		
		$this->assertEquals(1, $user->roles()->count());
		$this->assertEquals('Administrator', $user->roles[0]->name);
	}
	
	public function testRelationAttributes()
	{
		$user = new User;
		
		$this->assertEquals(array('padawan', 'manager', 'master', 'posts', 'roles'), $user->relationAttributes());
	}
	
	public function testRelations()
	{
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

	public function testRelationQuery()
	{
		$chris = User::find(1);
		$john = User::find(3);

		$padawans = $chris->padawan()->query()->cheers();
		$posts = $chris->posts()->query()->cheers();
		$masters = $john->master()->query()->cheers();
		$roles = $john->roles()->query()->cheers();

		// Assert that we have the right types
		foreach ($padawans as $padawan) {
			$this->assertInstanceOf(User::class, $padawan);
		}

		foreach ($masters as $master) {
			$this->assertInstanceOf(User::class, $master);
		}

		foreach ($roles as $role) {
			$this->assertInstanceOf(Role::class, $role);
		}

		// Assert that we have the right count of each relation
		$this->assertEquals(1, count($padawans));
		$this->assertEquals(count($chris->posts), count($posts));
		$this->assertEquals(1, count($masters));
		$this->assertEquals(count($john->roles), count($roles));
	}

	public function testDefaultSearchAttributes()
	{
		$users = User::search('chris');
		
		$this->assertEquals(1, count($users));
		$this->assertEquals('Andrew', $users[0]->surname);
		
		$users = User::search('KING');
		
		$this->assertEquals(1, count($users));
		$this->assertEquals('Bethany', $users[0]->firstname);
	}
}

