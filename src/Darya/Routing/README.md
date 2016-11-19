# `Darya\Routing`

Darya's router is the heart of the framework. It is used to match request paths to a `Route` object, which is essentially a set of parameters.

It can also be used to invoke functions or class methods derived from the parameters of matched routes.

This is a detailed set of examples of the different ways the router can be used.

## Front controller

First, you'll want to set up a PHP script as a [front controller](http://en.wikipedia.org/wiki/Front_Controller_pattern). If you're using Apache as your web server you could achieve this with a `.htaccess` file at the root of your public directory. 

```
RewriteEngine on

# Redirect requests for any non-existing files to index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule .* index.php [L,QSA]
```

## Router

### Defining and matching routes

You can define routes in an array when instantiating a router using request
paths for keys and route parameters defaults values.

Reserved route parameters are as follows:

- `namespace`  - The namespace to prepend to a matched controller
- `controller` - The class to use as a controller
- `action`     - The anonymous function or controller method to run when the
                 route is dispatched
- `params`     - Slash-delimited parameters to pass to actions as arguments.
                 This one is always optional and should be used at the end of a
                 route's request path.

Here is an example of instantiating the router with an initial route definition.
The anonymous function becomes the route's `action` parameter.

```php
use Darya\Routing\Router;

$router = new Router(array(
	'/' => function() {
		return 'Hello world!';
	}
));

/**
 * @var Darya\Routing\Route
 */
$route = $router->match('/'); // $route->action == function() {return 'Hello world!';}
```

### Route matching

Now that our `index.php` receives all requests for any files that don't exist, let's instantiate a router with its first route!

```php
require_once "vendor/darya/framework/autoloader.php";

use Darya\Routing\Router;

$router = new Router(array(
	'/' => array('message' => 'Hello world!'),
	'/test' => array('message' => 'Test!')
));

/**
 * @var Darya\Routing\Route
 */
$route = $router->match($_SERVER['REQUEST_URI']);

echo $route->message;
```

If you navigate to the root directory of your web server, you should see "Hello world!". If you then navigate to `/test` you should see 'Test!'.

This example instantiates a router with a couple of base route containing message parameters.

It then matches the current request URL to a route, and prints the matched route's `message` property.

When no route is matched using the given request, the `match` method returns `false`.

### Setting a base URL

If your application is not in your web server's root web directory, but instead in some subdirectory, you can let the router know by setting a base URL.

This is simply a case of using the router's `setBaseUrl` method.

```php
$router = new Router(array(
	'/' => array('message' => 'Hello world!'),
	'/test' => array('message' => 'Test!')
));

$router->setBaseUrl('/darya');

echo $router->match('/darya')->message; // Displays 'Hello world!'
echo $router->match('/darya/test')->message; // Displays 'Test!'
```

Now, provided your `.htaccess` and `index.php` files are in a subfolder named `darya`, the router will work just as it did in the web server's root web directory. 

This works simply by removing the given base URL from the beginning of any URLs passed to the `match` method.

### Routing to functions

Darya's router's `dispatch` method can be used to run functions when a route is matched.

When you assign a function to a route, it's stored in the route's `action` parameter, the same way we set the `message` parameter previously. 

The `dispatch` method simply matches the given request URL to a route and executes its action if one is found. It does some other stuff too but that will be covered later on.

When no route is matched by the given request, the `dispatch` method returns `null`.

```php
$router = new Router(array(
	'/' => function(){
		return 'Hello world!';
	}
));

echo $router->dispatch($_SERVER['REQUEST_URI']);
```

### Dynamic route parameters

Darya's router is capable of matching parts of request URLs and setting them as properties on the matched route. If the route already has the matched property, it will be overwritten with the value in the request URL.

You can specify properties in the route's path by prepending a string with the `:` character. The following example will print anything after the last `/` of the request URL.

```php
$router = new Router(array(
	'/'         => array('message' => 'Hello world!'),
	'/:message' => array('message' => 'default')
));

echo $router->match('/test')->message; // Displays 'test'
echo $router->match('/darya')->message; // Displays 'darya'
```

You can use dynamic parameters with functions too. Matched parameters will be 
passed in order as arguments to the function. It doesn't matter whether the 
parameter names match the argument names.

```php
$router = new Router(array(
	'/' => function() {
		return 'Hello world!';
	},
	'/:message' => function($message) {
		return "Hello $message!";
	}
));

echo $router->dispatch($_SERVER['REQUEST_URI']);
```

With this configuration, visiting `/mate` will display `Hello mate!`, `/dude`
will display `Hello dude!` and so on.

### Optional parameters

You can make a URL parameter optional by appending the `?` character. You should
make the function argument optional so that no error if there is no default
value for the parameter.

```php
$router = new Router(array(
	'/:message?' => function($message = null) {
		return $message ? 'Message: ' . $message : 'No message!';
	}
));
```

### Responding automatically

To avoid having to `echo` the result of your `dispatch` call you can use the
`respond` method instead. This creates an HTTP response from whatever your
functions return.

```php
$router = new Router(array(
	'/about/:what' => function($what) {
		return "About $what!";
	},
	'/' => function(){
		return 'Hello world!';
	}
));

$router->respond('/about/me'); // 'About me!'
```

### Error handling

If no route is matched by the request, `match` will return false and `dispatch`
will return null after attempting to call an error handler. You can assign any
callable as the router's error handler.

```php
$router->setErrorHandler(function($request){
	return 'No route was matched!';
});
```

### Routing to class methods

You can assign any callable to a route and it will become the route's action
parameter.

```php
class MyClass
{
	public function myMethod($message) {
		return $message ? "Message: $message" : 'No message';
	}
}

$router = new Router(array(
	'/:message' => array(new MyClass(), 'myMethod')
));
```

If you assign a string to the route, it is interpreted as a class name and set
as the route's `controller` parameter. When dispatching, the router will then
use the `action` parameter as a method to call on the `controller`.

The default value for the action parameter is `index`.

```php
class MyClass
{
	public function index($message) {
		return $message ? "Message: $message" : 'No message';
	}
}

$router = new Router(array(
	'/:message' => 'MyClass'
));
```

Bear in mind that the class will not be instantiated if you assign routes this
way. This is a task for Darya's `Dispatcher` class.

### Dynamic actions

You can use dynamic parameters to decide which class method should be run when
a route is matched. This is useful for allowing a single class to handle
different requests.

```php
class MyClass
{
	public function index() {
		return 'Index!'
	}
	
	public function test() {
		return 'Test action!';
	}
}

$router = new Router(array(
	'/:action?' => 'MyClass'
));

$router->respond('/'); // Displays 'Index!'
$router->respond('/test'); // Displays 'Test action!';
```

You can also suffix a method name with Action and it will still be matched in
the same way. This is useful in the case of using reserved words as action
names. For example, a `newAction()` method would be matched by the URL `/new`.
