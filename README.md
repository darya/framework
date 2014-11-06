# Darya Framework

Darya is a PHP framework for web application development.

Its components include:

- Autoloader
- Router & Dispatcher
- Service container
- MVC foundation
- Simple abstractions for HTTP requests and responses

The framework has been extracted from and is intended as a foundation for the Darya CMS project.

Inspired by PHP frameworks such as Laravel, Phalcon and Symfony.

## Installation

Use [composer](https://getcomposer.org)! The package name is `darya/framework`.

Otherwise, just clone this repository.

## Usage

### Autoloading

The following examples assume the use of Composer.

To get started you'll want to make use of a class autoloader to save you from having to individually include a file for every class you want to use.

Composer's autoloader is preconfigured and will autoload all of your composer dependencies.

#### Composer's autoloader
```php
require_once 'vendor/autoload.php';
```

#### Darya's autoloader

If you want to use Darya's autoloader for your own code, just include Darya's autoload script. It includes Composer's autoloader, if it exists, and returns an instance of Darya's autoloader.

It assumes that you'll be including it from the root directory of a composer project and its base path will be set to this directory.

```php
require_once 'vendor/darya/framework/autoloader.php';
```

You can configure the autoloader created by this script if desired. 

Using the `registerNamespaces` function you can map namespaces to their relevant base directories or even fully qualified class names directly to PHP files.

Use namespaces as keys and directories as values for the array you pass to this function.

```php
$autoloader = require_once 'vendor/darya/framework/autoloader.php';

$autoloader->registerNamespaces(array(
	'MyNamespace' => 'src',
	'MyNamespace' => 'src/MyNamespace', // This also works
	'MyNamespace\MyClass' => 'src/MyNamespace/MyClass.php'
));
```

Here is an example of instantiating and registering Darya's autoloader manually. 

Its constructor arguments are a base path followed by a set of namespace to directory mappings.

```php
require_once 'vendor/darya/framework/src/Darya/Common/Autoloader.php';

use Darya\Common\Autoloader;

$autoloader = new Autoloader(__DIR__, array(
	'Darya' => 'vendor/darya/framework/src'
));

$autoloader->register();
```

### Routing

Darya's router is the heart of the framework. It is used to match request paths to a `Route` object, which is essentially a set of configuration variables.

It can also be used to run functions or class methods derived from matched routes.

#### Front controller

First, you'll want to set up a PHP script as a [front controller](http://en.wikipedia.org/wiki/Front_Controller_pattern). If you're using Apache as your web server you could achieve this with a `.htaccess` file at the root of your public directory. 

```
RewriteEngine on

# Redirect requests for any non-existing files to index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^([A-Za-z0-9-/]+.*)$ index.php/$1 [L,QSA]

# Optionally prevent direct access to any PHP files other than index.php
#RewriteCond %{REQUEST_FILENAME} !(index\.php)
#RewriteRule ^(.*)\.php$ - [R=403]
```

#### Basic route matching

Now that our `index.php` receives all requests for any files that don't exist, let's instantiate a router with its first route!

```php
require_once "vendor/darya/framework/autoloader.php";

use Darya\Routing\Router;

$router = new Router(array(
	'/' => array('message' => 'Hello world!')
));

/**
 * @var Darya\Routing\Route
 */
$route = $router->match($_SERVER['REQUEST_URI']);

echo $route->message;
```

If you navigate to the root directory of your web server, you should see "Hello world!".

This example instantiates a router with a base route containing a message property with the value `'Hello world!'`.

It then matches the current request URL (which should be '/' thanks to our .htaccess file) to this route, and prints the route's `message` property.

#### Setting a base URL

If your application is not in your web server's root web directory, but instead in some subdirectory, you can let the router know by setting a base URL.

This is simply a case of using the router's `setBaseUrl` method.

```php
$router = new Router(array(
	'/' => array('message' => 'Hello world!')
));

$router->setBaseUrl('/darya');

echo $router->match($_SERVER['REQUEST_URI'])->message;
```

Now, provided your `.htaccess` and `index.php` files are in a subfolder named `darya`, the router will work just as it did in the web server's root web directory. 

This works simply by removing the given base URL from the beginning of any URLs passed to the `match` method.

#### Routing to functions

Darya's router's `dispatch` method can be used to run functions when a route is matched.

When you assign a function to a route, it's stored in the route's `action` property, the same way we set its `message` property previously. 

The `dispatch` method simply matches the given request URL to a route and executes its action if one is found (it does some other stuff too but that's cover later on).

```php
$router = new Router(array(
	'/' => function(){
		return 'Hello world!';
	}
));

echo $router->dispatch($_SERVER['REQUEST_URI']);
```

#### Dynamic route properties

Darya's router is capable of matching parts of request URLs and setting them as properties on the matched route. If the route already has the matched property, it will be overwritten with the value in the request URL.

You can specify properties in the route's path using the `:` character. The following example will print anything after the last `/` of the request URL.

```php
$router = new Router(array(
	'/'         => array('message' => 'Hello world!'),
	'/:message' => array('message' => 'default')
));

echo $router->match($_SERVER['REQUEST_URI'])->message;
```

You can use dynamic properties with functions too. Matched properties will be passed in order as arguments to the function.

```php
$router = new Router(array(
	'/' => function(){
		return 'Hello world!';
	},
	'/:message' => function($message){
		return "Hello $message!";
	}
));

echo $router->dispatch($_SERVER['REQUEST_URI']);
```

With this configuration, visiting `/mate` will print `Hello mate!`.