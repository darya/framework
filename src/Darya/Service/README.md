# `Darya\Service`

Darya's service container can be used to manage and resolve dependencies within
an application.

## Usage

- [Resolving dependencies](#resolving-dependencies)
  - [Instantiating classes](#instantiating-classes)
  - [Invoking callables](#invoking-callables)
- [Services](#services)
  - [Registering services and aliases](#registering-services-and-aliases)
  - [Resolving services](#resolving-services)

### Resolving dependencies

Out of the box, the container can be used to invoke callables or instantiate
classes with their concrete type-hinted dependencies automatically resolved.

#### Instantiating classes

```php
use Darya\Service\Container;

class Foo
{
	public $bar;
	
	public function __construct(Bar $bar) {
		$this->bar = $bar;
	}
}

class Bar
{
	public $baz;
	
	public function __construct(Baz $baz) {
		$this->baz = $baz;
	}
}

class Baz {}

$container = new Container;

$foo = $container->create('Foo');

$foo instanceof Foo;           // true
$foo->bar instanceof Bar;      // true
$foo->bar->baz instanceof Baz; // true
```

#### Invoking callables

```php
$closure = function (Foo $foo) {
	return $foo;
};

$foo = $container->call($closure);

$foo instanceof Foo;           // true
$foo->bar instanceof Bar;      // true
$foo->bar->baz instanceof Baz; // true
```

### Services

Services are values, objects or closures registered with the container.

They are most useful when registering concrete implementations of interfaces
that can then be resolved automatically using the type hints of dependent
classes or callables.

#### Registering services and aliases

Services can be values, objects, or closures.

- Values can be interface or class names to be resolved by the container
- Objects can be used as concrete services
- Closures can be used to define and compose a service when it is first resolved

You can optionally define aliases for these services after the service
definitions themselves, or with a separate method call.

```php
$container = new Container;

// Register an object as a service
$container->set('App\SomeInterface', new App\SomeImplementation);

// Define an alias
$container->alias('some', 'App\SomeInterface');

// Register multiple services and aliases
$container->register(array(
	'App\SomeInterface'    => new App\SomeImplementation,
	'App\AnotherInterface' => function (Container $services) {
		return new App\AnotherImplementation($services->some);
	},
	'some'    => 'App\SomeInterface',
	'another' => 'App\AnotherInterface'
));
```

By default, closures are treated as instance definitions instead of factories.
This means the closure is executed once, when the service is first resolved,
and its return value is retained for subsequent resolutions.

#### Resolving services

```php
// Resolve services by class or interface
$container->resolve('App\SomeInterface');    // App/SomeImplementation
$container->resolve('App\AnotherInterface'); // App/AnotherImplementation

// Resolve services by alias
$container->resolve('some');    // App\SomeImplementation
$container->resolve('another'); // App\AnotherImplementation

// Shorter syntax
$container->some;    // App\SomeImplementation
$container->another; // App\AnotherImplementation

// Fetch services as they were registered
$container->get('some');     // App\SomeImplementation
$container->get('another');  // Closure

// Closures become lazy-loaded instances
$container->another === $container->another; // true
```
