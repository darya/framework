# `Darya\Storage`

Darya's storage package provides tools and interfaces for interacting with
queryable data stores in a consistent, convenient way.

Some of the examples below explain features in the context of an SQL database,
but the core concept behind the package is the extensible,
database agnostic abstraction of
[CRUD](https://en.wikipedia.org/wiki/Create,_read,_update_and_delete).

This means it could be used for an SQL database, a NoSQL database, or even a
file system.

- [Examples](#examples)
  - [Create](#create)
  - [Read](#read)
  - [Update](#update)
  - [Delete](#delete)
- [Queryable interface](#queryable-interface)
  - [`run()`](#run)
  - [`query()`](#query)
- [Queries](#queries)
  - [Resource](#resource)
  - [Fields](#fields)
  - [CRUD](#crud)
  - [Filters](#filters)
  - [Orders](#orders)
  - [Limit & offset](#limit--offset)
  - [Unique](#unique)
- [Results](#results)
- [Query builder](#query-builder)
  - [Callbacks](#callbacks)

## Examples

These quick examples demonstrate a storage implementation in action.

See the documentation sections below the examples for more detail.

See the `Darya\Database` package for documentation about joins and subqueries.

### Create

```php
$result = $storage->query('users')
	->create([
		'id'        => 1,
		'firstname' => 'Obi-Wan',
		'surname'   => 'Kenobi
	])
	->run();
```

### Read

```php
$result = $storage->query('users', ['id', 'firstname', 'surname'])
	->where('firstname like', 'Obi-Wan')
	->where('manager_id >', 0),
	->order('surname')
	->limit(5, 10)
	->run();
```

### Update

```php
$result = $storage->query('users')
	->update([
		'firstname' => 'Qui-Gon',
		'surname'   => 'Jinn'
	])
	->where('surname', 'Kenobi')
	->run();
```

### Delete

```php
$result = $storage->query('users')
	->delete()
	->where('type like', 'force user')
	->where('id >', 5)
	->run();
```

## Queryable interface

The `Queryable` interface conveys the primary concept behind this package.

```php
use Darya\Storage\Query;
use Darya\Storage\Result;

interface Queryable
{
	/**
	 * Run the given query.
	 * 
	 * @param Query $query
	 * @return Result
	 */
	public function run(Query $query);
	
	/**
	 * Open a query on the given resource.
	 * 
	 * @param string       $resource
	 * @param array|string $fields   [optional]
	 * @return Query\Builder
	 */
	public function query($resource, $fields = array());
}
```

### `run()`

The `run()` method accepts a [`Query`](#queries) and returns a corresponding
[`Result`](#results).

This formalizes the structure of what is sent to a data store and what it
responds with.

### `query()`

The `query()` method accepts a resource to query and optionally the fields to
act upon, returning a ready-to-use [query builder](#query-builder).

While it might *only* seem like a convenience method at first by saving you
from instantiating your own [`Query`](#queries) and
[`Query\Builder`](#query-builder) objects, it more importantly allows
implementors to easily provide their own query builders that can make use of an
extended `Query` class.

This is how the `Darya\Database\Storage` class works; by returning
`Query\Builder` that uses an extension of the base `Query` class, which provides
support for joins and subqueries.

On top of this, the `run()` method can accept either a base `Query` or its
extended `Database\Storage\Query`.

This allows for flexibility without sacrificing the structured approach, and
leaves it to the developer to choose between exposing their application to the
usage of extra features (joins, subqueries) or whether to keep it strictly CRUD
and interoperable with any `Queryable` storage that works with the base `Query`
class.

## Queries

The `Query` class provides a fluent interface for defining storage queries.

Instances can be used to query [`Queryable` storage](#queryable-interface), and
such storage can even build [`Query\Builder`](#query-builder) objects for you to
start fluently building upon.

### Resource

A resource is some segment of a data store referred to by a `string` identifier.

```php
use Darya\Storage\Query;

// Instantiate a new query for the 'users' resource
$query = new Query('users');

// Change the resource of an existing query
$query->resource('users');

// Retrieve the resource of the query
$resource = $query->resource;
```

In the context of an SQL database, a resource is a database table.

### Fields

Fields are the properties of the resource the query should interact with. If no fields
are specified, all of them will be retrieved.

```php
// Instantiate a new query for the 'id' field of the 'users' resource
$query = new Query('users', 'id');

// Instantiate a new query for the two fields of the 'users' resource
$query = new Query('users', ['firstname', 'surname']);

// Change the fields of an existing query
$query->fields(['age', 'last_login']);

// Retrieve the fields of the query
$fields = $query->fields;
```

In the context of an SQL database, fields are the columns of a database table.

### CRUD

CRUD is short for [create, read, update and delete](https://en.wikipedia.org/wiki/Create,_read,_update_and_delete).

Corresponding methods allow you to simply modify a `Query` to perform one of
these actions.

```php
$query = new Query('users');

// Create a new user
$query->create([
	'firstname' => 'Foo',
	'surname'   => 'Bar'
]);

// Read the firstname and surname fields
$query->read(['firstname', 'surname']);

// Update fields with the given values
$query->update([
	'firstname' => 'Bar',
	'surname'   => 'Baz'
]);

// Delete any matching items
$query->delete();

// Retrieve the type of the query
$type = $query->type;

// Retrieve the data to be created or updated
$data = $query->data;
```

In the context of an SQL database, these methods would represent `INSERT`,
`SELECT`, `UPDATE` and `DELETE` queries.

### Filters

Filters are used to place restrictions on data that the `Query` will `read()`,
`update()` or `delete()`.

These are not yet formalized internally, but follow a simple convention: the
first value refers to the field of a resource, optionally proceeded by some
comparison operator, and the second value is the value to filter by.

Or comparisons are represented by the special `'or'` key (in either `filter()`
or `filters()` calls), the value of which can be an array of filter conditions.

```php
// Filter items down to those that match the criteria
$query->filter('firstname', 'Foo')->filter('age >', 24);

// Add multiple filters at once
$query->filters([
	'surname'    => 'Bar',
	'age <'      => 30,
	'role_id in' => [1, 2, 3]
]);

// Add an 'or' filter
$query->filter('or', [
	'firstname like' => '%Foo%',
	'surname   like' => '%Bar%'
]);

// All of the above applied at once
$query->filters([
	'firstname'  => 'Foo',
	'age >'      => 24,
	'age <'      => 30,
	'role_id in' => [1, 2, 3],
	'or' => [
		'firstname like' => '%Foo%',
		'surname   like' => '%Bar%'
	]
]);

// Retrieve the set of query filters
$filters = $query->filter;
```

In the context of an SQL database, filters become a `WHERE` clause. You can use
the `where()` alias method in place of the `filter()` method.

The above example would translate to the following `WHERE` clause:

```sql
WHERE firstname = 'Foo'
AND age > 24
AND age < 30
AND role_id IN (1, 2, 3)
AND (firstname LIKE '%Foo%' OR surname LIKE '%Bar%')
```

### Orders

Orders are used to sort items by a given set of fields in ascending or
descending order for `read()` queries.

```php
// Sort by surname in ascending order
$query->order('surname');

// Also sort by firstname in descending order
$query->order('firstname', 'desc');

// All of the above applied at once
$query->orders([
    'firstname',
    'surname' => 'desc'
]);

// Retrieve the set of query orders
$orders = $query->order;
```

In the context of an SQL database, orders become an `ORDER` clause. You can use
the `sort()` alias method in place of the `order()` method.

### Limit & offset

Limit & offset are used to constrain the number of items and skip past the a
number of matching items that can be retrieved from `read()` queries, and in
some cases those affected by `update()` and `delete()` queries.

```php
// Retrieve 5 items
$query->limit(5);

// Retrieve 5 items after skipping the first 10 items
$query->limit(5, 10);

// Skip 5 items
$query->offset(5);

// Retrieve the limit and offset of the query
$limit  = $query->limit;
$offset = $query->offset;
```

In the context of an SQL database, limit & offset become a `LIMIT` clause. You
can use the `skip()` alias method in place of the `offset()` method.

### Unique

Unique queries return items that are unique across all of their fields.

```php
$query->unique();
```

To change a unique query back to a regular query that returns all rows, just
use the `all()` method.

```php
$query->all();
```

In the context of an SQL database, the unique setting translates to using
a `SELECT DISTINCT` statement. You can also use the `distinct()` alias method
in place of the `unique()` method.

## Results

The `Result` class provides a consistent way to represent storage query results.

```php
// Retrieve the storage query that led to this result
$query = $result->query;

// Retrieve any error that occurred with the query
if ($result->error) {
    $errorNumber  = $result->error->number;
    $errorMessage = $result->error->message;
    
    throw new Exception("Storage error $errorNumber: $errorMessage");
}

// Retrieve any data retrieved by the query
$data = $result->data;

// Or iterate over the result data using the result object directly
foreach ($result as $item) {
    $firstname = $item['firstname'];
    $surname   = $item['surname'];
}

// Retrieve other result metadata
$count    = $result->count;
$fields   = $result->fields;
$affected = $result->affected;
$insertId = $result->insertId;
```

## Query builder

Query builders encapsulate a fluent [`Query`](#queries) in the context of some
[`Queryable` storage](#queryable-interface).

They work in the same way as `Query` objects, but have the added ability to
execute themselves against storage and return a [`Result`](#results) in the same
call chain, as well as process the `Result`s before they're returned.

```php
use Darya\Storage\Query;

$query = new Query\Builder(new Query('users'), $storage);

$result = $query->where('id >', 5)->run();
```

Storage interfaces can open query builders on themselves for you. Just pass a
resource to their [`query()`](#query) method.

This makes it effortless to fluently build and run a query.

```php
$result = $storage->query('users')->where('id > 5')->run();

foreach ($result as $item) {
    // ...
}
```

Execution can also be triggered by existing methods on query objects.

`all()`, `read()`, `select()`, `unique()`, `distinct()`, and `delete()` methods
are query executors in the context of a builder.

```php
// Delete users with IDs less than 50 and retrieve how many were deleted
$deleted = $storage->query('users')->where('id <', 50)->delete()->affected;

// Read unique first names beginning with C
$result = $storage->query('users', 'firstname')->where('firstname like', 'C%')->unique();
```

### Callbacks

You can attach a [PHP
callables](http://php.net/manual/en/language.types.callable.php) to query
builders to process results before they're returned from execution.

```php
use Darya\Storage\Result;

$query = $storage->query('users')->callback(function (Result $result) {
    $users = [];
    
    foreach ($result as $item) {
        $users[] = new User($item);
    }
    
    return $users;
});

// User[]
$users = $query->where('surname', 'Foo')->read();
```

This functionality is used by the ORM package to convert query results into to
the desired model objects.
