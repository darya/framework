<?php
// Composer's autoloader
if (is_file(__DIR__ . '/../../autoload.php')) {
	include __DIR__ . '/../../autoload.php';
}

// Darya's autoloader
include __DIR__ . '/src/Darya/Common/Autoloader.php';

use Darya\Common\Autoloader;

$autoloader = new Autoloader(realpath('./'), array(
	'Darya' => 'vendor/darya/framework/src'
));

$autoloader->register();

return $autoloader;
