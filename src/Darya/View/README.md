# `Darya\View`

Views are used to separate an application's logic from its presentation. It's
good practice to treat them only as a means of displaying the data they are
given.

## PHP view

The simple `Darya\View\Php` class is provided for you to easily use PHP as a
templating engine.

An adapter already exists for the [Smarty](https://github.com/darya/smarty)
templating engine.

Adapters are planned for some popular templating engines including Blade, Twig
and Mustache.

### views/index.php

```php
<p>Hello <?=$thing?>, this is a <?=$test?>.</p>

<?php foreach ($somethings as $something): ?>
	<p><?=ucfirst($something)?> something.</p>
<?php endforeach; ?>
```

### index.php

```php
use Darya\View;

$view = new View\Php('views/index.php');

$view->assign(array(
	'thing' => 'world',
	'test'  => 'test',
	'somethings' => array('one', 'two', 'three')
));

echo $view->render();
```

### Output

```html
<p>Hello world, this is a test.</p>

	<p>One something.</p>
	<p>Two something.</p>
	<p>Three something.</p>
```
