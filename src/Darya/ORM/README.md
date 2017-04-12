# `Darya\ORM`

Darya's ORM package provides a simple and flexible Active Record implementation,
including a base class for domain models that makes common tasks a breeze.

- [Models](#models)
  - [Creating a model](#creating-a-model)
  - [Interacting with model attributes](#interacting-with-model-attributes)
  - [Iterating over a model](#iterating-over-a-model)
  - [Serializing a model](#serializing-a-model)
  - [Defining attribute types](#defining-attribute-types)
- [Records](#records)
  - [Setting up storage](#setting-up-storage)
  - [Table names](#table-names)
  - [Loading and saving](#loading-and-saving)
  - [Listing values](#listing-values)
  - [Query builder](#query-builder)
- [Record relationships](#record-relationships)
  - [Defining relationships](#defining-relationships)
  - [Loading and saving related records](#loading-and-saving-related-records)
  - [Eager loading](#eager-loading)

## Models

Darya models are self-validating objects used to represent business entities
within an application.

Darya's abstract `Model` implementation implements `ArrayAccess`, `Countable`,
`IteratorAggregate` and `Serializable`. It is essentially a flexible set of
data intended to represent an instance of a business entity.

### Creating a model

```php
use Darya\ORM\Model;

// Define a model
class Something extends Model
{
	
}

// Instantiate it with some data
$model = new Something([
	'id'   => 72,
	'name' => 'Something',
	'type' => 'A thing'
]);
```

### Interacting with model attributes

```php
// Access its attributes using any convenient syntax
$id   = $model->id;          // 72
$name = $model['name'];      // 'Something'
$type = $model->get('type'); // 'A thing'

// Set attributes in the same way
$model->id     = 73;
$model['name'] = 'Something else';
$model->set('type', 'Another thing');
```

### Iterating over a model

```php
$attributes = [];

foreach ($model as $key => $value) {
	$attributes[$key] = $value;
}
```

### Serializing a model

```php
$serialized = serialize($model);
$attributes = $model->toArray();
$json       = $model->toJson();
```

### Defining attribute types

```php
class Something extends Model
{
	protected $attributes = [
		'count' => 'int',
		'data'  => 'json'
	];
}

$model = new Something;

$model->count = '1';
$count = $model->count; // 1

$model->data = ['my' => 'data']; // Stored as '{"my":"data"}'
```

## Records

Records are supercharged [models](#models) with access to persistent storage through the
[`Darya\Storage`](/src/Darya/Storage) interfaces. They implement the active
record pattern, but with testability in mind.

### Setting up storage

The database connection for a single record instance, or all instances of a
specific type of record, can be swapped out for a different storage adapter.

```php
use Darya\Database\Connection;
use Darya\Database\Storage;
use Darya\ORM\Record;

$databaseStorage = new Storage(
	new Connection\MySql('hostname', 'username', 'password', 'database')
);

$inMemoryStorage = new Darya\Storage\InMemory;

// Use database storage for all Records
Record::setSharedStorage($databaseStorage);

// Use in-memory storage for this type of Record
TestRecord::setSharedStorage($inMemoryStorage);

// Use in-memory storage for this instance of a User Record
$user->storage($inMemoryStorage);

// Retrieve the current storage used by the User Record
$userStorage = $user->storage();
```

### Table names

They use the typical convention of a singular class name mapping to a plural
database table name.

```php
use Darya\ORM\Record;

class User extends Record
{
	
}
```

`User` would map to the **users** table.

This can of course be overridden, as can the default primary key of `id`.

```php
class User extends Record
{
	protected $key = 'uid';

	protected $table = 'people';
}
```

### Loading and saving

Records provide methods that you may be familiar with.

```php
// Change a single user
$user = User::find(1);
$user->name = 'Chris';
$user->save();

// Load all users
$users = User::all();

// Save the users
User::saveMany($users);
```

### Listing values

They also provide methods you may not have seen before.

```php
// List all of the values of a given attribute
$list = User::listing('name');

// List all of the distinct values of a given attribute
$names = User::distinct('name');
```

### Query builder

Powerful query building enables simple retrieval of specific models.

```php
$users = User::query()
	->where('name like', 'Chris')
	->where('parent_id', 72)
	->order('surname')
	->limit(5, 10)
	->cheers();
```

## Record relationships

[Records](#records) can express relationships between themselves and others.

### Defining relationships

Defining relationships is a breeze.

```php
class Page extends Record
{
	protected $relations = [
		'author'   => ['belongs_to',      'User',   'author_id'],
		'groups'   => ['belongs_to_many', 'Group'],
		'parent'   => ['belongs_to',      'Page',   'parent_id'],
		'children' => ['has_many',        'Page',   'parent_id'],
		'sections' => ['has_many',        'Section']
	];
}
```

### Loading and saving related records

Loading and saving them is just as easy.

```php
$page = Page::find(1);

foreach ($page->children as $child) {
	$child->title = "$page->title - $child->title";
}

$page->save();
```

Saving a record will save any of its loaded related models that have had their
attributes changed.

You can skip saving related models if need be.

```php
$page->save([
	'skipRelations' => true
]);

Page::saveMany($pages, [
	'skipRelations' => true
]);
```

### Eager loading

If you need to load the related records of many parent records, the eager
loading feature will help you out.

```php
$pages = Page::eager('children');

// Causes no storage queries; models are already loaded efficiently
foreach ($pages as $page) {
	$children = $page->children;
	// ...
}
```

Without eager loading (`Page::all()`), storage would be queried once for each
parent record's children.

With eager loading, all children are loaded efficiently; one query for each
relation.

An array of relations can be provided to this method to eagerly load multiple
relationships.

```php
// Load all pages and eagerly load all of their related records
$pages = Page::eager(['author', 'groups', 'parent', 'children', 'sections']);
```
