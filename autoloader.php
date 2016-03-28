<?php
// Composer's autoloader
if (is_file(__DIR__ . '/../../autoload.php')) {
	include __DIR__ . '/../../autoload.php';
}

// Darya's autoloader
include __DIR__ . '/src/Darya/Foundation/Autoloader.php';

use Darya\Foundation\Autoloader;

// Base path wherever it's included from, absolute path to the framework
$autoloader = new Autoloader(realpath('./'), array(
	'Darya' => realpath(__DIR__ . '/src')
));

$autoloader->register();

return $autoloader;
