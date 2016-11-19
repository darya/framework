# `Darya\Database`

Darya's database package provides tools and abstractions for interacting with
different relational databases in a consistent way, despite differences in SQL
dialects.

## Usage

- [Connections](#connections)
  - [Queries](#queries)
  - [Results](#results)
- [Storage](#storage)
- [Joins & Subqueries](#joins--subqueries)
  - [Simple joins](#simple-joins)
  - [Complex join](#complex-join)
  - [Where-condition subquery](#where-condition-subquery)
  - [Insert select](#insert-select)

### Connections

Create database connections using a factory.

Supported databases are currently just `'mysql'` and `'mssql'`/`'sqlserver'`.

```php
use Darya\Database\Factory;

$factory = new Factory;

$connection = $factory->create('mysql', array(
	'hostname' => 'localhost',
	'username' => 'darya',
	'password' => 'password',
	'database' => 'darya'
));
```

Connections aren't initiated until you explicitly call either the `connect()` or
`query()` method.

```php
$connection->connect();
```

If you prefer, you can just instantiate a connection yourself.

```
use Darya\Database\Connection\MySql;
use Darya\Database\Connection\SqlServer;

$mySqlConnection = new MySql('hostname', 'username', 'password', 'database');

$sqlServerConnection = new SqlServer('hostname', 'username', 'password', 'database');
```

### Queries

Perform simple queries and retrieve their result data.

```php
$result = $connection->query('SELECT * FROM users');

// You can iterate straight over the result object
foreach ($result as $row) {
	// ...
}

// Or grab the array of result data directly
$data = $result->data;

// Optionally accepts parameters to bind
$result = $connection->query('SELECT * FROM users WHERE id = ?', array(1));
```

Or use a query object.

```php
use Darya\Database\Query;

$query = new Query('SELECT * FROM users WHERE name LIKE ?', array('%darya%'));

$query->string;     // The SQL query string
$query->parameters; // The query parameters

$result = $connection->query($query);
```

### Results

Access result metadata.

```php
$result->count;
$result->fields;
$result->affected;
$result->insertId;
```

Access the query that produced the result.

```php
$query = $result->query; // Darya\Database\Query

$sql = $result->query->string;
```

Results will expose an error property if an error occurred with the query.

```php
$result->error; // Darya\Database\Error

if ($result->error) {
	$result->error->number;
	$result->error->message;
}
```

### Storage

The database storage namespace provides a fluent interface for interacting with
a connection.

```php
use Darya\Database\Storage;

$storage = new Storage($connection);
```

Once you've created a storage object with a connection, you can start querying.

See the `Darya\Storage` namespace to learn more about using the query builder.

```php
$result = $storage->query('users')->where('id >', 50)->read();
```

Results allow you to access the storage and database queries that produced it.

You can access metadata and error data in the same way as connection queries.

```php
// Queries
$result->query;         // Darya\Database\Storage\Query
$result->databaseQuery; // Darya\Database\Query

// Metadata
$result->count;
$result->fields;
$result->affected;
$result->insertId;

if ($result->error) {
	$result->error->number;
	$result->error->message;
}
```

### Joins & Subqueries

Database storage queries offer extra query builder functionality.

#### Simple joins

```php
$result = $storage->query('users', 'users.*')
	->join('comments', 'comments.user_id = users.id')
	->where('comments.body like', '%darya%')
	->read();

$storage->query('users')->leftJoin('comments')->read();
$storage->query('users')->rightJoin('comments')->read();
```

#### Complex join

```php
$result = $storage->query('users', array(
		'users.id'   => 'user_id',
		'admin.id'   => 'admin_id',
		'users.name' => 'name'
	))
	->join('admin', function ($join) {
		$join->on('admin.user_id = users.id'); // Identifier-only condition
		$join->where('admin.active >', 0);     // Value condition
	})
	->where('users.name like', '%darya%')
	->read();
```

#### Where-condition subquery

```php
$result = $storage->query('users')->where('id not in', $storage->query(
	'users_archive', array('id')
))->read();
```

#### Insert select

Insert into a table using the result of another query.

```php
$storage->query('users_archive')->insertFrom(
	$storage->query('users')->where('created <=', strtotime('-1 year'))
)->execute();
```

This works when providing columns too.

```php
$storage->query('users_archive', array('id', 'name'))->insertFrom(
	$storage->query('users', array('id', 'name'))->where(
		'created <=', strtotime('-1 year')
	)
)->execute();
```