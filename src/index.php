<?php

/**
 * License Service API Entry Point
 */

// Load autoloader
require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/App.php';

// Bootstrap and run the application
$app = new App();
$app->run();

?>