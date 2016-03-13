<?php

/**
 * Example index.php for your app
 */

// set a timezone
date_default_timezone_set("Europe/Stockholm");

// fire up the autoloader
require_once "vendor/autoload.php";

use KlangTiny\App;
use KlangTiny\App\Controller\NoRoute;

$app = new App();

// register a controller to use as fallback (404)
App::registerNoRouteController(new NoRoute());

// Add your own controllers to use
// Just extend KlangTiny\App\Controller
// App::registerController(new \Your\Own\Controller\Demo());

// RUN!
App::run();