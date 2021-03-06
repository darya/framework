<?php
namespace Darya\Tests\Unit\Database\Query\Translator;

use PHPUnit\Framework\TestCase;
use Darya\Database\Storage\Query;
use Darya\Storage\InMemory;
use Darya\Database\Storage;
use Darya\Database\Query\Translator;

/**
 * TODO: Update non-join queries to use plain Storage\Query.
 */
class MySqlTest extends TestCase {

	protected function translator() {
		return new Translator\MySql;
	}

	protected function mockConnection() {
		return $this->getMockBuilder('Darya\Database\Connection')->getMock();
	}

	public function testSelect() {
		$translator = $this->translator();

		$query = new Query('users');
		$query->where('age >=', 23)
			->where('name like', '%test%')
			->order('id')
			->limit(5);

		$result = $translator->translate($query);
		$this->assertEquals("SELECT * FROM `users` WHERE `age` >= ? AND `name` LIKE ? ORDER BY `id` ASC LIMIT 5", $result->string);
		$this->assertEquals(array(23, '%test%'), $result->parameters);

		$query->fields(array('id', 'firstname', 'lastname'));
		$query->where('role_id', array(1, 2, '3', '4', 5));
		$query->limit(0);

		$result = $translator->translate($query);
		$this->assertEquals("SELECT `id`, `firstname`, `lastname` FROM `users` WHERE `age` >= ? AND `name` LIKE ? AND `role_id` IN (?, ?, ?, ?, ?) ORDER BY `id` ASC", $result->string);
		$this->assertEquals(array(23, '%test%', 1, 2, '3', '4', 5), $result->parameters);

		$query->group('firstname');
		$query->having('id >', 6);
		$query->having('id <', 7);

		$result = $translator->translate($query);
		$this->assertEquals("SELECT `id`, `firstname`, `lastname` FROM `users` WHERE `age` >= ? AND `name` LIKE ? AND `role_id` IN (?, ?, ?, ?, ?) GROUP BY `firstname` HAVING `id` > ? AND `id` < ? ORDER BY `id` ASC", $result->string);
		$this->assertEquals(array(23, '%test%', 1, 2, '3', '4', 5, 6, 7), $result->parameters);

	}

	public function testSelectQueryFields() {
		$translator = $this->translator();

		$query = new Query('users', 'id');

		$this->assertEquals('SELECT `id` FROM `users`', $translator->translate($query)->string);

		$query->fields('test');

		$this->assertEquals('SELECT `test` FROM `users`', $translator->translate($query)->string);

		$query->fields(array('one', 'two'));

		$this->assertEquals('SELECT `one`, `two` FROM `users`', $translator->translate($query)->string);

		$query->fields();

		$this->assertEquals('SELECT * FROM `users`', $translator->translate($query)->string);
	}

	public function testSelectColumnsAliases() {
		$translator = $this->translator();

		$query = new Query('users', array('Identifier' => 'id'));

		$this->assertEquals('SELECT `id` `Identifier` FROM `users`', $translator->translate($query)->string);

		$query = new Query('users', array(
			'Identifier' => 'id',
			'First name' => 'firstname',
			'Surname'    => 'surname'
		));

		$this->assertEquals('SELECT `id` `Identifier`, `firstname` `First name`, `surname` `Surname` FROM `users`', $translator->translate($query)->string);
	}

	public function testSelectWhereRobustness() {
		$translator = $this->translator();

		$query = (new Query('users'))
			->where('name', '%test1%')
			->where('name ', '%test2%')
			->where('name like', '%test3%')
			->where('name like ', '%test4%')
			->where('name not like', '%test5%')
			->where('name not like ', '%test6%')
			->where('name like name')
			->where('users.name like users.name')
			->where('users.name not like users.name');

		$result = $translator->translate($query);

		$this->assertEquals(
			"SELECT * FROM `users` WHERE "
			. "`name` = ? AND `name` = ? "
			. "AND `name` LIKE ? AND `name` LIKE ? "
			. "AND `name` NOT LIKE ? AND `name` NOT LIKE ? "
			. "AND `name` LIKE `name` AND `users`.`name` LIKE `users`.`name` "
			. "AND `users`.`name` NOT LIKE `users`.`name`",
			$result->string
		);

		$this->assertEquals(
			array('%test1%', '%test2%', '%test3%', '%test4%', '%test5%', '%test6%'),
			$result->parameters
		);
	}

	public function testSelectWhereInNull() {
		$translator = $this->translator();

		$query = (new Query('users'))
			->where('id', array(null, null));

		$result = $translator->translate($query);

		$this->assertEquals(
			"SELECT * FROM `users` WHERE `id` IN (NULL, NULL)",
			$result->string
		);

		$this->assertEquals(array(), $result->parameters);

		$query->where('id', array(null, 5, null, 6));

		$result = $translator->translate($query);

		$this->assertEquals(
			"SELECT * FROM `users` WHERE `id` IN (NULL, ?, NULL, ?)",
			$result->string
		);

		$this->assertEquals(array(5, 6), $result->parameters);
	}

	public function testSelectWithJoins() {
		$translator = $this->translator();

		$query = new Query('users', array('users.*'));
		$query->join('posts', 'posts.user_id = users.id');
		$query->where('posts.title like', '%swag%');

		$result = $translator->translate($query);

		$this->assertEquals(
			"SELECT `users`.* FROM `users` "
			. "JOIN `posts` ON `posts`.`user_id` = `users`.`id` "
			. "WHERE `posts`.`title` LIKE ?",
			$result->string
		);

		$query->join('comments as c', 'c.post_id = posts.id');

		$result = $translator->translate($query);

		$this->assertEquals(
			"SELECT `users`.* FROM `users` "
			. "JOIN `posts` ON `posts`.`user_id` = `users`.`id` "
			. "JOIN `comments` `c` ON `c`.`post_id` = `posts`.`id` "
			. "WHERE `posts`.`title` LIKE ?",
			$result->string
		);
	}

	public function testSelectWithComplexJoins() {
		$translator = $this->translator();

		$query = new Query('pages', array('pages.*'));

		$query->join('sections as s', function($join) {
			$join->on('s.page_id = pages.id')
				->on('s.page_id = pages.id')
				->where('pages.id > ', 1)
				->where('or', array('s.page_id >' => 2, 's.id >' => 3));
		});

		$query->where('page.awesome', true);

		$result = $translator->translate($query);

		$this->assertEquals(
			"SELECT `pages`.* FROM `pages` "
			. "JOIN `sections` `s` ON `s`.`page_id` = `pages`.`id` "
			. "AND `s`.`page_id` = `pages`.`id` AND `pages`.`id` > ? "
			. "AND (`s`.`page_id` > ? OR `s`.`id` > ?) "
			. "WHERE `page`.`awesome` IS TRUE",
			$result->string
		);

		$this->assertEquals(array(1, 2, 3), $result->parameters);
	}

	public function testSelectWithColumnSubqueries() {
		$translator = $this->translator();

		$subquery = new Query('sections', array('title'));
		$subquery->where('sections.page_id = pages.id');
		$subquery->where('sections.title like', '%awesome%');

		$query = new Query('pages', array('id', $subquery));

		$result = $translator->translate($query);

		$this->assertEquals(
			"SELECT `id`, ("
				. "SELECT `title` FROM `sections` "
				. "WHERE `sections`.`page_id` = `pages`.`id` "
				. "AND `sections`.`title` LIKE ?"
			. ") FROM `pages`",
			$result->string
		);

		$this->assertEquals(array('%awesome%'), $result->parameters);
	}

	public function testSelectWithColumnSubqueryAliases() {
		$translator = $this->translator();

		$subquery = new Query('other_table');

		$query = new Query('users', array('Some Other Table' => $subquery));

		$this->assertEquals('SELECT (SELECT * FROM `other_table`) `Some Other Table` FROM `users`', $translator->translate($query)->string);
	}

	public function testSelectWithWhereSubqueries() {
		$translator = $this->translator();

		$query = new Query('pages');

		$subquery = new Query('old_pages', array('id'));
		$subquery->where('id >', 1);

		$query->where('id not in', $subquery);

		$result = $translator->translate($query);

		$this->assertEquals(
			"SELECT * FROM `pages` "
			. "WHERE `id` NOT IN (SELECT `id` FROM `old_pages` WHERE `id` > ?)",
			$result->string
		);

		$this->assertEquals(array(1), $result->parameters);
	}

	public function testSelectWithJoinSubqueries() {
		$translator = $this->translator();

		$query = new Query('pages');

		$query->join('comments', function($join) {
			$join->on('comments.page_id = pages.id');
			$join->where('comments.id in', (new Query('comments', array('id')))->where('page_id > ', 5));
		});

		$result = $translator->translate($query);

		$this->assertEquals(
			"SELECT * FROM `pages` JOIN `comments` ON `comments`.`page_id` = `pages`.`id` AND `comments`.`id` IN (SELECT `id` FROM `comments` WHERE `page_id` > ?)",
			$result->string
		);

		$this->assertEquals(array(5), $result->parameters);
	}

	public function testSelectWithQueryBuilderFilterValues() {
		$connection = $this->getMockBuilder('Darya\Database\Connection')->getMock();
		$storage = new Storage($connection);

		$builder = $storage->query('table');

		$tables = array('table_2', 'table_3', 'table_4', 'table_5');

		$current = $builder;

		foreach ($tables as $table) {
			$new = $storage->query($table, 'id');

			$current->where('id not in', $new);

			$current = $new;
		}

		$current->where('id', 'swag');

		$translator = $this->translator();

		$result = $translator->translate($builder->query);

		// Fuck yeah
		$this->assertEquals(
			"SELECT * FROM `table` "
			. "WHERE `id` NOT IN ("
				. "SELECT `id` FROM `table_2` "
				. "WHERE `id` NOT IN ("
					. "SELECT `id` FROM `table_3` "
					. "WHERE `id` NOT IN ("
						. "SELECT `id` FROM `table_4` "
						. "WHERE `id` NOT IN ("
							. "SELECT `id` FROM `table_5` "
							. "WHERE `id` = ?"
						. ")"
					. ")"
				. ")"
			. ")",
			$result->string
		);

		$this->assertEquals(array('swag'), $result->parameters);
	}

	public function testInsert() {
		$translator = $this->translator();

		$query = new Query('users');
		$query->create(array(
			'firstname' => 'Chris',
			'lastname'  => 'Andrew',
			'age'       => 23,
			'role_id'   => 1
		));

		$result = $translator->translate($query);
		$this->assertEquals("INSERT INTO `users` (`firstname`, `lastname`, `age`, `role_id`) VALUES (?, ?, ?, ?)", $result->string);
		$this->assertEquals(array('Chris', 'Andrew', 23, 1), $result->parameters);
	}

	public function testInsertSelect() {
		$translator = $this->translator();

		$oneYearAgo = date('Y-m-d H:i:s', strtotime('-1 year'));

		$query = (new Query('users_archive'))
			->createFrom((new Query('users'))->where('created <=', $oneYearAgo));

		$result = $translator->translate($query);

		$this->assertEquals('INSERT INTO `users_archive` SELECT * FROM `users` WHERE `created` <= ?', $result->string);
		$this->assertEquals(array($oneYearAgo), $result->parameters);

		$query = (new Query('users_archive', array('id', 'name', 'created')))
			->createFrom((new Query('users', array('id', 'name', 'created')))->where('created <=', $oneYearAgo));

		$result = $translator->translate($query);

		$this->assertEquals("INSERT INTO `users_archive` (`id`, `name`, `created`) SELECT `id`, `name`, `created` FROM `users` WHERE `created` <= ?", $result->string);
		$this->assertEquals(array($oneYearAgo), $result->parameters);
	}

	public function testUpdate() {
		$translator = $this->translator();

		$query = new Query('users');
		$query->update(array(
				'age' => 24,
				'comment' => "Oh god I'm too old"
			))->where('age >=', 23)
			->where('name like', '%swag%');

		$result = $translator->translate($query);
		$this->assertEquals("UPDATE `users` SET `age` = ?, `comment` = ? WHERE `age` >= ? AND `name` LIKE ?", $result->string);
		$this->assertEquals(array(24, "Oh god I'm too old", 23, '%swag%'), $result->parameters);

		$query->where('role_id', array(1, 2, '3', '4', 5));
		$query->limit(3);

		$result = $translator->translate($query);
		$this->assertEquals("UPDATE `users` SET `age` = ?, `comment` = ? WHERE `age` >= ? AND `name` LIKE ? AND `role_id` IN (?, ?, ?, ?, ?) LIMIT 3", $result->string);
		$this->assertEquals(array(24, "Oh god I'm too old", 23, '%swag%', 1, 2, '3', '4', 5), $result->parameters);
	}

	public function testDelete() {
		$translator = $this->translator();

		$query = new Query('users');
		$query->delete()
			->where('age <', 23)
			->where('type !=', 'normal');

		$result = $translator->translate($query);
		$this->assertEquals('DELETE FROM `users` WHERE `age` < ? AND `type` != ?', $result->string);
		$this->assertEquals(array(23, 'normal'), $result->parameters);

		$query->where('role_id not in', array(1, '2'));
		$query->limit('10');

		$result = $translator->translate($query);
		$this->assertEquals('DELETE FROM `users` WHERE `age` < ? AND `type` != ? AND `role_id` NOT IN (?, ?) LIMIT 10', $result->string);
		$this->assertEquals(array(23, 'normal', 1, '2'), $result->parameters);
	}

	public function testNullParameters() {
		$translator = $this->translator();

		$query = new Query('users');
		$query->where('age', null);
		$query->where('role_id !=', null);

		$result = $translator->translate($query);
		$this->assertEquals('SELECT * FROM `users` WHERE `age` IS NULL AND `role_id` IS NOT NULL', $result->string);
		$this->assertEquals(array(), $result->parameters);

		$query = new Query('users');
		$query->update(array('age' => null));

		$result = $translator->translate($query);
		$this->assertEquals('UPDATE `users` SET `age` = NULL', $result->string);
		$this->assertEquals(array(), $result->parameters);

		$query = new Query('users');
		$query->create(array(
			'name' => 'swag',
			'age'  => null
		));

		$result = $translator->translate($query);
		$this->assertEquals('INSERT INTO `users` (`name`, `age`) VALUES (?, NULL)', $result->string);
		$this->assertEquals(array('swag'), $result->parameters);
	}

	public function testArrayParameters() {
		$translator = $this->translator();

		$query = new Query('users');
		$query->where('role_id', array(1, 2, 3));
		$query->where('age !=', array(4, 5, 6));

		$result = $translator->translate($query);
		$this->assertEquals('SELECT * FROM `users` WHERE `role_id` IN (?, ?, ?) AND `age` NOT IN (?, ?, ?)', $result->string);
		$this->assertEquals(array(1, 2, 3, 4, 5, 6), $result->parameters);
	}

}
