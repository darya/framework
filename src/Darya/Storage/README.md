# `Darya\Storage`

Darya's storage package provides tools and interfaces for interacting with
queryable storage in a consistent and convenient way.

Some of the examples below explain features in the context of an SQL database,
but concept behind the package is abstracting
[CRUD](https://en.wikipedia.org/wiki/Create,_read,_update_and_delete) in a way
that can be extended as necessary, whether for an SQL database, a NoSQL
database, or even a file system.

## Usage

- [Queries](#queries)
  - [Resource](#resource)
  - [Fields](#fields)
  - [CRUD](#crud)
  - [Filters](#filters)
  - [Orders](#orders)
  - [Limit & offset](#limit--offset)
- [Results](#results)
- [Queryable interface](#queryable-interface)
  - [`execute()`](#execute)
  - [`query()`](#query)
- [Query builder](#query-builder)

### Queries

The `Query` class provides a fluent interface for defining storage queries.

Instances can be used to query [`Queryable` storage](#queryable-interface), and
such storage can even build [`Query\Builder`](#query-builder) objects for you to
start fluently building upon.

#### Resource

A resource is some segment of a data store referred to by a `string` identifier.

```php
use Darya\Storage\Query;

// Instantiate a new query for the 'users' resource
$query = new Query('users');

// Change the resource of an existing query
$query->resource('users');
```

In the context of an SQL database, a resource is a database table.

#### Fields

Fields are the properties of the resource the query should interact with. If no fields
are specified, all of them will be retrieved.

```php
// Instantiate a new query for the 'id' field of the 'users' resource
$query = new Query('users', 'id');

// Instantiate a new query for the two fields of the 'users' resource
$query = new Query('users', ['firstname', 'surname']);

// Change the fields of an existing query
$query->fields(['age', 'last_login']);
```

In the context of an SQL database, fields are the columns of a database table.

#### CRUD

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
```

In the context of an SQL database, these methods would become `INSERT`,
`SELECT`, `UPDATE` and `DELETE` queries.

#### Filters

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

#### Orders

Orders are used to sort items by a given set of fields in ascending or descending
order.

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
```

In the context of an SQL database, orders become an `ORDER` clause. You can use
the `sort()` alias method in place of the `order()` method.

#### Limit & offset

TODO.

### Results

TODO.

### Queryable interface

The `Queryable` interface conveys the primary concept behind this package.

```php
interface Queryable
{
	/**
	 * Execute the given query.
	 * 
	 * @param Query $query
	 * @return \Darya\Storage\Result
	 */
	public function execute(Query $query);
	
	/**
	 * Open a query on the given resource.
	 * 
	 * @param string       $resource
	 * @param array|string $fields   [optional]
	 * @return \Darya\Storage\Query\Builder
	 */
	public function query($resource, $fields = array());
}
```

#### `execute()`

The `execute()` method accepts a [`Query`](#queries) and returns a corresponding
[`Result`](#results).

This formalizes the structure of what is sent to a data store and what it
responds with.

#### `query()`

The `query()` method accepts a resource to query and optionally the fields to
act upon, returning a ready-to-use [query builder](#query-builder).

While it might *only* seem like a convenience method at first by saving you
from instantiating your own [`Query`](#queries) objects, it more importantly
allows implementors to easily provide their own [query builders](#query-builder)
that could make use of an extended `Query` class.

This is how the `Darya\Database\Storage` class works; by returning
`Query\Builder` that uses an extension of the base `Query` class, which provides
support for joins and subqueries.

On top of this, its `execute()` method can accept either a base `Query` or its
extended `Database\Storage\Query`.

This allows for flexibility without sacrificing the structured approach, and
leaves it to the developer to choose between exposing their application to the
usage of extra features (joins, subqueries) or where to keep it strictly CRUD
and interoperable with anything that works with the base `Query` class.

### Query builder

TODO.
