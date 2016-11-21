# `Darya\ORM`

Darya's ORM package provides a simple and flexible Active Record implementation,
including a base class for domain models that makes common tasks a breeze.

## Models

Darya models are self-validating objects used to represent business entities
within an application.

Darya's abstract `Model` implementation implements `ArrayAccess`, `Countable`,
`IteratorAggregate` and `Serializable`. It is essentially a flexible set of
data intended to represent one instance of a business entity.

### Creating a model

```php
use Darya\ORM\Model;

// Define a model
class Something extends Model
{
	
}

// Instantiate it with some data
$model = new Something(array(
	'id'   => 72,
	'name' => 'Something',
	'type' => 'A thing'
));
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
$attributes = array();

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
	protected $attributes = array(
		'count' => 'int',
		'data'  => 'json'
	);
}

$model = new Something;

$model->count = '1';
$count = $model->count; // 1

$model->data = array('my' => 'data'); // Stored as '{"my":"data"}'
```

## Records

Records are supercharged models with access to persistent storage through the
[`Darya\Storage`](/src/Darya/Storage) interfaces. They implement the active
record pattern, but with testability in mind.

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

// Set storage for all Records
Record::setSharedStorage($databaseStorage);

// Use in memory storage for this type of Record
TestRecord::setSharedStorage($inMemoryStorage);

// Use in memory storage for this instance of a User Record
$user->storage($inMemoryStorage);

// Retrieve the current storage used by the User Record
$userStorage = $user->storage();
```

They use the typical convention of a singular class name mapping to a plural
database table name.

```php
use Darya\ORM\Record;

class User extends Record
{
	
}
```

`User` would map to the **users** table. This can of course be overriden.

```php
class User extends Record
{
	protected $table = 'people';
}
```

Records provide methods that you may be familiar with.

```php
// Change a single user
$user = User::find(1);
$user->name = 'Chris';
$user->save();

// Load all users
$users = User::all();
```

And some you may not have seen before.

```php
// Load all of the values of a given attribute
$list = User::listing('name');

// Load all of the distinct values of a given attribute
$names = User::distinct('name');
```

### Relationships

Defining and working with relationships is a breeze.

```php
class Page extends Record
{
	protected $relations = [
		'author'   => ['belongs_to', 'User', 'author_id'],
		'parent'   => ['belongs_to', 'Page', 'parent_id'],
		'children' => ['has_many',   'Page', 'parent_id'],
		'sections' => ['has_many',   'Section']
	];
}

$page = Page::find(1);

$children = $page->children;

foreach ($children as $child) {
	$child->title = "$page->title - $child->title";
}

$page->children = $children;

$page->save();
```
