# Darya Framework

[![Latest Stable Version](https://poser.pugx.org/darya/framework/version)](//packagist.org/packages/darya/framework)
[![Latest Unstable Version](https://poser.pugx.org/darya/framework/v/unstable)](//packagist.org/packages/darya/framework)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/darya/framework.svg?style=flat)](https://scrutinizer-ci.com/g/darya/framework/?branch=develop)

Darya is a PHP framework for web application development.

Its components include:

- [ORM](/src/Darya/ORM)
- [CRUD storage abstractions](/src/Darya/Storage)
- [Service container](/src/Darya/Service)
- [HTTP abstractions](/src/Darya/Http)
- [Router](/src/Darya/Routing)
- [Event dispatcher](/src/Darya/Events)
- [Views](/src/Darya/View)

The framework is currently under development and the API is liable to change
until v1.0.0.

Eventually, each package will be split into its own repository.

## Installation

Use [composer](https://getcomposer.org) to install the `darya/framework`
package.

Otherwise just clone this repository into a directory such as
`/vendor/darya/framework`.

After this, you'll want to make use of a class autoloader to save you from
having to manually include every class. You can use composer's autoloader or
the autoloader that Darya provides.

### Composer's autoloader
```php
require_once 'vendor/autoload.php';
```

### Darya's autoloader

Darya's `autoloader.php` includes Composer's `autoload.php` if it can find it.

```php
require_once 'vendor/darya/framework/autoloader.php';
```

