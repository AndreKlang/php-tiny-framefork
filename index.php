<?php
require_once "vendor/autoload.php";

use KlangTiny\App\Controller;
use KlangTiny\App\Controller\NoRoute;

$app = new App();

App::registerNoRouteController(new NoRoute());

App::run();