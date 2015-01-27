# Darya Framework

## HTTP Abstractions

Darya provides some simple abstractions for HTTP requests and responses, as well
as session control.

### Requests

Request objects can be created with just a URI.

An HTTP method can optionally be supplied (`GET`, `POST`, 'PUT, etc), with `GET`
being the default.

Request methods are treated case insensitively (and using lower case
internally, in case you were interested (not that it's important (and this may
well change to upper case at some point))).

#### Creating requests

```php
$request = new Request('/hello');
```

They can be populated with request data when instantiated. This data is expected to mirror the structure of PHP's superglobals, which means the superglobals themselves can be used to mimic the current request.

```php
$request = new Request($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'], array(
	'get'    => $_GET,
	'post'   => $_POST,
	'cookie' => $_COOKIE,
	'file'   => $_FILES,
	'server' => $_SERVER,
	'header' => Request::headersFromGlobals($_SERVER)
))
```

This shortcut method does all of the above for you.

```php
$request = Request::createFromGlobals();
```

#### Accessing request data

Assume the request URI `/hello?id=10` for the following examples.

##### Retrieving the request URI

```php
// GET /hello?id=10
$request->uri(); // '/hello'
```
##### Determining the request method

```php
$request->method();       // 'get'
$request->method('get');  // true
$request->method('post'); // false
```

##### Retrieving parameter values and other data

```php
$request->get('id');  // 10
$request->post('id'); // null
$request->any('id');  // 10 (checks post then get)

$request->cookie('my_cookie'); // Accessing cookies

// IP of the client that issued the current request
$request->server('REMOTE_ADDR');

$request->ip(); // Same as the above
```

##### Testing for an ajax request

```php
$request->header('X-Requested-With') == 'XmlHttpRequest';

$request->ajax(); // Similar to above but also checks for 'ajax' get and post parameters
```

