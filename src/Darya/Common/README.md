# Darya

## Autoloading

The following examples assume the use of Composer.

Class autoloaders to save you from having to individually include a file for every class you want to use.

Composer's autoloader is preconfigured and will autoload all of your composer dependencies.

### Composer's autoloader
```php
require_once 'vendor/autoload.php';
```

### Darya's autoloader

If you want to use Darya's autoloader for your own code, include Darya's autoload script. It includes Composer's autoloader, if it exists, and returns an instance of Darya's autoloader.

It assumes that you'll be including it from the root directory of a composer project and its base path will be set to this directory.

```php
require_once 'vendor/darya/framework/autoloader.php';
```

You can configure the autoloader created by this script if desired.

Using the `namespaces()` method you can map namespaces to their relevant base directories or even fully qualified class names directly to PHP files.

Use namespaces as keys and directories as values for the array you pass to this function.

```php
$autoloader = require_once 'vendor/darya/framework/autoloader.php';

$autoloader->registerNamespaces(array(
	'MyNamespace' => 'app',
	'MyNamespace' => 'app/MyNamespace', // This works like the previous
	'MyNamespace\MyClass' => 'app/MyNamespace/MyClass.php'
));
```

Here is an example of instantiating and registering Darya's autoloader manually. 

Its constructor accepts a base path followed by a set of namespace to directory mappings.

```php
require_once 'vendor/darya/framework/src/Darya/Common/Autoloader.php';

use Darya\Common\Autoloader;

$autoloader = new Autoloader(__DIR__, array(
	'Darya' => 'vendor/darya/framework/src'
));

$autoloader->register();
```