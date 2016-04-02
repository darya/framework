<?php
date_default_timezone_set('Europe/London');

include 'src/Darya/Foundation/Autoloader.php';

use Darya\Foundation\Autoloader;

$autoloader = new Autoloader(__DIR__, array(
	'Darya' => 'src'
));

$autoloader->register();

return $autoloader;
