<?php
// Darya's autoloader
include 'src/Darya/Common/Autoloader.php';

use Darya\Common\Autoloader;

$autoloader = new Autoloader(__DIR__, array(
	'Darya' => 'src'
));

$autoloader->register();

return $autoloader;
